<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\OpenPlaySessionModel;
use App\Models\OpenPlaySessionPlayerModel;
use App\Models\PlayerModel;

class OpenPlayService
{
    private OpenPlaySessionModel $sessionModel;
    private OpenPlaySessionPlayerModel $playerModel;
    private BranchModel $branchModel;
    private PlayerModel $profileModel;

    public function __construct()
    {
        $this->sessionModel = new OpenPlaySessionModel();
        $this->playerModel = new OpenPlaySessionPlayerModel();
        $this->branchModel = new BranchModel();
        $this->profileModel = new PlayerModel();
    }

    public function list(int $tenantId, array $filters = []): array
    {
        return $this->sessionModel->getByTenant($tenantId, $filters);
    }

    public function players(int $sessionId, int $tenantId): array
    {
        return $this->playerModel->getBySession($sessionId, $tenantId);
    }

    public function entryForPlayer(int $sessionId, int $playerId, int $tenantId): ?object
    {
        return $this->playerModel->findByPlayer($sessionId, $playerId, $tenantId);
    }

    public function create(array $data, int $tenantId, ?int $userId = null): array
    {
        $error = $this->validateSession($data, $tenantId);
        if ($error !== true) {
            return ['success' => false, 'message' => $error];
        }
        $id = $this->sessionModel->insert([
            'tenant_id' => $tenantId,
            'branch_id' => (int) $data['branch_id'],
            'host_player_id' => (int) $data['host_player_id'],
            'title' => trim((string) $data['title']),
            'session_date' => $data['session_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'capacity' => (int) $data['capacity'],
            'min_level' => $data['min_level'] ?? null,
            'max_level' => $data['max_level'] ?? null,
            'price_per_player' => round((float) ($data['price_per_player'] ?? 0), 2),
            'visibility' => in_array($data['visibility'] ?? 'public', ['public', 'private', 'club_only'], true) ? $data['visibility'] : 'public',
            'status' => 'open',
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        if (!$id) {
            return ['success' => false, 'message' => 'Không thể tạo Open Play.'];
        }
        $this->playerModel->insert(['tenant_id' => $tenantId, 'session_id' => (int) $id, 'player_id' => (int) $data['host_player_id'], 'status' => 'approved', 'requested_at' => date('Y-m-d H:i:s'), 'approved_at' => date('Y-m-d H:i:s'), 'created_by' => $userId, 'updated_by' => $userId]);
        $this->audit('created', (int) $id, $tenantId, ['host_player_id' => (int) $data['host_player_id']]);
        return ['success' => true, 'id' => (int) $id, 'session' => $this->sessionModel->findForTenant((int) $id, $tenantId)];
    }

    public function requestJoin(int $sessionId, int $playerId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $session = $this->sessionModel->findForUpdate($sessionId, $tenantId);
        if (!$session || !in_array($session->status, ['open', 'full'], true)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Open Play không còn nhận người.'];
        }
        if (!$this->profileModel->findForTenant($playerId, $tenantId)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Người chơi không thuộc tenant.'];
        }
        $existing = $this->playerModel->findByPlayer($sessionId, $playerId, $tenantId);
        if ($existing && in_array($existing->status, ['requested', 'approved', 'waitlisted'], true)) {
            $db->transComplete();
            return ['success' => true, 'duplicate' => true, 'id' => (int) $existing->id, 'status' => $existing->status];
        }
        $approved = $this->playerModel->approvedCount($sessionId, $tenantId);
        $status = $approved < (int) $session->capacity ? 'requested' : 'waitlisted';
        $payload = ['status' => $status, 'requested_at' => date('Y-m-d H:i:s'), 'approved_at' => null, 'created_by' => $userId, 'updated_by' => $userId];
        $id = $existing
            ? ($this->playerModel->update((int) $existing->id, $payload) ? (int) $existing->id : false)
            : $this->playerModel->insert(array_merge($payload, ['tenant_id' => $tenantId, 'session_id' => $sessionId, 'player_id' => $playerId]));
        $db->transComplete();
        if (!$id || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể gửi yêu cầu tham gia.'];
        }
        $this->audit('join_requested', $sessionId, $tenantId, ['player_id' => $playerId, 'status' => $status]);
        return ['success' => true, 'id' => (int) $id, 'status' => $status];
    }

    public function approve(int $entryId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $entry = $this->playerModel->findForUpdate($entryId, $tenantId);
        if (!$entry) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Yêu cầu không tồn tại.'];
        }
        $session = $this->sessionModel->findForUpdate((int) $entry->session_id, $tenantId);
        if (!$session || !in_array($session->status, ['open', 'full'], true)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Session không còn mở.'];
        }
        if ($entry->status === 'approved') {
            $db->transComplete();
            return ['success' => true, 'duplicate' => true];
        }
        if (!in_array($entry->status, ['requested', 'waitlisted'], true)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Yêu cầu không còn chờ duyệt.'];
        }
        if ($this->playerModel->approvedCount((int) $session->id, $tenantId) >= (int) $session->capacity) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Open Play đã đủ chỗ.'];
        }
        $this->playerModel->update((int) $entry->id, ['status' => 'approved', 'approved_at' => date('Y-m-d H:i:s'), 'updated_by' => $userId]);
        if ($this->playerModel->approvedCount((int) $session->id, $tenantId) >= (int) $session->capacity) {
            $this->sessionModel->update((int) $session->id, ['status' => 'full', 'updated_by' => $userId]);
        }
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể duyệt người chơi.'];
        }
        $this->audit('join_approved', (int) $session->id, $tenantId, ['entry_id' => $entryId]);
        return ['success' => true, 'message' => 'Đã duyệt người chơi.'];
    }

    public function leave(int $entryId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $entry = $this->playerModel->findForUpdate($entryId, $tenantId);
        if (!$entry || $entry->status === 'cancelled') {
            $db->transRollback();
            return ['success' => false, 'message' => 'Lượt tham gia không tồn tại.'];
        }
        $session = $this->sessionModel->findForUpdate((int) $entry->session_id, $tenantId);
        $this->playerModel->update($entryId, ['status' => 'cancelled', 'updated_by' => $userId]);
        if ($session && $session->status === 'full') {
            $this->sessionModel->update((int) $session->id, ['status' => 'open', 'updated_by' => $userId]);
        }
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể rời Open Play.'];
        }
        $this->audit('left', (int) $entry->session_id, $tenantId, ['entry_id' => $entryId]);
        return ['success' => true, 'message' => 'Đã rời Open Play.'];
    }

    public static function isValidTimeRange(string $start, string $end): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $start) === 1 && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $end) === 1 && $start < $end;
    }

    public static function canAdd(int $approved, int $capacity): bool
    {
        return $capacity >= 2 && $approved < $capacity;
    }

    private function validateSession(array $data, int $tenantId): true|string
    {
        if (!$tenantId || !$this->branchModel->findForTenant((int) ($data['branch_id'] ?? 0), $tenantId)) {
            return 'Chi nhánh không hợp lệ.';
        }
        if (!$this->profileModel->findForTenant((int) ($data['host_player_id'] ?? 0), $tenantId)) {
            return 'Người tổ chức không thuộc tenant.';
        }
        $capacity = (int) ($data['capacity'] ?? 0);
        if ($capacity < 2 || $capacity > 100) {
            return 'Sức chứa phải từ 2 đến 100 người.';
        }
        if (empty($data['title']) || strlen(trim((string) $data['title'])) > 255) {
            return 'Tên session không hợp lệ.';
        }
        if (empty($data['session_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['session_date'])) {
            return 'Ngày session không hợp lệ.';
        }
        if (!empty($data['min_level']) && strlen((string) $data['min_level']) > 30 || !empty($data['max_level']) && strlen((string) $data['max_level']) > 30) {
            return 'Khoảng level không hợp lệ.';
        }
        if (!self::isValidTimeRange((string) ($data['start_time'] ?? ''), (string) ($data['end_time'] ?? ''))) {
            return 'Khung giờ không hợp lệ.';
        }
        return true;
    }

    private function audit(string $action, int $sessionId, int $tenantId, array $data = []): void
    {
        if (function_exists('log_audit')) {
            log_audit(['action' => 'open_play_' . $action, 'entity_type' => 'open_play_session', 'entity_id' => $sessionId, 'tenant_id' => $tenantId, 'metadata' => $data]);
        }
    }
}
