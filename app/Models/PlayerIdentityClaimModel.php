<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerIdentityClaimModel extends Model
{
    protected $table = 'player_identity_claims';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'player_id', 'claim_type', 'claim_value', 'verified_at', 'verification_source', 'is_primary',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findByPlayer(int $playerId): array
    {
        return $this->where('player_id', $playerId)->findAll();
    }

    public function findByValue(string $type, string $value): ?object
    {
        return $this->where('claim_type', $type)
            ->where('claim_value', $value)
            ->first();
    }

    public function findPotentialDuplicates(int $excludePlayerId, ?string $phone = null, ?string $email = null): array
    {
        $builder = $this->where('player_id !=', $excludePlayerId);
        $builder->groupStart();
        if ($phone) {
            $builder->orGroupStart()
                ->where('claim_type', 'phone')
                ->where('claim_value', $phone)
                ->groupEnd();
        }
        if ($email) {
            $builder->orGroupStart()
                ->where('claim_type', 'email')
                ->where('claim_value', $email)
                ->groupEnd();
        }
        $builder->groupEnd();
        return $builder->findAll();
    }
}
