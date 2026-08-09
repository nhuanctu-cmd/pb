<?php

namespace App\Models;

use CodeIgniter\Model;

class CommunityCommentModel extends Model
{
    protected $table = 'community_comments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'post_id', 'player_id', 'body', 'status'];
    protected $useTimestamps = true;
    protected $deletedField = 'deleted_at';

    public function byPost(int $postId, int $tenantId): array
    {
        return $this->select('community_comments.*, players.full_name as player_name')->join('players', 'players.id = community_comments.player_id AND players.tenant_id = community_comments.tenant_id', 'left')->where('community_comments.post_id', $postId)->where('community_comments.tenant_id', $tenantId)->where('community_comments.status', 'published')->where('community_comments.deleted_at', null)->orderBy('community_comments.created_at', 'ASC')->findAll();
    }
}
