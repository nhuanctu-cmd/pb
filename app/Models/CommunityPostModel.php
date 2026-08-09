<?php

namespace App\Models;

use CodeIgniter\Model;

class CommunityPostModel extends Model
{
    protected $table = 'community_posts';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'player_id', 'type', 'title', 'body', 'status'];
    protected $useTimestamps = true;
    protected $deletedField = 'deleted_at';

    public function feed(int $tenantId, int $limit = 30): array
    {
        return $this->select('community_posts.*, players.full_name as player_name, COUNT(DISTINCT community_comments.id) as comments_count, COUNT(DISTINCT community_reactions.id) as reactions_count')
            ->join('players', 'players.id = community_posts.player_id AND players.tenant_id = community_posts.tenant_id', 'left')
            ->join('community_comments', 'community_comments.post_id = community_posts.id AND community_comments.tenant_id = community_posts.tenant_id AND community_comments.status = \'published\' AND community_comments.deleted_at IS NULL', 'left')
            ->join('community_reactions', 'community_reactions.post_id = community_posts.id AND community_reactions.tenant_id = community_posts.tenant_id', 'left')
            ->where('community_posts.tenant_id', $tenantId)->where('community_posts.status', 'published')->where('community_posts.deleted_at', null)
            ->groupBy('community_posts.id')->orderBy('community_posts.created_at', 'DESC')->findAll($limit);
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }
}
