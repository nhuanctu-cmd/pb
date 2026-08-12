<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantModel extends Model
{
    protected $table            = 'tenants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Tenant::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'code', 'country_code', 'default_timezone', 'default_currency', 'default_locale', 'name', 'email', 'phone', 'address', 'logo',
        'domain', 'db_name', 'is_active', 'status',
        'created_by', 'updated_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'code'      => 'required|is_unique[tenants.code,id,{id}]|min_length[2]|max_length[50]',
        'name'      => 'required|max_length[255]',
        'email'     => 'permit_empty|valid_email|max_length[255]',
        'phone'     => 'permit_empty|max_length[50]',
        'status'    => 'required|in_list[active,inactive,suspended]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
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

    public function getActive()
    {
        return $this->where('status', 'active')
                    ->where('deleted_at', null)
                    ->findAll();
    }

    public function getByCode(string $code)
    {
        return $this->where('code', $code)->first();
    }
}
