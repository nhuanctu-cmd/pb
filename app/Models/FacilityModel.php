<?php

namespace App\Models;

use CodeIgniter\Model;

class FacilityModel extends Model
{
    protected $table = 'facilities';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = \App\Entities\Facility::class;
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'code', 'name_vi', 'name_en', 'description_vi', 'description_en',
        'address', 'city', 'district', 'latitude', 'longitude', 'phone', 'email',
        'website', 'logo', 'cover_image', 'timezone', 'currency', 'total_courts',
        'total_branches', 'is_active', 'status', 'sort_order', 'meta',
        'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'code'      => 'required|max_length[50]',
        'name_vi'   => 'required|max_length[255]',
        'status'    => 'required|in_list[active,inactive,suspended]',
    ];

    public function getByTenant(int $tenantId, array $filters = [])
    {
        $builder = $this->where('tenant_id', $tenantId)->where('deleted_at', null);

        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('code', $filters['search'])
                ->orLike('name_vi', $filters['search'])
                ->orLike('name_en', $filters['search'])
                ->groupEnd();
        }

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        return $builder->orderBy('sort_order', 'ASC')->orderBy('name_vi', 'ASC')->findAll();
    }

    public function findForTenant(int $facilityId, int $tenantId): ?object
    {
        return $this->where('id', $facilityId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }
}
