<?php

namespace App\Models;

use CodeIgniter\Model;

class BranchModel extends Model
{
    protected $table            = 'branches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Branch::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'facility_id', 'code', 'branch_type', 'name', 'email', 'phone', 'address', 'country_code', 'timezone', 'currency',
        'city', 'district', 'latitude', 'longitude',
        'total_courts', 'indoor_courts', 'outdoor_courts',
        'has_parking', 'has_canteen', 'has_locker', 'has_shower', 'has_wifi',
        'opening_date', 'images', 'settings', 'is_main', 'is_active', 'status',
        'created_by', 'updated_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id' => 'required|is_natural_no_zero',
        'code'      => 'required|max_length[50]',
        'name'      => 'required|max_length[255]',
        'email'     => 'permit_empty|valid_email|max_length[255]',
        'phone'     => 'permit_empty|max_length[50]',
        'status'    => 'required|in_list[active,inactive,maintenance,closed]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $afterInsert    = ['logAudit'];
    protected $afterUpdate    = ['logAudit'];
    protected $afterDelete    = ['logAudit'];

    public function logAudit(array $data)
    {
        if (function_exists('log_audit')) {
            log_audit($data);
        }
        return $data;
    }

    public function getByTenant(int $tenantId)
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('deleted_at', null)
                    ->findAll();
    }

    public function getMainBranch(int $tenantId)
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('is_main', 1)
                    ->where('deleted_at', null)
                    ->first();
    }

    public function findForTenant(int $branchId, int $tenantId): ?object
    {
        return $this->where('id', $branchId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }

    public function getByFacilityForTenant(int $facilityId, int $tenantId): array
    {
        return $this->where('facility_id', $facilityId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->orderBy('branch_type', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();
    }
}
