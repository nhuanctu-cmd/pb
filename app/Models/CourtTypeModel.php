<?php

namespace App\Models;

use CodeIgniter\Model;

class CourtTypeModel extends Model
{
    protected $table            = 'court_types';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\CourtType::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'name_vi', 'name_en', 'description_vi', 'description_en',
        'default_capacity', 'status', 'created_by', 'updated_by',
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
        'tenant_id'        => 'required|integer',
        'name_vi'          => 'required|max_length[255]',
        'name_en'          => 'permit_empty|max_length[255]',
        'default_capacity' => 'permit_empty|integer|greater_than[0]',
        'status'           => 'required|in_list[active,inactive]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = ['logAudit'];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = ['logAudit'];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = ['logAudit'];

    public function logAudit(array $data)
    {
        if (function_exists('log_audit')) {
            log_audit($data);
        }
        return $data;
    }

    public function getActive(int $tenantId)
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
                    ->findAll();
    }
}
