<?php

namespace App\Services;

use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use App\Models\UserRoleModel;

class PermissionService
{
    protected PermissionModel $permissionModel;
    protected RolePermissionModel $rolePermissionModel;
    protected UserRoleModel $userRoleModel;

    public function __construct()
    {
        $this->permissionModel     = new PermissionModel();
        $this->rolePermissionModel = new RolePermissionModel();
        $this->userRoleModel       = new UserRoleModel();
    }

    public function getAllPermissions(): array
    {
        return $this->permissionModel->where('deleted_at', null)->findAll();
    }

    public function getPermissionsByRole(int $roleId): array
    {
        return $this->permissionModel->getPermissionsByRole($roleId);
    }

    public function getPermissionIdsByRole(int $roleId): array
    {
        return $this->rolePermissionModel->getPermissionIdsByRole($roleId);
    }

    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        return $this->rolePermissionModel->syncPermissions($roleId, $permissionIds);
    }

    public function getUserPermissions(int $userId): array
    {
        $roleIds = $this->userRoleModel->getRoleIdsByUser($userId);
        $permissions = [];
        foreach ($roleIds as $roleId) {
            $rolePerms = $this->getPermissionsByRole($roleId);
            foreach ($rolePerms as $perm) {
                $permissions[$perm->slug] = $perm;
            }
        }
        return $permissions;
    }

    public function hasPermission(int $userId, string $permissionSlug): bool
    {
        $permissions = $this->getUserPermissions($userId);
        return isset($permissions[$permissionSlug]);
    }

    public function hasAnyPermission(int $userId, array $permissionSlugs): bool
    {
        $permissions = $this->getUserPermissions($userId);
        foreach ($permissionSlugs as $slug) {
            if (isset($permissions[$slug])) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(int $userId, array $permissionSlugs): bool
    {
        $permissions = $this->getUserPermissions($userId);
        foreach ($permissionSlugs as $slug) {
            if (!isset($permissions[$slug])) {
                return false;
            }
        }
        return true;
    }

    public function createPermission(array $data): ?int
    {
        return $this->permissionModel->insert($data) ?: null;
    }

    public function updatePermission(int $id, array $data): bool
    {
        return $this->permissionModel->update($id, $data);
    }

    public function deletePermission(int $id): bool
    {
        return $this->permissionModel->delete($id);
    }
}
