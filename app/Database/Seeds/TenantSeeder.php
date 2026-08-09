<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run()
    {
        // Tenant 1: Pickleball Pro Center
        $this->db->table('tenants')->insert([
            'code'        => 'PPC001',
            'name'        => 'Pickleball Pro Center',
            'email'       => 'info@pickleballpro.com',
            'phone'       => '0909123456',
            'address'     => '123 Nguyễn Huệ, Quận 1, TP.HCM',
            'is_active'   => 1,
            'status'      => 'active',
            'created_by'  => 1,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // Tenant 2: Sunny Pickleball Club
        $this->db->table('tenants')->insert([
            'code'        => 'SPC002',
            'name'        => 'Sunny Pickleball Club',
            'email'       => 'contact@sunnypickleball.com',
            'phone'       => '0909987654',
            'address'     => '456 Lê Lợi, Quận 3, TP.HCM',
            'is_active'   => 1,
            'status'      => 'active',
            'created_by'  => 1,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // Branches for Tenant 1
        $this->db->table('branches')->insertBatch([
            [
                'tenant_id'  => 1,
                'code'       => 'PPC-HCM1',
                'name'       => 'PPC Trung Tâm - Quận 1',
                'email'      => 'hcm1@pickleballpro.com',
                'phone'      => '0909123456',
                'address'    => '123 Nguyễn Huệ, Quận 1',
                'city'       => 'Hồ Chí Minh',
                'district'   => 'Quận 1',
                'is_main'    => 1,
                'is_active'  => 1,
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'tenant_id'  => 1,
                'code'       => 'PPC-HCM2',
                'name'       => 'PPC Thảo Điền - Quận 2',
                'email'      => 'hcm2@pickleballpro.com',
                'phone'      => '0909123457',
                'address'    => '456 Thảo Điền, Quận 2',
                'city'       => 'Hồ Chí Minh',
                'district'   => 'Quận 2',
                'is_main'    => 0,
                'is_active'  => 1,
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);

        // Branches for Tenant 2
        $this->db->table('branches')->insertBatch([
            [
                'tenant_id'  => 2,
                'code'       => 'SPC-HCM1',
                'name'       => 'Sunny Center - Quận 3',
                'email'      => 'hcm1@sunnypickleball.com',
                'phone'      => '0909987654',
                'address'    => '456 Lê Lợi, Quận 3',
                'city'       => 'Hồ Chí Minh',
                'district'   => 'Quận 3',
                'is_main'    => 1,
                'is_active'  => 1,
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'tenant_id'  => 2,
                'code'       => 'SPC-HN1',
                'name'       => 'Sunny Pickleball Hà Nội',
                'email'      => 'hn1@sunnypickleball.com',
                'phone'      => '0909987655',
                'address'    => '789 Trần Hưng Đạo, Hoàn Kiếm',
                'city'       => 'Hà Nội',
                'district'   => 'Hoàn Kiếm',
                'is_main'    => 0,
                'is_active'  => 1,
                'status'     => 'active',
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
