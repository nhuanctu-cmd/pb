<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Permission::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'parent_id', 'name', 'slug', 'module', 'description',
        'is_active', 'status',
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
        'name'   => 'required|max_length[100]',
        'slug'   => 'required|is_unique[permissions.slug,id,{id}]|max_length[100]|alpha_dash',
        'module' => 'permit_empty|max_length[100]',
        'status' => 'required|in_list[active,inactive]',
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

    public function getByModule(string $module)
    {
        return $this->where('module', $module)
                    ->where('deleted_at', null)
                    ->findAll();
    }

    public function getPermissionsByRole(int $roleId)
    {
        return $this->select('permissions.*')
                    ->join('role_permissions', 'role_permissions.permission_id = permissions.id')
                    ->where('role_permissions.role_id', $roleId)
                    ->where('permissions.deleted_at', null)
                    ->findAll();
    }
}
