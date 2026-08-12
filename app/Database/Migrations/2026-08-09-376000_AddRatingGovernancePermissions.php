<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Makes rating governance permissions available without resetting existing RBAC.
 *
 * This is intentionally additive: running the RBAC seeder is still optional and
 * this migration never removes permissions granted by an administrator.
 */
class AddRatingGovernancePermissions extends Migration
{
    private const PERMISSIONS = [
        'rating.view'    => 'Xem điểm trình',
        'rating.review'  => 'Duyệt claim/import và cảnh báo rating',
        'rating.adjust'  => 'Điều chỉnh rating thủ công',
        'rating.imports' => 'Quản lý dữ liệu import rating',
    ];

    public function up()
    {
        if (! $this->db->tableExists('permissions') || ! $this->db->tableExists('role_permissions')) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $permissionIds = [];

        foreach (self::PERMISSIONS as $slug => $name) {
            $permission = $this->db->table('permissions')->where('slug', $slug)->get()->getRowArray();

            if ($permission) {
                $this->db->table('permissions')->where('id', $permission['id'])->update([
                    'name'       => $name,
                    'module'     => 'rating',
                    'is_active'  => 1,
                    'status'     => 'active',
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);
                $permissionIds[$slug] = (int) $permission['id'];
                continue;
            }

            $this->db->table('permissions')->insert([
                'name'       => $name,
                'slug'       => $slug,
                'module'     => 'rating',
                'is_active'  => 1,
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $permissionIds[$slug] = (int) $this->db->insertID();
        }

        foreach (['owner', 'super-admin'] as $roleSlug) {
            $role = $this->db->table('roles')->where('slug', $roleSlug)->get()->getRowArray();
            if (! $role) {
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                $assignment = $this->db->table('role_permissions')
                    ->where('role_id', $role['id'])
                    ->where('permission_id', $permissionId)
                    ->get()
                    ->getRowArray();

                if ($assignment) {
                    $this->db->table('role_permissions')->where('id', $assignment['id'])->update([
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);
                    continue;
                }

                $this->db->table('role_permissions')->insert([
                    'role_id'       => $role['id'],
                    'permission_id' => $permissionId,
                    'created_by'    => 1,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }

    public function down()
    {
        // Additive governance permissions remain intact on rollback to avoid
        // revoking an administrator's explicit access unexpectedly.
    }
}
