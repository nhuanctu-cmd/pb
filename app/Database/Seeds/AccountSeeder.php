<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Cấp đủ tài khoản demo cho MỌI vai trò (idempotent — bỏ qua email đã tồn tại).
 *
 * Tenant 1 (Pickleball Pro):
 *   - owner1   / Chủ sân            (đã có từ UserSeeder)
 *   - manager1 / Quản lý chi nhánh  (đã có)
 *   - staff1   / Nhân viên          (đã có)
 *   - referee1 / Trọng tài          (mới)
 *   - player1  / Người chơi          (mới)
 * Tenant 2 (Sunny): owner2, staff2 (đã có) + manager2, referee2 (mới)
 */
class AccountSeeder extends Seeder
{
    public function run()
    {
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $roleId = fn (string $slug) => (int) $db->table('roles')->where('slug', $slug)->get()->getRow('id');

        $accounts = [
            // Tenant 1
            ['tenant_id' => 1, 'branch_id' => 1, 'username' => 'referee1', 'email' => 'referee@pickleballpro.com', 'first_name' => 'Trọng tài', 'last_name' => 'Một',  'role' => 'referee'],
            ['tenant_id' => 1, 'branch_id' => 1, 'username' => 'player1',  'email' => 'player@pickleballpro.com',  'first_name' => 'Người chơi', 'last_name' => 'Một', 'role' => 'player'],
            // Tenant 2
            ['tenant_id' => 2, 'branch_id' => 3, 'username' => 'manager2', 'email' => 'manager@sunnypickleball.com', 'first_name' => 'Quản lý', 'last_name' => 'Hai', 'role' => 'branch-manager'],
            ['tenant_id' => 2, 'branch_id' => 3, 'username' => 'referee2', 'email' => 'referee@sunnypickleball.com', 'first_name' => 'Trọng tài', 'last_name' => 'Hai', 'role' => 'referee'],
        ];

        $created = 0;
        foreach ($accounts as $acc) {
            $exists = $db->table('users')->where('email', $acc['email'])->countAllResults();
            if ($exists) {
                continue;
            }

            $db->table('users')->insert([
                'tenant_id'     => $acc['tenant_id'],
                'branch_id'     => $acc['branch_id'],
                'username'      => $acc['username'],
                'email'         => $acc['email'],
                'password'      => password_hash('password', PASSWORD_DEFAULT),
                'first_name'    => $acc['first_name'],
                'last_name'     => $acc['last_name'],
                'is_superadmin' => 0,
                'is_active'     => 1,
                'status'        => 'active',
                'created_by'    => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            $userId = (int) $db->insertID();
            $rid    = $roleId($acc['role']);

            if ($userId && $rid) {
                $db->table('user_roles')->insert([
                    'user_id'    => $userId,
                    'role_id'    => $rid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $created++;
        }

        echo "AccountSeeder: tạo mới {$created} tài khoản vai trò.\n";
    }
}
