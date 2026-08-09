<?php

namespace App\Models;

use CodeIgniter\Model;

class MembershipPackageModel extends Model
{
    protected $table            = 'membership_packages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\MembershipPackage::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'name_vi', 'name_en', 'duration_days', 'price',
        'discount_percent', 'booking_priority', 'status', 'created_by', 'updated_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id'     => 'required|integer',
        'name_vi'       => 'required|max_length[255]',
        'duration_days' => 'required|integer',
        'price'         => 'required|decimal',
        'status'        => 'permit_empty|in_list[active,inactive]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function getActiveByTenant(int $tenantId)
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->where('deleted_at', null)
                    ->findAll();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('deleted_at', null)
                    ->orderBy('price', 'ASC')
                    ->findAll();
    }
}
