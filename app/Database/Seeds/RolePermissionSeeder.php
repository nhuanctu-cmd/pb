<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // ========== ROLES ==========
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin', 'is_system' => 1, 'tenant_id' => null, 'status' => 'active'],
            ['name' => 'Owner', 'slug' => 'owner', 'is_system' => 1, 'tenant_id' => null, 'status' => 'active'],
            ['name' => 'Branch Manager', 'slug' => 'branch-manager', 'is_system' => 1, 'tenant_id' => null, 'status' => 'active'],
            ['name' => 'Staff', 'slug' => 'staff', 'is_system' => 1, 'tenant_id' => null, 'status' => 'active'],
            ['name' => 'Referee', 'slug' => 'referee', 'is_system' => 1, 'tenant_id' => null, 'status' => 'active'],
            ['name' => 'Player', 'slug' => 'player', 'is_system' => 1, 'tenant_id' => null, 'status' => 'active'],
        ];

        foreach ($roles as &$role) {
            $role['created_at'] = date('Y-m-d H:i:s');
            $role['updated_at'] = date('Y-m-d H:i:s');
            $role['created_by'] = 1;
        }
        $this->db->table('roles')->insertBatch($roles);

        // ========== PERMISSIONS ==========
        $permissions = [
            // Tenant Management
            ['name' => 'View Tenants', 'slug' => 'tenants.view', 'module' => 'tenants', 'status' => 'active'],
            ['name' => 'Create Tenants', 'slug' => 'tenants.create', 'module' => 'tenants', 'status' => 'active'],
            ['name' => 'Edit Tenants', 'slug' => 'tenants.edit', 'module' => 'tenants', 'status' => 'active'],
            ['name' => 'Delete Tenants', 'slug' => 'tenants.delete', 'module' => 'tenants', 'status' => 'active'],

            // Branch Management
            ['name' => 'View Branches', 'slug' => 'branches.view', 'module' => 'branches', 'status' => 'active'],
            ['name' => 'Create Branches', 'slug' => 'branches.create', 'module' => 'branches', 'status' => 'active'],
            ['name' => 'Edit Branches', 'slug' => 'branches.edit', 'module' => 'branches', 'status' => 'active'],
            ['name' => 'Delete Branches', 'slug' => 'branches.delete', 'module' => 'branches', 'status' => 'active'],

            // User Management
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users', 'status' => 'active'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users', 'status' => 'active'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'users', 'status' => 'active'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users', 'status' => 'active'],

            // Role Management
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'roles', 'status' => 'active'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'module' => 'roles', 'status' => 'active'],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'module' => 'roles', 'status' => 'active'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'module' => 'roles', 'status' => 'active'],

            // Settings
            ['name' => 'View Settings', 'slug' => 'settings.view', 'module' => 'settings', 'status' => 'active'],
            ['name' => 'Edit Settings', 'slug' => 'settings.edit', 'module' => 'settings', 'status' => 'active'],

            // Audit Logs
            ['name' => 'View Audit Logs', 'slug' => 'audit-logs.view', 'module' => 'audit_logs', 'status' => 'active'],

            // Booking Management
            ['name' => 'View Bookings', 'slug' => 'bookings.view', 'module' => 'bookings', 'status' => 'active'],
            ['name' => 'Create Bookings', 'slug' => 'bookings.create', 'module' => 'bookings', 'status' => 'active'],
            ['name' => 'Edit Bookings', 'slug' => 'bookings.edit', 'module' => 'bookings', 'status' => 'active'],
            ['name' => 'Cancel Bookings', 'slug' => 'bookings.cancel', 'module' => 'bookings', 'status' => 'active'],

            // Court Management
            ['name' => 'View Courts', 'slug' => 'courts.view', 'module' => 'courts', 'status' => 'active'],
            ['name' => 'Create Courts', 'slug' => 'courts.create', 'module' => 'courts', 'status' => 'active'],
            ['name' => 'Edit Courts', 'slug' => 'courts.edit', 'module' => 'courts', 'status' => 'active'],
            ['name' => 'Delete Courts', 'slug' => 'courts.delete', 'module' => 'courts', 'status' => 'active'],

            // Reports
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'reports', 'status' => 'active'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'reports', 'status' => 'active'],
        ];

        foreach ($permissions as &$perm) {
            $perm['created_at'] = date('Y-m-d H:i:s');
            $perm['updated_at'] = date('Y-m-d H:i:s');
            $perm['created_by'] = 1;
        }
        $this->db->table('permissions')->insertBatch($permissions);

        // ========== ROLE PERMISSIONS (Super Admin gets all) ==========
        $allPerms = $this->db->table('permissions')->select('id')->get()->getResult();
        $rolePerms = [];
        foreach ($allPerms as $perm) {
            $rolePerms[] = [
                'role_id'       => 1, // Super Admin
                'permission_id' => $perm->id,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ];
        }
        $this->db->table('role_permissions')->insertBatch($rolePerms);

        // Owner gets tenant/booking/court permissions
        $ownerPermIds = [1,2,3,4,5,6,7,8,9,10,11,12,13,17,18,20,21,22,23,24,25,26,27,28,29];
        $ownerPerms = [];
        foreach ($ownerPermIds as $pid) {
            $ownerPerms[] = [
                'role_id'       => 2, // Owner
                'permission_id' => $pid,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ];
        }
        $this->db->table('role_permissions')->insertBatch($ownerPerms);
    }
}
