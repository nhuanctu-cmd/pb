<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Role::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'name', 'slug', 'description',
        'is_system', 'is_active', 'status',
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
        'tenant_id' => 'permit_empty|is_natural_no_zero',
        'name'      => 'required|max_length[100]',
        'slug'      => 'required|max_length[100]|alpha_dash',
        'status'    => 'required|in_list[active,inactive]',
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

    public function getByTenant(?int $tenantId)
    {
        return $this->where('deleted_at', null)
                    ->groupStart()
                        ->where('tenant_id', $tenantId)
                        ->orWhere('tenant_id', null)
                    ->groupEnd()
                    ->findAll();
    }

    public function getBySlug(string $slug, ?int $tenantId = null)
    {
        return $this->where('slug', $slug)
                    ->where('tenant_id', $tenantId)
                    ->where('deleted_at', null)
                    ->first();
    }

    public function getSystemRoles()
    {
        return $this->where('is_system', 1)
                    ->where('deleted_at', null)
                    ->findAll();
    }
}
