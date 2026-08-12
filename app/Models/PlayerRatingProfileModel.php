<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerRatingProfileModel extends Model
{
    protected $table = 'player_rating_profiles';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'player_id', 'provider_id', 'discipline_id', 'rating_value', 'skill_band_id', 'reliability_score', 'status',
        'rated_match_count', 'verified_match_count', 'last_rated_match_at', 'highest_rating', 'lowest_rating', 'established_at', 'calculated_at', 'metadata',
    ];
    protected $useTimestamps = true;

    public function findProfile(int $tenantId, int $playerId, int $providerId, int $disciplineId): ?object
    {
        return $this->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('provider_id', $providerId)->where('discipline_id', $disciplineId)->first();
    }
}
