<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table            = 'user_roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'role_id', 'created_by', 'updated_by'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'user_id' => 'required|is_natural_no_zero',
        'role_id' => 'required|is_natural_no_zero',
    ];

    public function getRolesByUser(int $userId): array
    {
        return $this->select('roles.*')
                    ->join('roles', 'roles.id = user_roles.role_id')
                    ->where('user_roles.user_id', $userId)
                    ->where('user_roles.deleted_at', null)
                    ->findAll();
    }

    public function getRoleIdsByUser(int $userId): array
    {
        $rows = $this->select('role_id')
                     ->where('user_id', $userId)
                     ->where('deleted_at', null)
                     ->findAll();
        return array_column($rows, 'role_id');
    }

    public function syncUserRoles(int $userId, array $roleIds)
    {
        $this->where('user_id', $userId)->delete();
        $data = [];
        foreach ($roleIds as $roleId) {
            $data[] = ['user_id' => $userId, 'role_id' => $roleId];
        }
        if (!empty($data)) {
            return $this->insertBatch($data);
        }
        return true;
    }
}
