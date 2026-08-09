<?php

namespace App\Services;

use App\Models\CommunityCommentModel;
use App\Models\CommunityPostModel;
use App\Models\CommunityReactionModel;
use App\Models\PlayerModel;

class CommunityService
{
    private CommunityPostModel $postModel;
    private CommunityCommentModel $commentModel;
    private CommunityReactionModel $reactionModel;
    private PlayerModel $playerModel;

    public function __construct()
    {
        $this->postModel = new CommunityPostModel();
        $this->commentModel = new CommunityCommentModel();
        $this->reactionModel = new CommunityReactionModel();
        $this->playerModel = new PlayerModel();
    }

    public function feed(int $tenantId, int $limit = 30): array { return $this->postModel->feed($tenantId, min(max($limit, 1), 100)); }
    public function comments(int $postId, int $tenantId): array { return $this->commentModel->byPost($postId, $tenantId); }

    public function createPost(int $playerId, array $data, int $tenantId, ?int $userId = null): array
    {
        $title = trim((string) ($data['title'] ?? '')); $body = trim((string) ($data['body'] ?? '')); $type = (string) ($data['type'] ?? 'tip');
        if (!$this->playerModel->findForTenant($playerId, $tenantId) || $title === '' || mb_strlen($title) > 180 || $body === '' || mb_strlen($body) > 5000 || !in_array($type, ['announcement', 'tip', 'event'], true)) return ['success' => false, 'message' => 'Bài viết không hợp lệ.'];
        $db = \Config\Database::connect(); $db->transStart();
        $id = $this->postModel->insert(['tenant_id' => $tenantId, 'player_id' => $playerId, 'type' => $type, 'title' => $title, 'body' => $body, 'status' => 'published']);
        $db->transComplete(); if (!$id || !$db->transStatus()) return ['success' => false, 'message' => 'Không thể tạo bài viết.'];
        $this->audit('post_created', (int) $id, $tenantId, ['player_id' => $playerId, 'user_id' => $userId]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã đăng bài viết.'];
    }

    public function comment(int $postId, int $playerId, string $body, int $tenantId, ?int $userId = null): array
    {
        $post = $this->postModel->findForTenant($postId, $tenantId); $body = trim($body);
        if (!$post || $post->status !== 'published' || !$this->playerModel->findForTenant($playerId, $tenantId) || $body === '' || mb_strlen($body) > 2000) return ['success' => false, 'message' => 'Bình luận không hợp lệ.'];
        $db = \Config\Database::connect(); $db->transStart();
        $id = $this->commentModel->insert(['tenant_id' => $tenantId, 'post_id' => $postId, 'player_id' => $playerId, 'body' => $body, 'status' => 'published']);
        $db->transComplete(); if (!$id || !$db->transStatus()) return ['success' => false, 'message' => 'Không thể bình luận.'];
        $this->audit('comment_created', (int) $id, $tenantId, ['post_id' => $postId, 'player_id' => $playerId, 'user_id' => $userId]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã thêm bình luận.'];
    }

    public function react(int $postId, int $playerId, string $reaction, int $tenantId, ?int $userId = null): array
    {
        if (!$this->postModel->findForTenant($postId, $tenantId) || !$this->playerModel->findForTenant($playerId, $tenantId) || !in_array($reaction, ['like', 'love', 'wow'], true)) return ['success' => false, 'message' => 'Reaction không hợp lệ.'];
        $db = \Config\Database::connect(); $db->transStart(); $existing = $this->reactionModel->findForPlayer($postId, $playerId, $tenantId);
        if ($existing) $ok = $this->reactionModel->update((int) $existing->id, ['reaction' => $reaction]); else $ok = $this->reactionModel->insert(['tenant_id' => $tenantId, 'post_id' => $postId, 'player_id' => $playerId, 'reaction' => $reaction]);
        $db->transComplete(); if (!$ok || !$db->transStatus()) return ['success' => false, 'message' => 'Không thể lưu reaction.'];
        $this->audit('reaction_saved', (int) ($existing->id ?? $ok), $tenantId, ['post_id' => $postId, 'player_id' => $playerId, 'reaction' => $reaction, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã lưu reaction.'];
    }

    public static function normalizeBody(string $body): string { return trim(preg_replace('/\s+/u', ' ', $body) ?? ''); }

    private function audit(string $action, int $id, int $tenantId, array $data): void { if (function_exists('log_audit')) log_audit(['action' => 'community_' . $action, 'entity_type' => 'community', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => $data]); }
}
