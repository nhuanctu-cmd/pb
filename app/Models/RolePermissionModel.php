<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['role_id', 'permission_id', 'created_by', 'updated_by'];

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
        'role_id'       => 'required|is_natural_no_zero',
        'permission_id' => 'required|is_natural_no_zero',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;

    public function getPermissionIdsByRole(int $roleId): array
    {
        $rows = $this->select('permission_id')
                     ->where('role_id', $roleId)
                     ->where('deleted_at', null)
                     ->findAll();
        return array_column($rows, 'permission_id');
    }

    public function syncPermissions(int $roleId, array $permissionIds)
    {
        $this->where('role_id', $roleId)->delete();
        $data = [];
        foreach ($permissionIds as $permId) {
            $data[] = ['role_id' => $roleId, 'permission_id' => $permId];
        }
        if (!empty($data)) {
            return $this->insertBatch($data);
        }
        return true;
    }
}
