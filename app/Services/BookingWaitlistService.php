<?php

namespace App\Services;

use App\Models\BookingWaitlistModel;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class BookingWaitlistService
{
    private BookingWaitlistModel $waitlistModel;
    private BranchModel $branchModel;
    private CourtModel $courtModel;
    private PlayerModel $playerModel;
    private BookingService $bookingService;

    public function __construct()
    {
        $this->waitlistModel = new BookingWaitlistModel();
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
        $this->playerModel = new PlayerModel();
        $this->bookingService = service('bookingService');
    }

    public function list(int $tenantId, array $filters = []): array
    {
        return $tenantId > 0 ? $this->waitlistModel->getByTenant($tenantId, $filters) : [];
    }

    public function join(array $data, int $tenantId, ?int $userId = null): array
    {
        $error = $this->validate($data, $tenantId);
        if ($error !== true) {
            return ['success' => false, 'message' => $error];
        }

        $key = trim((string) ($data['idempotency_key'] ?? ''));
        if ($key !== '') {
            if (strlen($key) > 100) {
                return ['success' => false, 'message' => 'idempotency_key quá dài.'];
            }
            $existing = $this->waitlistModel->findExistingKey($key, $tenantId);
            if ($existing) {
                return ['success' => true, 'duplicate' => true, 'id' => (int) $existing->id, 'status' => $existing->status];
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();
        if ($key !== '' && $this->waitlistModel->findExistingKey($key, $tenantId)) {
            $db->transRollback();
            $existing = $this->waitlistModel->findExistingKey($key, $tenantId);
            return ['success' => true, 'duplicate' => true, 'id' => (int) $existing->id, 'status' => $existing->status];
        }
        $player = !empty($data['player_id']) ? $this->playerModel->findForTenant((int) $data['player_id'], $tenantId) : null;
        try {
            $id = $this->waitlistModel->insert([
            'tenant_id' => $tenantId,
            'branch_id' => (int) $data['branch_id'],
            'court_id' => !empty($data['court_id']) ? (int) $data['court_id'] : null,
            'player_id' => $player?->id,
            'customer_name' => trim((string) ($data['customer_name'] ?? $player?->full_name ?? '')),
            'customer_phone' => trim((string) ($data['customer_phone'] ?? $player?->phone ?? '')),
            'customer_email' => $data['customer_email'] ?? $player?->email,
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'duration_minutes' => $this->durationMinutes((string) $data['start_time'], (string) $data['end_time']),
            'priority' => max(1, min(10000, (int) ($data['priority'] ?? 100))),
            'status' => 'waiting',
            'idempotency_key' => $key !== '' ? $key : null,
            'created_by' => $userId,
            'updated_by' => $userId,
            ]);
        } catch (DatabaseException $exception) {
            $db->transRollback();
            $existing = $key !== '' ? $this->waitlistModel->findExistingKey($key, $tenantId) : null;
            if ($existing) {
                return ['success' => true, 'duplicate' => true, 'id' => (int) $existing->id, 'status' => $existing->status];
            }
            log_message('error', 'Waitlist insert failed: ' . $exception->getMessage());
            return ['success' => false, 'message' => 'Không thể tham gia danh sách chờ.'];
        }
        $db->transComplete();
        if (!$id || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể tham gia danh sách chờ.'];
        }
        $this->audit('created', (int) $id, $tenantId, ['slot' => $data['booking_date'] . ' ' . $data['start_time']]);
        return ['success' => true, 'id' => (int) $id, 'status' => 'waiting'];
    }

    public function notifyNext(int $tenantId, int $branchId, ?int $courtId, string $date, string $start, string $end): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $candidate = $this->waitlistModel->findNextForSlot($tenantId, $branchId, $courtId, $date, $start, $end);
        if (!$candidate) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Không có người chờ phù hợp.'];
        }
        $locked = $this->waitlistModel->findForUpdate((int) $candidate->id, $tenantId);
        if (!$locked || $locked->status !== 'waiting') {
            $db->transRollback();
            return ['success' => false, 'message' => 'Mục chờ đã được xử lý.'];
        }
        $expiresAt = date('Y-m-d H:i:s', time() + 15 * 60);
        $ok = $this->waitlistModel->update((int) $locked->id, [
            'status' => 'notified',
            'notified_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
            'updated_by' => user_id(),
        ]);
        $db->transComplete();
        if (!$ok || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể thông báo người chờ.'];
        }
        $this->audit('notified', (int) $locked->id, $tenantId, ['expires_at' => $expiresAt]);
        return ['success' => true, 'id' => (int) $locked->id, 'expires_at' => $expiresAt];
    }

    public function claim(int $id, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $entry = $this->waitlistModel->findForUpdate($id, $tenantId);
        if (!$entry) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Mục chờ không tồn tại.'];
        }
        if ($entry->status === 'claimed' && $entry->booking_id) {
            $db->transComplete();
            return ['success' => true, 'duplicate' => true, 'booking_id' => (int) $entry->booking_id];
        }
        if (!in_array($entry->status, ['waiting', 'notified'], true)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Mục chờ không còn có thể nhận.'];
        }
        if ($entry->status === 'notified' && $entry->expires_at && strtotime($entry->expires_at) < time()) {
            $this->waitlistModel->update($id, ['status' => 'expired', 'updated_by' => $userId]);
            $db->transComplete();
            return ['success' => false, 'message' => 'Thời hạn nhận sân đã hết.'];
        }

        $courtId = (int) ($entry->court_id ?? 0);
        if (!$courtId) {
            foreach ($this->courtModel->getAvailable((int) $entry->branch_id, (string) $entry->booking_date, (string) $entry->start_time, (string) $entry->end_time) as $court) {
                if ((int) $court->tenant_id === $tenantId) {
                    $courtId = (int) $court->id;
                    break;
                }
            }
        }
        if (!$courtId) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Không còn sân trống cho khung giờ này.'];
        }

        $booking = $this->bookingService->createBooking([
            'tenant_id' => $tenantId,
            'branch_id' => (int) $entry->branch_id,
            'player_id' => $entry->player_id,
            'customer_name' => $entry->customer_name,
            'customer_phone' => $entry->customer_phone,
            'customer_email' => $entry->customer_email,
            'booking_date' => $entry->booking_date,
            'start_time' => $entry->start_time,
            'end_time' => $entry->end_time,
            'source' => 'admin',
            'status' => 'pending',
            'created_by' => $userId,
            'items' => [[
                'court_id' => $courtId,
                'start_time' => $entry->start_time,
                'end_time' => $entry->end_time,
            ]],
        ]);
        if (empty($booking['success'])) {
            $db->transRollback();
            return ['success' => false, 'message' => $booking['message'] ?? 'Slot đã được nhận hoặc không còn trống.'];
        }
        $this->waitlistModel->update($id, [
            'status' => 'claimed',
            'booking_id' => $booking['booking']->id ?? null,
            'claimed_at' => date('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể commit booking từ waitlist.'];
        }
        $this->audit('claimed', $id, $tenantId, ['booking_id' => $booking['booking']->id ?? null]);
        return ['success' => true, 'booking_id' => $booking['booking']->id ?? null];
    }

    public function cancel(int $id, int $tenantId, ?int $userId = null): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $entry = $this->waitlistModel->findForUpdate($id, $tenantId);
        if (!$entry || in_array($entry->status, ['claimed', 'cancelled'], true)) {
            $db->transRollback();
            return false;
        }
        $ok = $this->waitlistModel->update($id, ['status' => 'cancelled', 'updated_by' => $userId]);
        $db->transComplete();
        if (!$ok || !$db->transStatus()) {
            return false;
        }
        $this->audit('cancelled', $id, $tenantId, []);
        return true;
    }

    public function expire(int $tenantId): int
    {
        $count = $this->waitlistModel->where('tenant_id', $tenantId)
            ->where('status', 'notified')->where('expires_at <', date('Y-m-d H:i:s'))
            ->set(['status' => 'expired', 'updated_by' => user_id()])->update();
        return $count ? $this->waitlistModel->db->affectedRows() : 0;
    }

    public function makeRequestKey(int $tenantId, ?int $playerId, ?int $courtId, string $date, string $start, string $end): string
    {
        return hash('sha256', implode('|', [$tenantId, $playerId ?: 0, $courtId ?: 0, $date, $start, $end]));
    }

    private function validate(array $data, int $tenantId): true|string
    {
        foreach (['branch_id', 'booking_date', 'start_time', 'end_time'] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                return "Thiếu trường {$field}.";
            }
        }
        $branch = $this->branchModel->findForTenant((int) $data['branch_id'], $tenantId);
        if (!$branch) {
            return 'Chi nhánh không thuộc tenant hiện tại.';
        }
        if (!empty($data['court_id'])) {
            $court = $this->courtModel->findForTenant((int) $data['court_id'], $tenantId);
            if (!$court || (int) $court->branch_id !== (int) $branch->id || $court->status === 'inactive') {
                return 'Sân không thuộc tenant hoặc chi nhánh hiện tại.';
            }
        }
        if (!empty($data['player_id']) && !$this->playerModel->findForTenant((int) $data['player_id'], $tenantId)) {
            return 'Người chơi không thuộc tenant hiện tại.';
        }
        if (!$this->validDate((string) $data['booking_date']) || !$this->validTime((string) $data['start_time']) || !$this->validTime((string) $data['end_time']) || $data['start_time'] >= $data['end_time']) {
            return 'Ngày hoặc khung giờ không hợp lệ.';
        }
        if (empty($data['player_id']) && (trim((string) ($data['customer_name'] ?? '')) === '' || trim((string) ($data['customer_phone'] ?? '')) === '')) {
            return 'Khách vãng lai cần tên và số điện thoại.';
        }
        return true;
    }

    private function durationMinutes(string $start, string $end): int
    {
        return (int) ((strtotime($end) - strtotime($start)) / 60);
    }

    private function validDate(string $value): bool
    {
        $date = \DateTime::createFromFormat('!Y-m-d', $value);
        return (bool) ($date && $date->format('Y-m-d') === $value);
    }

    private function validTime(string $value): bool
    {
        return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $value);
    }

    private function audit(string $action, int $id, int $tenantId, array $data): void
    {
        if (function_exists('log_audit')) {
            log_audit(['tenant_id' => $tenantId, 'table' => 'booking_waitlist', 'record_id' => $id, 'action' => $action, 'data' => $data]);
        }
    }
}
