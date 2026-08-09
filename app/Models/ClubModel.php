<?php

namespace App\Models;

use CodeIgniter\Model;

class ClubModel extends Model
{
    protected $table = 'clubs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'name_vi', 'name_en', 'logo', 'description_vi', 'description_en',
        'owner_player_id', 'status',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'name_vi' => 'required|max_length[255]',
        'status' => 'permit_empty|in_list[active,inactive,pending]',
    ];

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->where('tenant_id', $tenantId)->where('deleted_at', null);

        if (! empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $builder->groupStart()
                ->like('name_vi', $filters['search'])
                ->orLike('name_en', $filters['search'])
                ->groupEnd();
        }

        return $builder->orderBy('name_vi', 'ASC')->findAll();
    }

    public function findForTenant(int $clubId, int $tenantId): ?object
    {
        return $this->where('id', $clubId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }
}
