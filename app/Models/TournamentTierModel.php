<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentTierModel extends Model
{
    protected $table = 'tournament_tiers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'tenant_id', 'ranking_authority_id', 'code', 'name_vi', 'name_en',
        'point_multiplier', 'default_rating_weight', 'sort_order', 'is_active',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getActive(?int $tenantId = null, ?int $rankingAuthorityId = null): array
    {
        $builder = $this->where('is_active', 1)->where('deleted_at', null);
        if ($tenantId !== null) {
            $builder->groupStart()
                ->where('tenant_id', $tenantId)
                ->orWhere('tenant_id', null)
                ->groupEnd();
        }
        if ($rankingAuthorityId !== null) {
            $builder->where('ranking_authority_id', $rankingAuthorityId);
        }
        return $builder->orderBy('sort_order', 'ASC')->findAll();
    }

    public function findByCode(string $code, ?int $tenantId = null): ?object
    {
        $builder = $this->where('code', $code)->where('is_active', 1)->where('deleted_at', null);
        if ($tenantId !== null) {
            $builder->groupStart()
                ->where('tenant_id', $tenantId)
                ->orWhere('tenant_id', null)
                ->groupEnd();
        }
        return $builder->first();
    }
}
