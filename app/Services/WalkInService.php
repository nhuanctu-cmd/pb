<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;
use App\Models\WalkInSessionModel;

class WalkInService
{
    private WalkInSessionModel $sessionModel;
    private BranchModel $branchModel;
    private CourtModel $courtModel;
    private PlayerModel $playerModel;
    private BookingService $bookingService;

    public function __construct()
    {
        $this->sessionModel = new WalkInSessionModel();
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
        $this->playerModel = new PlayerModel();
        $this->bookingService = service('bookingService');
    }

    public function list(int $tenantId, array $filters = []): array
    {
        return $this->sessionModel->getByTenant($tenantId, $filters);
    }

    public function create(array $data, int $tenantId, ?int $userId = null): array
    {
        $error = $this->validate($data, $tenantId);
        if ($error !== true) {
            return ['success' => false, 'message' => $error];
        }

        $key = self::normalizeSessionKey($data['session_key'] ?? '');
        if ($key !== '') {
            $existing = $this->sessionModel->findBySessionKey($key, $tenantId);
            if ($existing) {
                return ['success' => true, 'duplicate' => true, 'id' => (int) $existing->id, 'session' => $existing];
            }
        }

        $playerId = !empty($data['player_id']) ? (int) $data['player_id'] : null;
        $player = $playerId ? $this->playerModel->findForTenant($playerId, $tenantId) : null;
        $name = trim((string) ($data['customer_name'] ?? '')) ?: (string) ($player->full_name ?? '');
        $phone = trim((string) ($data['customer_phone'] ?? '')) ?: (string) ($player->phone ?? '');
        $email = trim((string) ($data['customer_email'] ?? '')) ?: ($player->email ?? null);

        $result = $this->bookingService->createBooking([
            'tenant_id' => $tenantId,
            'branch_id' => (int) $data['branch_id'],
            'player_id' => $playerId,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_email' => $email,
            'booking_date' => $data['booking_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'source' => 'phone',
            'status' => 'reserved',
            'note' => trim('[WALK-IN] ' . (string) ($data['note'] ?? '')),
            'created_by' => $userId,
            'items' => [[
                'court_id' => (int) $data['court_id'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]],
        ]);
        if (empty($result['success'])) {
            return $result;
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $id = $this->sessionModel->insert([
            'tenant_id' => $tenantId,
            'booking_id' => (int) $result['booking']->id,
            'branch_id' => (int) $data['branch_id'],
            'player_id' => $playerId,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'customer_email' => $email,
            'session_key' => $key ?: null,
            'status' => 'open',
            'note' => $data['note'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        $db->transComplete();
        if (!$id || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể tạo phiên walk-in.'];
        }

        $this->audit('created', (int) $id, $tenantId, ['booking_id' => (int) $result['booking']->id]);
        return ['success' => true, 'id' => (int) $id, 'session' => $this->sessionModel->findForTenant((int) $id, $tenantId), 'booking' => $result['booking']];
    }

    public function checkIn(int $id, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $session = $this->sessionModel->findForUpdate($id, $tenantId);
        if (!$session) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Phiên walk-in không tồn tại.'];
        }
        if ($session->status === 'checked_in') {
            $db->transComplete();
            return ['success' => true, 'duplicate' => true, 'message' => 'Khách đã check-in.'];
        }
        if ($session->status !== 'open') {
            $db->transRollback();
            return ['success' => false, 'message' => 'Phiên walk-in không còn có thể check-in.'];
        }
        $result = $this->bookingService->checkIn((int) $session->booking_id, $userId, $tenantId);
        if (empty($result['success'])) {
            $db->transRollback();
            return $result;
        }
        $this->sessionModel->update($id, ['status' => 'checked_in', 'checked_in_at' => date('Y-m-d H:i:s'), 'updated_by' => $userId]);
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể cập nhật phiên walk-in.'];
        }
        $this->audit('checked_in', $id, $tenantId, ['booking_id' => (int) $session->booking_id]);
        return ['success' => true, 'message' => 'Walk-in đã check-in.', 'session' => $this->sessionModel->findForTenant($id, $tenantId)];
    }

    public function checkout(int $id, int $tenantId, ?int $userId = null): array
    {
        $session = $this->sessionModel->findForTenant($id, $tenantId);
        if (!$session || $session->status !== 'checked_in') {
            return ['success' => false, 'message' => 'Chỉ phiên đã check-in mới được checkout.'];
        }
        $result = $this->bookingService->markCompleted((int) $session->booking_id, $userId, $tenantId);
        if (empty($result['success'])) {
            return $result;
        }
        $this->sessionModel->update($id, ['status' => 'completed', 'checked_out_at' => date('Y-m-d H:i:s'), 'updated_by' => $userId]);
        $this->audit('completed', $id, $tenantId, ['booking_id' => (int) $session->booking_id]);
        return ['success' => true, 'message' => 'Walk-in đã checkout.', 'session' => $this->sessionModel->findForTenant($id, $tenantId)];
    }

    public function cancel(int $id, int $tenantId, ?int $userId = null): array
    {
        $session = $this->sessionModel->findForTenant($id, $tenantId);
        if (!$session || in_array($session->status, ['completed', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'Phiên walk-in không thể hủy.'];
        }
        $result = $this->bookingService->cancelBooking((int) $session->booking_id, 'Hủy walk-in', $userId, $tenantId);
        if (empty($result['success'])) {
            return $result;
        }
        $this->sessionModel->update($id, ['status' => 'cancelled', 'updated_by' => $userId]);
        $this->audit('cancelled', $id, $tenantId, ['booking_id' => (int) $session->booking_id]);
        return ['success' => true, 'message' => 'Đã hủy phiên walk-in.'];
    }

    public static function normalizeSessionKey(?string $key): string
    {
        return substr(trim((string) $key), 0, 100);
    }

    public static function isValidTimeRange(string $start, string $end): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $start) === 1
            && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $end) === 1
            && $start < $end;
    }

    private function validate(array $data, int $tenantId): true|string
    {
        if (!$tenantId || !$this->branchModel->findForTenant((int) ($data['branch_id'] ?? 0), $tenantId)) {
            return 'Chi nhánh không hợp lệ.';
        }
        $court = $this->courtModel->find((int) ($data['court_id'] ?? 0));
        if (!$court || (int) $court->tenant_id !== $tenantId || (int) $court->branch_id !== (int) $data['branch_id']) {
            return 'Sân không thuộc chi nhánh hoặc tenant.';
        }
        if (!empty($data['player_id']) && !$this->playerModel->findForTenant((int) $data['player_id'], $tenantId)) {
            return 'Người chơi không thuộc tenant.';
        }
        if (empty($data['booking_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['booking_date'])) {
            return 'Ngày đặt không hợp lệ.';
        }
        if (!self::isValidTimeRange((string) ($data['start_time'] ?? ''), (string) ($data['end_time'] ?? ''))) {
            return 'Khung giờ không hợp lệ.';
        }
        if (empty($data['customer_name']) && empty($data['player_id'])) {
            return 'Cần tên khách hoặc người chơi.';
        }
        if (empty($data['customer_phone']) && empty($data['player_id'])) {
            return 'Cần số điện thoại hoặc người chơi.';
        }
        return true;
    }

    private function audit(string $action, int $id, int $tenantId, array $data = []): void
    {
        if (function_exists('log_audit')) {
            log_audit(['action' => 'walk_in_' . $action, 'entity_type' => 'walk_in_session', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => $data]);
        }
    }
}
