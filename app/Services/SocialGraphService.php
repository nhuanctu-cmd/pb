<?php

namespace App\Services;

use App\Models\ClubModel;
use App\Models\CourtModel;
use App\Models\OpenPlaySessionModel;
use App\Models\PlayerFavoriteModel;
use App\Models\PlayerFollowModel;
use App\Models\PlayerModel;

class SocialGraphService
{
    private PlayerFollowModel $followModel;
    private PlayerFavoriteModel $favoriteModel;
    private PlayerModel $playerModel;

    public function __construct()
    {
        $this->followModel = new PlayerFollowModel();
        $this->favoriteModel = new PlayerFavoriteModel();
        $this->playerModel = new PlayerModel();
    }

    public function follow(int $followerId, int $followingId, int $tenantId, ?int $userId = null): array
    {
        if ($followerId === $followingId) {
            return ['success' => false, 'message' => 'Không thể tự follow chính mình.'];
        }
        if (!$this->playerModel->findForTenant($followerId, $tenantId) || !$this->playerModel->findForTenant($followingId, $tenantId)) {
            return ['success' => false, 'message' => 'Người chơi không thuộc tenant.'];
        }
        $existing = $this->followModel->findRelation($tenantId, $followerId, $followingId);
        if ($existing && $existing->status === 'active') {
            return ['success' => true, 'duplicate' => true, 'id' => (int) $existing->id];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        $id = $existing
            ? ($this->followModel->update((int) $existing->id, ['status' => 'active']) ? (int) $existing->id : false)
            : $this->followModel->insert(['tenant_id' => $tenantId, 'follower_player_id' => $followerId, 'following_player_id' => $followingId, 'status' => 'active']);
        $db->transComplete();
        if (!$id || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể follow người chơi.'];
        }
        $this->audit('followed', $id, $tenantId, ['follower_id' => $followerId, 'following_id' => $followingId, 'user_id' => $userId]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã follow người chơi.'];
    }

    public function unfollow(int $followerId, int $followingId, int $tenantId, ?int $userId = null): array
    {
        $existing = $this->followModel->findRelation($tenantId, $followerId, $followingId);
        if (!$existing) {
            return ['success' => true, 'duplicate' => true, 'message' => 'Quan hệ follow không tồn tại.'];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        $ok = $this->followModel->update((int) $existing->id, ['status' => 'blocked']);
        $db->transComplete();
        if (!$ok || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể unfollow.'];
        }
        $this->audit('unfollowed', (int) $existing->id, $tenantId, ['follower_id' => $followerId, 'following_id' => $followingId, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã unfollow.'];
    }

    public function favorite(int $playerId, string $type, int $entityId, int $tenantId, ?int $userId = null): array
    {
        if (!in_array($type, ['club', 'court', 'open_play'], true) || !$this->playerModel->findForTenant($playerId, $tenantId) || !$this->targetExists($type, $entityId, $tenantId)) {
            return ['success' => false, 'message' => 'Đối tượng yêu thích không hợp lệ.'];
        }
        $existing = $this->favoriteModel->findFavorite($tenantId, $playerId, $type, $entityId);
        if ($existing) {
            return ['success' => true, 'duplicate' => true, 'id' => (int) $existing->id];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        $id = $this->favoriteModel->insert(['tenant_id' => $tenantId, 'player_id' => $playerId, 'entity_type' => $type, 'entity_id' => $entityId]);
        $db->transComplete();
        if (!$id || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể lưu yêu thích.'];
        }
        $this->audit('favorited', (int) $id, $tenantId, ['player_id' => $playerId, 'entity_type' => $type, 'entity_id' => $entityId, 'user_id' => $userId]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã thêm vào yêu thích.'];
    }

    public function unfavorite(int $playerId, string $type, int $entityId, int $tenantId): array
    {
        $existing = $this->favoriteModel->findFavorite($tenantId, $playerId, $type, $entityId);
        if (!$existing) {
            return ['success' => true, 'duplicate' => true, 'message' => 'Mục yêu thích không tồn tại.'];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        // Favorites are a current-state relation; hard-delete on unfavorite so
        // the tenant-scoped unique key can be reused safely.
        $ok = $this->favoriteModel->delete((int) $existing->id, true);
        $db->transComplete();
        if (!$ok || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể xóa yêu thích.'];
        }
        $this->audit('unfavorited', (int) $existing->id, $tenantId, ['player_id' => $playerId, 'entity_type' => $type, 'entity_id' => $entityId]);
        return ['success' => true, 'message' => 'Đã xóa yêu thích.'];
    }

    public function following(int $tenantId, int $playerId): array
    {
        return $this->followModel->getFollowing($tenantId, $playerId);
    }

    public function favorites(int $tenantId, int $playerId): array
    {
        return $this->favoriteModel->getByPlayer($tenantId, $playerId);
    }

    public static function favoriteKey(string $type, int $id): string
    {
        return strtolower(trim($type)) . ':' . max(0, $id);
    }

    private function targetExists(string $type, int $id, int $tenantId): bool
    {
        return match ($type) {
            'club' => (bool) (new ClubModel())->findForTenant($id, $tenantId),
            'court' => (bool) (new CourtModel())->findForTenant($id, $tenantId),
            'open_play' => (bool) (new OpenPlaySessionModel())->findForTenant($id, $tenantId),
            default => false,
        };
    }

    private function audit(string $action, int $id, int $tenantId, array $data): void
    {
        if (function_exists('log_audit')) {
            log_audit(['action' => 'social_graph_' . $action, 'entity_type' => 'social_graph', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => $data]);
        }
    }
}
