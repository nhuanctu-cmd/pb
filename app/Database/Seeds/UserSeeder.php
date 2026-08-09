<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Super Admin
        $this->db->table('users')->insert([
            'tenant_id'     => null,
            'branch_id'     => null,
            'username'      => 'admin',
            'email'         => 'admin@pickleball.com',
            'password'      => password_hash('admin123', PASSWORD_DEFAULT),
            'first_name'    => 'Super',
            'last_name'     => 'Admin',
            'phone'         => '0909000000',
            'is_superadmin' => 1,
            'is_active'     => 1,
            'status'        => 'active',
            'created_by'    => 1,
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        // Tenant 1 users
        $this->db->table('users')->insertBatch([
            [
                'tenant_id'     => 1,
                'branch_id'     => 1,
                'username'      => 'owner1',
                'email'         => 'owner@pickleballpro.com',
                'password'      => password_hash('password', PASSWORD_DEFAULT),
                'first_name'    => 'Owner',
                'last_name'     => 'One',
                'phone'         => '0909123456',
                'is_superadmin' => 0,
                'is_active'     => 1,
                'status'        => 'active',
                'created_by'    => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'tenant_id'     => 1,
                'branch_id'     => 1,
                'username'      => 'manager1',
                'email'         => 'manager@pickleballpro.com',
                'password'      => password_hash('password', PASSWORD_DEFAULT),
                'first_name'    => 'Branch',
                'last_name'     => 'Manager',
                'phone'         => '0909123457',
                'is_superadmin' => 0,
                'is_active'     => 1,
                'status'        => 'active',
                'created_by'    => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'tenant_id'     => 1,
                'branch_id'     => 1,
                'username'      => 'staff1',
                'email'         => 'staff@pickleballpro.com',
                'password'      => password_hash('password', PASSWORD_DEFAULT),
                'first_name'    => 'Staff',
                'last_name'     => 'One',
                'phone'         => '0909123458',
                'is_superadmin' => 0,
                'is_active'     => 1,
                'status'        => 'active',
                'created_by'    => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ]);

        // Tenant 2 users
        $this->db->table('users')->insertBatch([
            [
                'tenant_id'     => 2,
                'branch_id'     => 3,
                'username'      => 'owner2',
                'email'         => 'owner@sunnypickleball.com',
                'password'      => password_hash('password', PASSWORD_DEFAULT),
                'first_name'    => 'Owner',
                'last_name'     => 'Two',
                'phone'         => '0909987654',
                'is_superadmin' => 0,
                'is_active'     => 1,
                'status'        => 'active',
                'created_by'    => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'tenant_id'     => 2,
                'branch_id'     => 3,
                'username'      => 'staff2',
                'email'         => 'staff@sunnypickleball.com',
                'password'      => password_hash('password', PASSWORD_DEFAULT),
                'first_name'    => 'Staff',
                'last_name'     => 'Two',
                'phone'         => '0909987655',
                'is_superadmin' => 0,
                'is_active'     => 1,
                'status'        => 'active',
                'created_by'    => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ]);

        // Assign roles to users
        $this->db->table('user_roles')->insertBatch([
            ['user_id' => 1, 'role_id' => 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], // admin -> Super Admin
            ['user_id' => 2, 'role_id' => 2, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], // owner1 -> Owner
            ['user_id' => 3, 'role_id' => 3, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], // manager1 -> Branch Manager
            ['user_id' => 4, 'role_id' => 4, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], // staff1 -> Staff
            ['user_id' => 5, 'role_id' => 2, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], // owner2 -> Owner
            ['user_id' => 6, 'role_id' => 4, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')], // staff2 -> Staff
        ]);
    }
}
