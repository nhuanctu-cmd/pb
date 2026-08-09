<?php

namespace App\Services;

use App\Models\BookingRecurringTemplateModel;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;

class RecurringBookingService
{
    private BookingRecurringTemplateModel $templateModel;
    private BranchModel $branchModel;
    private CourtModel $courtModel;
    private PlayerModel $playerModel;
    private BookingService $bookingService;

    public function __construct()
    {
        $this->templateModel = new BookingRecurringTemplateModel();
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
        $this->playerModel = new PlayerModel();
        $this->bookingService = service('bookingService');
    }

    public function list(int $tenantId, array $filters = []): array
    {
        return $tenantId > 0 ? $this->templateModel->getByTenant($tenantId, $filters) : [];
    }

    public function createTemplate(array $data, int $tenantId, ?int $userId = null): array
    {
        $validation = $this->validateTemplate($data, $tenantId);
        if ($validation !== true) {
            return ['success' => false, 'message' => $validation];
        }

        $dates = $this->buildOccurrenceDates(
            (string) $data['start_date'],
            (string) $data['end_date'],
            (string) $data['repeat_type'],
            (int) ($data['repeat_interval'] ?? 1),
            $this->decodeDateList($data['repeat_days'] ?? []),
            $this->decodeDateList($data['exclude_dates'] ?? [])
        );
        if (empty($dates)) {
            return ['success' => false, 'message' => 'Không tạo được occurrence trong khoảng ngày đã chọn.'];
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $id = $this->templateModel->insert([
            'tenant_id' => $tenantId,
            'branch_id' => (int) $data['branch_id'],
            'court_id' => (int) $data['court_id'],
            'player_id' => !empty($data['player_id']) ? (int) $data['player_id'] : null,
            'name' => trim((string) $data['name']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $this->durationMinutes((string) $data['start_time'], (string) $data['end_time']),
            'repeat_type' => $data['repeat_type'],
            'repeat_interval' => max(1, (int) ($data['repeat_interval'] ?? 1)),
            'repeat_days' => $this->encodeDateList($this->decodeDateList($data['repeat_days'] ?? [])),
            'exclude_dates' => $this->encodeDateList($this->decodeDateList($data['exclude_dates'] ?? [])),
            'status' => 'active',
            'total_occurrences' => count($dates),
            'completed_occurrences' => 0,
            'next_occurrence' => $dates[0],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        if (!$id) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Không thể tạo lịch định kỳ.'];
        }
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể commit lịch định kỳ.'];
        }

        $this->audit('created', (int) $id, $tenantId, ['occurrences' => count($dates)]);
        return ['success' => true, 'id' => (int) $id, 'occurrences' => count($dates), 'next_occurrence' => $dates[0]];
    }

    public function changeStatus(int $id, string $status, int $tenantId, ?int $userId = null): bool
    {
        if (!in_array($status, ['active', 'paused', 'cancelled'], true) || $tenantId <= 0) {
            return false;
        }
        $db = \Config\Database::connect();
        $db->transStart();
        $template = $this->templateModel->findForUpdate($id, $tenantId);
        if (!$template || $template->status === 'completed') {
            $db->transRollback();
            return false;
        }
        $ok = $this->templateModel->update($id, ['status' => $status, 'updated_by' => $userId]);
        $db->transComplete();
        if (!$ok || !$db->transStatus()) {
            return false;
        }
        $this->audit('status_changed', $id, $tenantId, ['status' => $status]);
        return true;
    }

    /** Generate at most one occurrence per locked template per call. */
    public function processDue(int $tenantId, int $limit = 20): array
    {
        $results = [];
        foreach ($this->templateModel->getDueOccurrences($tenantId, $limit) as $candidate) {
            $results[] = $this->processOne((int) $candidate->id, $tenantId);
        }
        return $results;
    }

    /** Pure calendar expansion, kept public for deterministic unit tests. */
    public function buildOccurrenceDates(string $startDate, string $endDate, string $repeatType, int $interval = 1, array $repeatDays = [], array $excludeDates = []): array
    {
        $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $startDate);
        $end = \DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);
        if (!$start || !$end || $start > $end || !in_array($repeatType, ['daily', 'weekly', 'biweekly', 'monthly', 'custom'], true)) {
            return [];
        }
        $interval = max(1, $interval);
        $excluded = array_fill_keys(array_map('strval', $excludeDates), true);
        $dates = [];
        for ($date = $start, $guard = 0; $date <= $end && $guard < 2000; $guard++) {
            $dateString = $date->format('Y-m-d');
            $include = false;
            if ($repeatType === 'daily') {
                $include = $start->diff($date)->days % $interval === 0;
            } elseif ($repeatType === 'monthly') {
                $difference = $start->diff($date);
                $months = $difference->m + ($difference->y * 12);
                $include = $start->format('d') === $date->format('d') && $months % $interval === 0;
            } else {
                $week = intdiv($start->diff($date)->days, 7);
                $allowedDays = $repeatType === 'custom' && $repeatDays ? $repeatDays : [(int) $start->format('w')];
                $step = $repeatType === 'biweekly' ? 2 : $interval;
                $include = in_array((int) $date->format('w'), array_map('intval', $allowedDays), true) && $week % $step === 0;
            }
            if ($include && !isset($excluded[$dateString])) {
                $dates[] = $dateString;
            }
            $date = $date->modify('+1 day');
        }
        return $dates;
    }

    private function processOne(int $id, int $tenantId): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $template = $this->templateModel->findForUpdate($id, $tenantId);
        if (!$template || $template->status !== 'active' || !$template->next_occurrence) {
            $db->transRollback();
            return ['id' => $id, 'success' => false, 'message' => 'Template không còn active.'];
        }
        if ((int) $template->total_occurrences > 0 && (int) $template->completed_occurrences >= (int) $template->total_occurrences) {
            $this->templateModel->update($id, ['status' => 'completed']);
            $db->transComplete();
            return ['id' => $id, 'success' => true, 'status' => 'completed'];
        }

        $player = $template->player_id ? $this->playerModel->findForTenant((int) $template->player_id, $tenantId) : null;
        $booking = $this->bookingService->createBooking([
            'tenant_id' => $tenantId,
            'branch_id' => (int) $template->branch_id,
            'player_id' => $player?->id,
            'customer_name' => $player?->full_name ?: $template->name,
            'customer_phone' => $player?->phone ?: '0000000000',
            'customer_email' => $player?->email,
            'booking_date' => $template->next_occurrence,
            'start_time' => $template->start_time,
            'end_time' => $template->end_time,
            'source' => 'admin',
            'status' => 'pending',
            'is_recurring' => 1,
            'recurring_pattern' => json_encode(['template_id' => $id], JSON_UNESCAPED_UNICODE),
            'created_by' => $template->created_by,
            'items' => [[
                'court_id' => (int) $template->court_id,
                'start_time' => $template->start_time,
                'end_time' => $template->end_time,
            ]],
        ]);
        if (empty($booking['success'])) {
            $db->transRollback();
            return ['id' => $id, 'success' => false, 'message' => $booking['message'] ?? 'Occurrence tạo thất bại.'];
        }

        $completed = (int) $template->completed_occurrences + 1;
        $next = $this->nextOccurrence($template);
        $status = !$next || ((int) $template->total_occurrences > 0 && $completed >= (int) $template->total_occurrences) ? 'completed' : 'active';
        $this->templateModel->update($id, [
            'completed_occurrences' => $completed,
            'next_occurrence' => $next,
            'status' => $status,
        ]);
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['id' => $id, 'success' => false, 'message' => 'Không thể commit occurrence.'];
        }
        return ['id' => $id, 'success' => true, 'booking_id' => $booking['booking']->id ?? null, 'next_occurrence' => $next, 'status' => $status];
    }

    private function validateTemplate(array $data, int $tenantId): true|string
    {
        foreach (['branch_id', 'court_id', 'name', 'start_date', 'end_date', 'start_time', 'end_time', 'repeat_type'] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                return "Thiếu trường {$field}.";
            }
        }
        $branch = $this->branchModel->findForTenant((int) $data['branch_id'], $tenantId);
        $court = $this->courtModel->findForTenant((int) $data['court_id'], $tenantId);
        if (!$branch || !$court || (int) $court->branch_id !== (int) $branch->id || $court->status === 'inactive') {
            return 'Chi nhánh hoặc sân không thuộc tenant hiện tại.';
        }
        if (!empty($data['player_id']) && !$this->playerModel->findForTenant((int) $data['player_id'], $tenantId)) {
            return 'Người chơi không thuộc tenant hiện tại.';
        }
        if (!$this->validDate((string) $data['start_date']) || !$this->validDate((string) $data['end_date']) || $data['start_date'] > $data['end_date']) {
            return 'Khoảng ngày không hợp lệ.';
        }
        if (!$this->validTime((string) $data['start_time']) || !$this->validTime((string) $data['end_time']) || $data['start_time'] >= $data['end_time']) {
            return 'Khung giờ không hợp lệ.';
        }
        if (!in_array($data['repeat_type'], ['daily', 'weekly', 'biweekly', 'monthly', 'custom'], true)) {
            return 'Kiểu lặp không hợp lệ.';
        }
        $repeatDays = $this->decodeDateList($data['repeat_days'] ?? []);
        if ($data['repeat_type'] === 'custom' && !$repeatDays) {
            return 'Lịch tùy chọn phải có ít nhất một ngày trong tuần.';
        }
        foreach ($repeatDays as $day) {
            if (!is_numeric($day) || (int) $day < 0 || (int) $day > 6) {
                return 'Ngày trong tuần không hợp lệ.';
            }
        }
        foreach ($this->decodeDateList($data['exclude_dates'] ?? []) as $date) {
            if (!$this->validDate((string) $date)) {
                return 'Ngày loại trừ không hợp lệ.';
            }
        }
        return true;
    }

    private function nextOccurrence(object $template): ?string
    {
        $dates = $this->buildOccurrenceDates(
            (string) $template->start_date,
            (string) $template->end_date,
            (string) $template->repeat_type,
            (int) $template->repeat_interval,
            $this->decodeDateList($template->repeat_days),
            $this->decodeDateList($template->exclude_dates)
        );
        foreach ($dates as $date) {
            if ($date > (string) $template->next_occurrence) {
                return $date;
            }
        }
        return null;
    }

    private function durationMinutes(string $start, string $end): int
    {
        return (int) ((strtotime($end) - strtotime($start)) / 60);
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);
        return (bool) ($parsed && $parsed->format('Y-m-d') === $date);
    }

    private function validTime(string $time): bool
    {
        return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time);
    }

    private function decodeDateList(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true) ?: array_filter(array_map('trim', explode(',', $value)));
        }
        return is_array($value) ? array_values($value) : [];
    }

    private function encodeDateList(array $value): ?string
    {
        return $value ? json_encode(array_values($value), JSON_UNESCAPED_UNICODE) : null;
    }

    private function audit(string $action, int $id, int $tenantId, array $data): void
    {
        if (function_exists('log_audit')) {
            log_audit(['tenant_id' => $tenantId, 'table' => 'booking_recurring_templates', 'record_id' => $id, 'action' => $action, 'data' => $data]);
        }
    }
}
