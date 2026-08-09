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

    /** Cache quyền trong cùng 1 request (tránh query lặp khi render menu/filter) */
    private static array $userPermissionsCache = [];

    public function __construct()
    {
        $this->permissionModel     = new PermissionModel();
        $this->rolePermissionModel = new RolePermissionModel();
        $this->userRoleModel       = new UserRoleModel();
    }

    /**
     * Xóa cache quyền của user (gọi khi đổi vai trò/quyền)
     */
    public static function clearCache(?int $userId = null): void
    {
        if ($userId === null) {
            self::$userPermissionsCache = [];
            return;
        }
        unset(self::$userPermissionsCache[$userId]);
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
        if (isset(self::$userPermissionsCache[$userId])) {
            return self::$userPermissionsCache[$userId];
        }

        $roleIds = $this->userRoleModel->getRoleIdsByUser($userId);
        $permissions = [];
        foreach ($roleIds as $roleId) {
            $rolePerms = $this->getPermissionsByRole($roleId);
            foreach ($rolePerms as $perm) {
                $permSlug = is_object($perm) ? $perm->slug : ($perm['slug'] ?? null);
                if ($permSlug) {
                    $permissions[$permSlug] = $perm;
                }
            }
        }

        return self::$userPermissionsCache[$userId] = $permissions;
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
