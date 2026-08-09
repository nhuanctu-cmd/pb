<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\CoachAvailabilityModel;
use App\Models\CoachBlackoutModel;
use App\Models\CoachModel;
use App\Models\CoachingSessionModel;
use App\Models\CoachingSessionPlayerModel;
use App\Models\CoachingAttendanceModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;
use App\Models\InvoiceModel;
use App\Services\NotificationService;

class CoachingService
{
    private CoachModel $coachModel;
    private CoachAvailabilityModel $availabilityModel;
    private CoachBlackoutModel $blackoutModel;
    private CoachingSessionModel $sessionModel;
    private CoachingSessionPlayerModel $sessionPlayerModel;
    private BranchModel $branchModel;
    private CourtModel $courtModel;
    private PlayerModel $playerModel;
    private BookingService $bookingService;
    private CoachingAttendanceModel $attendanceModel;

    public function __construct()
    {
        $this->coachModel = new CoachModel();
        $this->availabilityModel = new CoachAvailabilityModel();
        $this->blackoutModel = new CoachBlackoutModel();
        $this->sessionModel = new CoachingSessionModel();
        $this->sessionPlayerModel = new CoachingSessionPlayerModel();
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
        $this->playerModel = new PlayerModel();
        $this->bookingService = service('bookingService');
        $this->attendanceModel = new CoachingAttendanceModel();
    }

    public function coaches(int $tenantId): array
    {
        return $this->coachModel->getByTenant($tenantId, ['status' => 'active']);
    }

    public function sessions(int $tenantId, array $filters = []): array
    {
        return $this->sessionModel->getByTenant($tenantId, $filters);
    }

    public function players(int $sessionId, int $tenantId): array
    {
        return $this->sessionPlayerModel->getBySession($sessionId, $tenantId);
    }

    public function attendance(int $sessionId, int $tenantId): array
    {
        return $this->attendanceModel->getBySession($sessionId, $tenantId);
    }

    public function entryForPlayer(int $sessionId, int $playerId, int $tenantId): ?object
    {
        return $this->sessionPlayerModel->findByPlayer($sessionId, $playerId, $tenantId);
    }

    public function createCoach(array $data, int $tenantId, ?int $userId = null): array
    {
        if (!$tenantId || !$this->branchModel->findForTenant((int) ($data['branch_id'] ?? 0), $tenantId) || trim((string) ($data['full_name'] ?? '')) === '') {
            return ['success' => false, 'message' => 'Coach hoặc chi nhánh không hợp lệ.'];
        }
        $id = $this->coachModel->insert(['tenant_id' => $tenantId, 'branch_id' => (int) $data['branch_id'], 'full_name' => trim((string) $data['full_name']), 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null, 'bio' => $data['bio'] ?? null, 'certifications' => $data['certifications'] ?? null, 'specialties' => $data['specialties'] ?? null, 'hourly_rate' => round((float) ($data['hourly_rate'] ?? 0), 2), 'status' => 'active', 'created_by' => $userId, 'updated_by' => $userId]);
        if (!$id) return ['success' => false, 'message' => 'Không thể tạo coach.'];
        $this->audit('coach_created', (int) $id, $tenantId, []);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã tạo coach.'];
    }

    public function addAvailability(array $data, int $tenantId): array
    {
        $coach = $this->coachModel->findForTenant((int) ($data['coach_id'] ?? 0), $tenantId);
        $day = (int) ($data['day_of_week'] ?? -1);
        $branchId = !empty($data['branch_id']) ? (int) $data['branch_id'] : null;
        if (!$coach || ($branchId && !$this->branchModel->findForTenant($branchId, $tenantId)) || $day < 0 || $day > 6 || !self::isValidTimeRange((string) ($data['start_time'] ?? ''), (string) ($data['end_time'] ?? ''))) {
            return ['success' => false, 'message' => 'Availability không hợp lệ.'];
        }
        $id = $this->availabilityModel->insert(['tenant_id' => $tenantId, 'coach_id' => (int) $coach->id, 'branch_id' => $branchId, 'day_of_week' => $day, 'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'status' => 'active']);
        if (!$id) return ['success' => false, 'message' => 'Availability bị trùng hoặc không thể lưu.'];
        $this->audit('availability_created', (int) $id, $tenantId, ['coach_id' => $coach->id]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã lưu availability.'];
    }

    public function addBlackout(array $data, int $tenantId, ?int $userId = null): array
    {
        $coach = $this->coachModel->findForTenant((int) ($data['coach_id'] ?? 0), $tenantId);
        if (!$coach || !self::isValidDateTimeRange((string) ($data['start_at'] ?? ''), (string) ($data['end_at'] ?? ''))) {
            return ['success' => false, 'message' => 'Blackout không hợp lệ.'];
        }
        $id = $this->blackoutModel->insert(['tenant_id' => $tenantId, 'coach_id' => (int) $coach->id, 'start_at' => $data['start_at'], 'end_at' => $data['end_at'], 'reason' => $data['reason'] ?? null, 'status' => 'active', 'created_by' => $userId]);
        if (!$id) return ['success' => false, 'message' => 'Không thể lưu blackout.'];
        $this->audit('blackout_created', (int) $id, $tenantId, ['coach_id' => $coach->id, 'user_id' => $userId]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã lưu blackout.'];
    }

    public function createSession(array $data, int $tenantId, ?int $userId = null): array
    {
        $error = $this->validateSession($data, $tenantId);
        if ($error !== true) return ['success' => false, 'message' => $error];
        $coach = $this->coachModel->findForTenant((int) $data['coach_id'], $tenantId);
        $courtId = !empty($data['court_id']) ? (int) $data['court_id'] : null;
        $bookingId = null;
        $db = \Config\Database::connect();
        $db->transStart();
        if ($courtId) {
            $booking = $this->bookingService->createBooking(['tenant_id' => $tenantId, 'branch_id' => (int) $data['branch_id'], 'customer_name' => 'Coaching: ' . $data['title'], 'customer_phone' => '0000000000', 'booking_date' => $data['session_date'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'source' => 'admin', 'status' => 'reserved', 'note' => 'Giữ sân cho coaching session', 'created_by' => $userId, 'items' => [['court_id' => $courtId, 'start_time' => $data['start_time'], 'end_time' => $data['end_time']]]]);
            if (empty($booking['success'])) {
                $db->transRollback();
                return $booking;
            }
            $bookingId = (int) $booking['booking']->id;
        }
        $id = $this->sessionModel->insert(['tenant_id' => $tenantId, 'branch_id' => (int) $data['branch_id'], 'coach_id' => (int) $coach->id, 'court_id' => $courtId, 'booking_id' => $bookingId, 'title' => trim((string) $data['title']), 'session_type' => $data['session_type'], 'session_date' => $data['session_date'], 'start_time' => $data['start_time'], 'end_time' => $data['end_time'], 'capacity' => (int) $data['capacity'], 'price_per_player' => round((float) ($data['price_per_player'] ?? 0), 2), 'status' => 'open', 'notes' => $data['notes'] ?? null, 'created_by' => $userId, 'updated_by' => $userId]);
        $db->transComplete();
        if (!$id || !$db->transStatus()) return ['success' => false, 'message' => 'Không thể tạo coaching session.'];
        $this->audit('session_created', (int) $id, $tenantId, ['booking_id' => $bookingId]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã tạo coaching session.'];
    }

    public function requestJoin(int $sessionId, int $playerId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $session = $this->sessionModel->findForUpdate($sessionId, $tenantId);
        if (!$session || !in_array($session->status, ['open', 'full'], true) || !$this->playerModel->findForTenant($playerId, $tenantId)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Session không nhận đăng ký hoặc player không hợp lệ.'];
        }
        $existing = $this->sessionPlayerModel->findByPlayer($sessionId, $playerId, $tenantId);
        if ($existing && in_array($existing->status, ['requested', 'approved', 'waitlisted'], true)) {
            $db->transComplete();
            return ['success' => true, 'duplicate' => true, 'status' => $existing->status];
        }
        $approved = $this->sessionPlayerModel->approvedCount($sessionId, $tenantId);
        $status = $approved < (int) $session->capacity ? 'requested' : 'waitlisted';
        $payload = ['status' => $status, 'requested_at' => date('Y-m-d H:i:s'), 'approved_at' => null, 'created_by' => $userId];
        $id = $existing ? ($this->sessionPlayerModel->update((int) $existing->id, $payload) ? (int) $existing->id : false) : $this->sessionPlayerModel->insert(array_merge($payload, ['tenant_id' => $tenantId, 'session_id' => $sessionId, 'player_id' => $playerId]));
        $db->transComplete();
        if (!$id || !$db->transStatus()) return ['success' => false, 'message' => 'Không thể đăng ký coaching.'];
        $this->audit('player_requested', $sessionId, $tenantId, ['player_id' => $playerId, 'status' => $status]);
        return ['success' => true, 'status' => $status, 'message' => $status === 'waitlisted' ? 'Đã vào danh sách chờ.' : 'Đã gửi yêu cầu tham gia.'];
    }

    public function approve(int $entryId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $entry = $this->sessionPlayerModel->findForUpdate($entryId, $tenantId);
        $session = $entry ? $this->sessionModel->findForUpdate((int) $entry->session_id, $tenantId) : null;
        if (!$entry || !$session || !in_array($entry->status, ['requested', 'waitlisted'], true) || $this->sessionPlayerModel->approvedCount((int) $session->id, $tenantId) >= (int) $session->capacity) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Không thể duyệt học viên hoặc session đã đủ chỗ.'];
        }
        $this->sessionPlayerModel->update((int) $entry->id, ['status' => 'approved', 'approved_at' => date('Y-m-d H:i:s')]);
        if ($this->sessionPlayerModel->approvedCount((int) $session->id, $tenantId) >= (int) $session->capacity) $this->sessionModel->update((int) $session->id, ['status' => 'full', 'updated_by' => $userId]);
        if ((float) $session->price_per_player > 0 && empty($entry->invoice_id)) {
            $invoiceService = new InvoiceService();
            $existingInvoices = $invoiceService->getInvoicesByRef('coaching_session_player', (int) $entry->id, $tenantId);
            $invoice = $existingInvoices[0] ?? $invoiceService->createInvoice($tenantId, (int) $session->branch_id, 'COACH-' . $tenantId . '-' . $entry->id, (float) $session->price_per_player, ['customer_type' => 'player', 'player_id' => (int) $entry->player_id, 'ref_type' => 'coaching_session_player', 'ref_id' => (int) $entry->id, 'note' => 'Coaching: ' . $session->title, 'created_by' => $userId]);
            if (!$invoice || empty($invoice->id)) { $db->transRollback(); return ['success' => false, 'message' => 'Không thể tạo hóa đơn coaching.']; }
            $this->sessionPlayerModel->update((int) $entry->id, ['invoice_id' => (int) $invoice->id]);
        }
        $db->transComplete();
        if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể duyệt học viên.'];
        $this->audit('player_approved', (int) $session->id, $tenantId, ['entry_id' => $entryId]);
        $player = $this->playerModel->findForTenant((int) $entry->player_id, $tenantId);
        if (!empty($player->user_id)) (new NotificationService())->notifyUser((int) $player->user_id, 'coaching_player_approved', ['full_name' => $player->full_name, 'session_title' => $session->title], $tenantId, '/player/coaching');
        return ['success' => true, 'message' => 'Đã duyệt học viên.'];
    }

    public function leave(int $entryId, int $playerId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $entry = $this->sessionPlayerModel->findForUpdate($entryId, $tenantId);
        $session = $entry ? $this->sessionModel->findForUpdate((int) $entry->session_id, $tenantId) : null;
        if (!$entry || !$session || (int) $entry->player_id !== $playerId || !in_array($entry->status, ['requested', 'approved', 'waitlisted'], true)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Không thể rời coaching session.'];
        }
        $this->sessionPlayerModel->update((int) $entry->id, ['status' => 'cancelled']);
        if ($session->status === 'full') {
            $this->sessionModel->update((int) $session->id, ['status' => 'open']);
        }
        $db->transComplete();
        if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể rời coaching session.'];
        $this->audit('player_left', (int) $session->id, $tenantId, ['entry_id' => $entryId, 'player_id' => $playerId, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã rời coaching session.'];
    }

    public function cancelSession(int $sessionId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $session = $this->sessionModel->findForUpdate($sessionId, $tenantId);
        if (!$session || in_array($session->status, ['cancelled', 'completed'], true)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Session không thể hủy.'];
        }
        if (!empty($session->booking_id)) {
            $booking = $this->bookingService->cancelBooking((int) $session->booking_id, 'Coaching session cancelled', $userId, $tenantId);
            if (empty($booking['success'])) {
                $db->transRollback();
                return $booking;
            }
        }
        $this->sessionModel->update($sessionId, ['status' => 'cancelled', 'updated_by' => $userId]);
        $this->sessionPlayerModel->where('session_id', $sessionId)->where('tenant_id', $tenantId)->whereIn('status', ['requested', 'approved', 'waitlisted'])->set(['status' => 'cancelled'])->update();
        $db->transComplete();
        if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể hủy session.'];
        $this->audit('session_cancelled', $sessionId, $tenantId, ['user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã hủy coaching session.'];
    }

    public function payInvoice(int $entryId, int $playerId, int $tenantId, ?int $userId = null): array
    {
        $entry = $this->sessionPlayerModel->where('id', $entryId)->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('deleted_at', null)->first();
        if (!$entry || empty($entry->invoice_id)) return ['success' => false, 'message' => 'Chưa có hóa đơn coaching.'];
        $invoice = (new InvoiceModel())->findForTenant((int) $entry->invoice_id, $tenantId);
        if (!$invoice || in_array($invoice->status, ['paid', 'cancelled', 'refunded'], true)) return ['success' => false, 'message' => 'Hóa đơn không thể thanh toán.'];
        $remaining = round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2);
        if ($remaining <= 0) return ['success' => true, 'message' => 'Hóa đơn đã thanh toán.'];
        try { $result = (new PaymentService())->payByWallet((int) $invoice->id, $remaining, $playerId, ['created_by' => $userId, 'idempotency_key' => 'coaching-invoice-' . $entryId], $tenantId); } catch (\Throwable $exception) { return ['success' => false, 'message' => $exception->getMessage()]; }
        if (!empty($result['success'])) { $this->audit('invoice_paid', $entryId, $tenantId, ['player_id' => $playerId, 'invoice_id' => $invoice->id]); return ['success' => true, 'message' => 'Đã thanh toán coaching bằng ví.']; }
        return ['success' => false, 'message' => $result['message'] ?? 'Không thể thanh toán coaching.'];
    }

    public function markAttendance(int $entryId, string $status, int $tenantId, ?int $userId = null): array
    {
        if (!in_array($status, ['registered', 'attended', 'no_show', 'cancelled'], true)) return ['success' => false, 'message' => 'Trạng thái attendance không hợp lệ.'];
        $db = \Config\Database::connect(); $db->transStart();
        $entry = $this->sessionPlayerModel->findForUpdate($entryId, $tenantId);
        $session = $entry ? $this->sessionModel->findForUpdate((int) $entry->session_id, $tenantId) : null;
        if (!$entry || !$session || $entry->status !== 'approved') { $db->transRollback(); return ['success' => false, 'message' => 'Chỉ ghi attendance cho học viên đã duyệt.']; }
        $attendance = $this->attendanceModel->findForEntry((int) $session->id, (int) $entry->player_id, $tenantId);
        $payload = ['tenant_id' => $tenantId, 'session_id' => (int) $session->id, 'player_id' => (int) $entry->player_id, 'status' => $status, 'checkin_at' => $status === 'attended' ? date('Y-m-d H:i:s') : ($attendance->checkin_at ?? null)];
        $ok = $attendance ? $this->attendanceModel->update((int) $attendance->id, $payload) : $this->attendanceModel->insert($payload);
        $db->transComplete(); if (!$ok || !$db->transStatus()) return ['success' => false, 'message' => 'Không thể lưu attendance.'];
        $this->audit('attendance_' . $status, (int) $session->id, $tenantId, ['entry_id' => $entryId, 'player_id' => $entry->player_id, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã cập nhật attendance.'];
    }

    public static function isValidTimeRange(string $start, string $end): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $start) === 1 && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $end) === 1 && $start < $end;
    }

    public static function isValidDateTimeRange(string $start, string $end): bool
    {
        $a = strtotime($start); $b = strtotime($end);
        return $a !== false && $b !== false && $a < $b;
    }

    private function validateSession(array $data, int $tenantId): true|string
    {
        $branch = $this->branchModel->findForTenant((int) ($data['branch_id'] ?? 0), $tenantId);
        $coach = $this->coachModel->findForTenant((int) ($data['coach_id'] ?? 0), $tenantId);
        if (!$branch || !$coach || (int) $coach->branch_id !== (int) $data['branch_id']) return 'Coach hoặc chi nhánh không hợp lệ.';
        if (empty($data['title']) || !in_array($data['session_type'] ?? '', ['private', 'semi_private', 'group', 'clinic'], true)) return 'Thông tin session không hợp lệ.';
        $capacity = (int) ($data['capacity'] ?? 0);
        if ($capacity < 1 || $capacity > 50 || !self::isValidTimeRange((string) ($data['start_time'] ?? ''), (string) ($data['end_time'] ?? ''))) return 'Capacity hoặc khung giờ không hợp lệ.';
        $date = (string) ($data['session_date'] ?? '');
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < date('Y-m-d')) return 'Ngày session phải hợp lệ và không ở quá khứ.';
        $day = (int) date('w', strtotime($date));
        $available = $this->availabilityModel->where('tenant_id', $tenantId)->where('coach_id', $coach->id)->where('day_of_week', $day)->where('status', 'active')->where('deleted_at', null)->groupStart()->where('branch_id IS NULL', null, false)->orWhere('branch_id', (int) $data['branch_id'])->groupEnd()->where('start_time <=', $data['start_time'])->where('end_time >=', $data['end_time'])->countAllResults() > 0;
        if (!$available) return 'Coach không có availability trong khung giờ này.';
        $startAt = $date . ' ' . $data['start_time']; $endAt = $date . ' ' . $data['end_time'];
        if ($this->blackoutModel->where('tenant_id', $tenantId)->where('coach_id', $coach->id)->where('status', 'active')->where('deleted_at', null)->where('start_at <', $endAt)->where('end_at >', $startAt)->countAllResults() > 0) return 'Coach đang có blackout.';
        if ($this->sessionModel->where('tenant_id', $tenantId)->where('coach_id', $coach->id)->where('session_date', $date)->whereNotIn('status', ['cancelled', 'completed'])->where('start_time <', $data['end_time'])->where('end_time >', $data['start_time'])->where('deleted_at', null)->countAllResults() > 0) return 'Coach đã có session trùng lịch.';
        if (!empty($data['court_id'])) {
            $court = $this->courtModel->findForTenant((int) $data['court_id'], $tenantId);
            if (!$court || (int) $court->branch_id !== (int) $data['branch_id']) return 'Court không thuộc chi nhánh.';
        }
        return true;
    }

    private function audit(string $action, int $id, int $tenantId, array $data): void
    {
        if (function_exists('log_audit')) log_audit(['action' => 'coaching_' . $action, 'entity_type' => 'coaching', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => $data]);
    }
}
