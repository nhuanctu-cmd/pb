<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerFollowModel extends Model
{
    protected $table = 'player_follows';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'follower_player_id', 'following_player_id', 'status'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findRelation(int $tenantId, int $followerId, int $followingId): ?object
    {
        return $this->where('tenant_id', $tenantId)->where('follower_player_id', $followerId)->where('following_player_id', $followingId)->where('deleted_at', null)->first();
    }

    public function getFollowing(int $tenantId, int $followerId): array
    {
        return $this->select('player_follows.*, players.full_name, players.rating_score')->join('players', 'players.id = player_follows.following_player_id', 'left')->where('player_follows.tenant_id', $tenantId)->where('player_follows.follower_player_id', $followerId)->where('player_follows.status', 'active')->where('player_follows.deleted_at', null)->findAll();
    }
}
