<?php

namespace App\Models;

use CodeIgniter\Model;

class CommunityReactionModel extends Model
{
    protected $table = 'community_reactions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['tenant_id', 'post_id', 'player_id', 'reaction'];
    protected $useTimestamps = false;

    public function findForPlayer(int $postId, int $playerId, int $tenantId): ?object
    {
        return $this->where('post_id', $postId)->where('player_id', $playerId)->where('tenant_id', $tenantId)->first();
    }
}
