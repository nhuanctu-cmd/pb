<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run()
    {
        // Seed court types for tenant 1
        $courtTypes = [
            [
                'tenant_id'        => 1,
                'name_vi'          => 'Sân Standard',
                'name_en'          => 'Standard Court',
                'description_vi'   => 'Sân pickleball tiêu chuẩn, kích thước 13.41m x 6.10m',
                'description_en'   => 'Standard pickleball court, size 13.41m x 6.10m',
                'default_capacity' => 4,
                'status'           => 'active',
            ],
            [
                'tenant_id'        => 1,
                'name_vi'          => 'Sân VIP',
                'name_en'          => 'VIP Court',
                'description_vi'   => 'Sân pickleball cao cấp với đầy đủ tiện nghi',
                'description_en'   => 'Premium pickleball court with full amenities',
                'default_capacity' => 4,
                'status'           => 'active',
            ],
            [
                'tenant_id'        => 1,
                'name_vi'          => 'Sân Training',
                'name_en'          => 'Training Court',
                'description_vi'   => 'Sân tập luyện với kích thước nhỏ hơn',
                'description_en'   => 'Training court with smaller size',
                'default_capacity' => 2,
                'status'           => 'active',
            ],
        ];

        $courtTypeIds = [];
        foreach ($courtTypes as $type) {
            $this->db->table('court_types')->insert($type);
            $courtTypeIds[] = $this->db->insertID();
        }

        $branchSeeds = [
            ['code' => 'PPC-HCM1', 'name' => 'PPC Trung Tâm - Quận 1', 'district' => 'Quận 1', 'is_main' => 1],
            ['code' => 'PPC-HCM2', 'name' => 'PPC Thảo Điền - Quận 2', 'district' => 'Quận 2', 'is_main' => 0],
            ['code' => 'PPC-HCM3', 'name' => 'PPC Phú Mỹ Hưng - Quận 7', 'district' => 'Quận 7', 'is_main' => 0],
            ['code' => 'PPC-HCM4', 'name' => 'PPC Bình Thạnh', 'district' => 'Bình Thạnh', 'is_main' => 0],
            ['code' => 'PPC-HCM5', 'name' => 'PPC Tân Bình', 'district' => 'Tân Bình', 'is_main' => 0],
            ['code' => 'PPC-HCM6', 'name' => 'PPC Thủ Đức', 'district' => 'Thủ Đức', 'is_main' => 0],
        ];

        foreach ($branchSeeds as $seed) {
            $exists = $this->db->table('branches')
                ->where('tenant_id', 1)
                ->where('code', $seed['code'])
                ->get()
                ->getRow();

            if (!$exists) {
                $this->db->table('branches')->insert([
                    'tenant_id'  => 1,
                    'code'       => $seed['code'],
                    'name'       => $seed['name'],
                    'email'      => strtolower($seed['code']) . '@pickleballpro.com',
                    'phone'      => '0909' . rand(100000, 999999),
                    'address'    => $seed['name'],
                    'city'       => 'Hồ Chí Minh',
                    'district'   => $seed['district'],
                    'is_main'    => $seed['is_main'],
                    'is_active'  => 1,
                    'status'     => 'active',
                    'created_by' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $branches = $this->db->table('branches')
            ->where('tenant_id', 1)
            ->whereIn('code', array_column($branchSeeds, 'code'))
            ->orderBy('code', 'ASC')
            ->get()
            ->getResult();

        $statuses = ['available', 'available', 'available', 'occupied', 'available', 'maintenance', 'available', 'available'];

        foreach ($branches as $branch) {
            $branchId = $branch->id;

            if ($this->db->table('courts')->where('branch_id', $branchId)->countAllResults() === 0) {
                // Create 8 courts per branch
                for ($i = 1; $i <= 8; $i++) {
                    $courtTypeKey = ($i <= 4) ? 0 : (($i <= 6) ? 1 : 2);
                    $courtTypeId = $courtTypeIds[$courtTypeKey];
                    $floor = ($i <= 4) ? 1 : 2;
                    $status = $statuses[$i - 1];

                    $this->db->table('courts')->insert([
                        'tenant_id'     => 1,
                        'branch_id'     => $branchId,
                        'court_type_id' => $courtTypeId,
                        'code'          => $branch->code . '-C' . str_pad($i, 2, '0', STR_PAD_LEFT),
                        'name_vi'       => 'Sân ' . $i,
                        'name_en'       => 'Court ' . $i,
                        'floor'         => $floor,
                        'area'          => 81.80,
                        'is_indoor'     => $i <= 6 ? 1 : 0,
                        'has_light'     => 1,
                        'has_fan'       => $i <= 4 ? 1 : 0,
                        'has_camera'    => $i <= 2 ? 1 : 0,
                        'status'        => $status,
                        'sort_order'    => $i,
                    ]);
                }

                // Create opening hours for branch
                $openingHours = [
                    ['day_of_week' => 0, 'open_time' => '07:00:00', 'close_time' => '22:00:00', 'is_closed' => 0],
                    ['day_of_week' => 1, 'open_time' => '06:00:00', 'close_time' => '23:00:00', 'is_closed' => 0],
                    ['day_of_week' => 2, 'open_time' => '06:00:00', 'close_time' => '23:00:00', 'is_closed' => 0],
                    ['day_of_week' => 3, 'open_time' => '06:00:00', 'close_time' => '23:00:00', 'is_closed' => 0],
                    ['day_of_week' => 4, 'open_time' => '06:00:00', 'close_time' => '23:00:00', 'is_closed' => 0],
                    ['day_of_week' => 5, 'open_time' => '06:00:00', 'close_time' => '23:00:00', 'is_closed' => 0],
                    ['day_of_week' => 6, 'open_time' => '07:00:00', 'close_time' => '23:00:00', 'is_closed' => 0],
                ];

                foreach ($openingHours as $hour) {
                    $existsHour = $this->db->table('branch_opening_hours')
                        ->where('tenant_id', 1)
                        ->where('branch_id', $branchId)
                        ->where('day_of_week', $hour['day_of_week'])
                        ->get()
                        ->getRow();

                    if ($existsHour) {
                        continue;
                    }

                    $this->db->table('branch_opening_hours')->insert([
                        'tenant_id'   => 1,
                        'branch_id'   => $branchId,
                        'day_of_week' => $hour['day_of_week'],
                        'open_time'   => $hour['open_time'],
                        'close_time'  => $hour['close_time'],
                        'is_closed'   => $hour['is_closed'],
                    ]);
                }
            }
        }
    }
}
