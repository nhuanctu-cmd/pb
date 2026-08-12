<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerClubMembershipModel extends Model
{
    protected $table = 'player_club_memberships';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id', 'club_id', 'player_id', 'role', 'status', 'source',
        'is_primary', 'joined_at', 'left_at', 'verified_at', 'verified_by', 'metadata',
    ];

    public function forPlayer(int $playerId, int $tenantId): array
    {
        return $this->where('player_id', $playerId)
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'active'])
            ->orderBy('is_primary', 'DESC')
            ->orderBy('joined_at', 'DESC')
            ->findAll();
    }
}
