<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerLevelModel extends Model
{
    protected $table = 'player_levels';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'code', 'name', 'min_rating', 'max_rating', 'color',
        'sort_order', 'is_active',
    ];
    protected $useTimestamps = true;

    public function getActive(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    public function findByRating(int $tenantId, int $rating)
    {
        return $this->where('tenant_id', $tenantId)
            ->where('min_rating <=', $rating)
            ->where('max_rating >=', $rating)
            ->where('is_active', 1)
            ->orderBy('sort_order', 'DESC')
            ->first();
    }
}
