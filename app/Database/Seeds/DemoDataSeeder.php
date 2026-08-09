<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private string $now;

    public function run()
    {
        $this->now = date('Y-m-d H:i:s');

        if (!$this->db->tableExists('tenants') || !$this->db->tableExists('branches')) {
            echo "Vui lòng chạy migration trước: php spark migrate\n";
            return;
        }

        $tenantId = $this->ensureTenant();
        $branchIds = $this->ensureBranches($tenantId);
        $userIds = $this->ensureUsers($tenantId, $branchIds);

        $this->ensureRoles($userIds);
        $this->ensureSettings($tenantId);

        if ($this->db->tableExists('facilities')) {
            $this->ensureFacility($tenantId, $branchIds);
        }

        if ($this->db->tableExists('court_types') && $this->db->tableExists('courts')) {
            $courtTypeIds = $this->ensureCourtTypes($tenantId);
            $this->ensureCourtStatuses($tenantId);
            $courtIds = $this->ensureCourts($tenantId, $branchIds, $courtTypeIds);
            $this->ensureOpeningHours($tenantId, $branchIds);
            $this->ensureHolidays($tenantId, $branchIds);
            $this->ensureMaintenance($tenantId, $branchIds, $courtIds);
            $this->ensureDevices($tenantId, $branchIds, $courtIds);
            $this->ensurePricingRules($tenantId, $branchIds, $courtTypeIds);
            $this->ensureBookings($tenantId, $branchIds, $courtIds, $userIds);
        }

        echo "Đã tạo/cập nhật dữ liệu mẫu thành công.\n";
        echo "Tài khoản admin: admin@demo-pickleball.vn / admin123\n";
        echo "Tài khoản quản lý: manager@demo-pickleball.vn / password\n";
        echo "Tài khoản nhân viên: staff@demo-pickleball.vn / password\n";
    }

    private function ensureTenant(): int
    {
        $tenant = $this->db->table('tenants')->where('code', 'DEMO-PB')->get()->getRow();
        if ($tenant) {
            return (int) $tenant->id;
        }

        $this->db->table('tenants')->insert($this->filterColumns('tenants', [
            'code'       => 'DEMO-PB',
            'name'       => 'Demo Pickleball Center',
            'email'      => 'hello@demo-pickleball.vn',
            'phone'      => '0901000001',
            'address'    => '12 Nguyễn Huệ, Quận 1, TP.HCM',
            'is_active'  => 1,
            'status'     => 'active',
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]));

        return (int) $this->db->insertID();
    }

    private function ensureBranches(int $tenantId): array
    {
        $branches = [
            ['code' => 'DEMO-Q1', 'name' => 'Demo Pickleball Quận 1', 'district' => 'Quận 1', 'address' => '12 Nguyễn Huệ', 'is_main' => 1],
            ['code' => 'DEMO-TD', 'name' => 'Demo Pickleball Thảo Điền', 'district' => 'Thảo Điền', 'address' => '28 Quốc Hương', 'is_main' => 0],
            ['code' => 'DEMO-PMH', 'name' => 'Demo Pickleball Phú Mỹ Hưng', 'district' => 'Quận 7', 'address' => '68 Nguyễn Lương Bằng', 'is_main' => 0],
        ];

        $ids = [];
        foreach ($branches as $index => $branch) {
            $row = $this->db->table('branches')
                ->where('tenant_id', $tenantId)
                ->where('code', $branch['code'])
                ->get()
                ->getRow();

            if (!$row) {
                $this->db->table('branches')->insert($this->filterColumns('branches', [
                    'tenant_id'  => $tenantId,
                    'code'       => $branch['code'],
                    'name'       => $branch['name'],
                    'email'      => strtolower($branch['code']) . '@demo-pickleball.vn',
                    'phone'      => '09010000' . ($index + 2),
                    'address'    => $branch['address'],
                    'city'       => 'Hồ Chí Minh',
                    'district'   => $branch['district'],
                    'is_main'    => $branch['is_main'],
                    'is_active'  => 1,
                    'status'     => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]));
                $ids[] = (int) $this->db->insertID();
            } else {
                $ids[] = (int) $row->id;
            }
        }

        return $ids;
    }

    private function ensureUsers(int $tenantId, array $branchIds): array
    {
        if (!$this->db->tableExists('users')) {
            return [];
        }

        $users = [
            'admin' => ['email' => 'admin@demo-pickleball.vn', 'username' => 'demo_admin', 'password' => 'admin123', 'first_name' => 'Demo', 'last_name' => 'Admin', 'is_superadmin' => 1, 'branch_id' => null],
            'manager' => ['email' => 'manager@demo-pickleball.vn', 'username' => 'demo_manager', 'password' => 'password', 'first_name' => 'Quản lý', 'last_name' => 'Demo', 'is_superadmin' => 0, 'branch_id' => $branchIds[0] ?? null],
            'staff' => ['email' => 'staff@demo-pickleball.vn', 'username' => 'demo_staff', 'password' => 'password', 'first_name' => 'Nhân viên', 'last_name' => 'Demo', 'is_superadmin' => 0, 'branch_id' => $branchIds[0] ?? null],
            'player' => ['email' => 'player@demo-pickleball.vn', 'username' => 'demo_player', 'password' => 'password', 'first_name' => 'Người chơi', 'last_name' => 'Demo', 'is_superadmin' => 0, 'branch_id' => $branchIds[0] ?? null],
        ];

        $ids = [];
        foreach ($users as $key => $user) {
            $row = $this->db->table('users')->where('email', $user['email'])->get()->getRow();
            if (!$row) {
                $this->db->table('users')->insert($this->filterColumns('users', [
                    'tenant_id'     => $key === 'admin' ? null : $tenantId,
                    'branch_id'     => $user['branch_id'],
                    'username'      => $user['username'],
                    'email'         => $user['email'],
                    'password'      => password_hash($user['password'], PASSWORD_DEFAULT),
                    'first_name'    => $user['first_name'],
                    'last_name'     => $user['last_name'],
                    'phone'         => '0902' . rand(100000, 999999),
                    'is_superadmin' => $user['is_superadmin'],
                    'is_active'     => 1,
                    'status'        => 'active',
                    'created_by'    => 1,
                    'updated_by'    => 1,
                    'created_at'    => $this->now,
                    'updated_at'    => $this->now,
                ]));
                $ids[$key] = (int) $this->db->insertID();
            } else {
                $ids[$key] = (int) $row->id;
            }
        }

        return $ids;
    }

    private function ensureRoles(array $userIds): void
    {
        if (!$this->db->tableExists('roles') || !$this->db->tableExists('user_roles')) {
            return;
        }

        $roles = [
            'super-admin' => 'Super Admin',
            'branch-manager' => 'Branch Manager',
            'staff' => 'Staff',
            'player' => 'Player',
        ];

        $roleIds = [];
        foreach ($roles as $slug => $name) {
            $row = $this->db->table('roles')->where('slug', $slug)->get()->getRow();
            if (!$row) {
                $this->db->table('roles')->insert($this->filterColumns('roles', [
                    'tenant_id' => null,
                    'name' => $name,
                    'slug' => $slug,
                    'is_system' => 1,
                    'is_active' => 1,
                    'status' => 'active',
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]));
                $roleIds[$slug] = (int) $this->db->insertID();
            } else {
                $roleIds[$slug] = (int) $row->id;
            }
        }

        $map = [
            'admin' => 'super-admin',
            'manager' => 'branch-manager',
            'staff' => 'staff',
            'player' => 'player',
        ];

        foreach ($map as $userKey => $roleSlug) {
            if (empty($userIds[$userKey]) || empty($roleIds[$roleSlug])) {
                continue;
            }
            $exists = $this->db->table('user_roles')
                ->where('user_id', $userIds[$userKey])
                ->where('role_id', $roleIds[$roleSlug])
                ->get()
                ->getRow();
            if (!$exists) {
                $this->db->table('user_roles')->insert($this->filterColumns('user_roles', [
                    'user_id' => $userIds[$userKey],
                    'role_id' => $roleIds[$roleSlug],
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]));
            }
        }
    }

    private function ensureSettings(int $tenantId): void
    {
        if (!$this->db->tableExists('settings')) {
            return;
        }

        $settings = [
            ['key' => 'app_name', 'value' => 'Demo Pickleball Center', 'group' => 'general', 'type' => 'text', 'is_public' => 1],
            ['key' => 'app_timezone', 'value' => 'Asia/Ho_Chi_Minh', 'group' => 'general', 'type' => 'text'],
            ['key' => 'app_locale', 'value' => 'vi', 'group' => 'general', 'type' => 'text'],
            ['key' => 'app_currency', 'value' => 'VND', 'group' => 'general', 'type' => 'text'],
            ['key' => 'slot_duration', 'value' => '60', 'group' => 'booking', 'type' => 'number'],
            ['key' => 'booking_expiry_minutes', 'value' => '15', 'group' => 'booking', 'type' => 'number'],
            ['key' => 'deposit_percent', 'value' => '30', 'group' => 'booking', 'type' => 'number'],
        ];

        foreach ($settings as $setting) {
            $exists = $this->db->table('settings')
                ->where('tenant_id', $tenantId)
                ->where('key', $setting['key'])
                ->get()
                ->getRow();
            if ($exists) {
                continue;
            }

            $this->db->table('settings')->insert($this->filterColumns('settings', [
                'tenant_id' => $tenantId,
                'branch_id' => null,
                'key' => $setting['key'],
                'value' => $setting['value'],
                'group' => $setting['group'],
                'type' => $setting['type'],
                'is_public' => $setting['is_public'] ?? 0,
                'is_active' => 1,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));
        }
    }

    private function ensureFacility(int $tenantId, array $branchIds): void
    {
        $facility = $this->db->table('facilities')->where('tenant_id', $tenantId)->where('code', 'DEMO-FAC')->get()->getRow();
        if (!$facility) {
            $this->db->table('facilities')->insert($this->filterColumns('facilities', [
                'tenant_id' => $tenantId,
                'code' => 'DEMO-FAC',
                'name_vi' => 'Cụm sân Demo Pickleball',
                'name_en' => 'Demo Pickleball Facility',
                'address' => 'TP.HCM',
                'city' => 'Hồ Chí Minh',
                'phone' => '0901000001',
                'email' => 'hello@demo-pickleball.vn',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'currency' => 'VND',
                'total_branches' => count($branchIds),
                'is_active' => 1,
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));
            $facilityId = (int) $this->db->insertID();
        } else {
            $facilityId = (int) $facility->id;
        }

        foreach ($branchIds as $branchId) {
            $update = $this->filterColumns('branches', ['facility_id' => $facilityId]);
            if (!empty($update)) {
                $this->db->table('branches')->where('id', $branchId)->update($update);
            }
        }
    }

    private function ensureCourtTypes(int $tenantId): array
    {
        $types = [
            ['name_vi' => 'Sân tiêu chuẩn', 'name_en' => 'Standard Court', 'default_capacity' => 4],
            ['name_vi' => 'Sân VIP trong nhà', 'name_en' => 'Indoor VIP Court', 'default_capacity' => 4],
            ['name_vi' => 'Sân tập luyện', 'name_en' => 'Training Court', 'default_capacity' => 2],
        ];

        $ids = [];
        foreach ($types as $type) {
            $row = $this->db->table('court_types')
                ->where('tenant_id', $tenantId)
                ->where('name_vi', $type['name_vi'])
                ->get()
                ->getRow();
            if (!$row) {
                $this->db->table('court_types')->insert($this->filterColumns('court_types', [
                    'tenant_id' => $tenantId,
                    'name_vi' => $type['name_vi'],
                    'name_en' => $type['name_en'],
                    'description_vi' => 'Dữ liệu mẫu cho ' . strtolower($type['name_vi']),
                    'description_en' => 'Demo data for ' . strtolower($type['name_en']),
                    'default_capacity' => $type['default_capacity'],
                    'status' => 'active',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]));
                $ids[] = (int) $this->db->insertID();
            } else {
                $ids[] = (int) $row->id;
            }
        }

        return $ids;
    }

    private function ensureCourtStatuses(int $tenantId): void
    {
        if (!$this->db->tableExists('court_statuses')) {
            return;
        }

        $statuses = [
            ['code' => 'available', 'name_vi' => 'Sân trống', 'name_en' => 'Available', 'color' => '#198754', 'icon' => 'bi-check-circle', 'is_bookable' => 1],
            ['code' => 'occupied', 'name_vi' => 'Đang chơi', 'name_en' => 'Occupied', 'color' => '#ffc107', 'icon' => 'bi-play-circle', 'is_bookable' => 0],
            ['code' => 'booked', 'name_vi' => 'Đã đặt', 'name_en' => 'Booked', 'color' => '#0dcaf0', 'icon' => 'bi-calendar-check', 'is_bookable' => 0],
            ['code' => 'maintenance', 'name_vi' => 'Bảo trì', 'name_en' => 'Maintenance', 'color' => '#dc3545', 'icon' => 'bi-tools', 'is_bookable' => 0],
        ];

        foreach ($statuses as $index => $status) {
            $exists = $this->db->table('court_statuses')->where('tenant_id', $tenantId)->where('code', $status['code'])->get()->getRow();
            if ($exists) {
                continue;
            }
            $this->db->table('court_statuses')->insert($this->filterColumns('court_statuses', [
                'tenant_id' => $tenantId,
                'code' => $status['code'],
                'name_vi' => $status['name_vi'],
                'name_en' => $status['name_en'],
                'color' => $status['color'],
                'icon' => $status['icon'],
                'is_bookable' => $status['is_bookable'],
                'is_active' => 1,
                'sort_order' => $index + 1,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));
        }
    }

    private function ensureCourts(int $tenantId, array $branchIds, array $courtTypeIds): array
    {
        $ids = [];
        foreach ($branchIds as $branchIndex => $branchId) {
            for ($i = 1; $i <= 6; $i++) {
                $code = 'B' . ($branchIndex + 1) . '-S' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $row = $this->db->table('courts')
                    ->where('tenant_id', $tenantId)
                    ->where('branch_id', $branchId)
                    ->where('code', $code)
                    ->get()
                    ->getRow();

                if (!$row) {
                    $status = $i === 6 ? 'maintenance' : ($i === 5 ? 'occupied' : 'available');
                    $this->db->table('courts')->insert($this->filterColumns('courts', [
                        'tenant_id' => $tenantId,
                        'branch_id' => $branchId,
                        'court_type_id' => $courtTypeIds[min($i % 3, count($courtTypeIds) - 1)] ?? $courtTypeIds[0],
                        'code' => $code,
                        'name_vi' => 'Sân ' . $i,
                        'name_en' => 'Court ' . $i,
                        'display_name' => 'Court ' . $code,
                        'floor' => $i <= 3 ? 1 : 2,
                        'area' => 81.80,
                        'is_indoor' => $i <= 4 ? 1 : 0,
                        'surface_type' => 'acrylic',
                        'has_light' => 1,
                        'has_fan' => $i <= 4 ? 1 : 0,
                        'has_camera' => $i <= 2 ? 1 : 0,
                        'status' => $status,
                        'sort_order' => $i,
                        'coordinates_x' => 40 + (($i - 1) % 3) * 170,
                        'coordinates_y' => 30 + intdiv($i - 1, 3) * 120,
                        'created_by' => 1,
                        'updated_by' => 1,
                        'created_at' => $this->now,
                        'updated_at' => $this->now,
                    ]));
                    $ids[] = (int) $this->db->insertID();
                } else {
                    $ids[] = (int) $row->id;
                }
            }
        }

        return $ids;
    }

    private function ensureOpeningHours(int $tenantId, array $branchIds): void
    {
        if (!$this->db->tableExists('branch_opening_hours')) {
            return;
        }

        foreach ($branchIds as $branchId) {
            for ($day = 0; $day <= 6; $day++) {
                $exists = $this->db->table('branch_opening_hours')
                    ->where('tenant_id', $tenantId)
                    ->where('branch_id', $branchId)
                    ->where('day_of_week', $day)
                    ->get()
                    ->getRow();
                if ($exists) {
                    continue;
                }
                $this->db->table('branch_opening_hours')->insert($this->filterColumns('branch_opening_hours', [
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'day_of_week' => $day,
                    'open_time' => in_array($day, [0, 6], true) ? '07:00:00' : '06:00:00',
                    'close_time' => '23:00:00',
                    'is_closed' => 0,
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]));
            }
        }
    }

    private function ensureHolidays(int $tenantId, array $branchIds): void
    {
        if (!$this->db->tableExists('branch_holidays') || empty($branchIds)) {
            return;
        }

        $holidays = [
            ['date' => date('Y') . '-09-02', 'name_vi' => 'Quốc khánh', 'name_en' => 'National Day', 'is_closed' => 1],
            ['date' => date('Y') . '-12-24', 'name_vi' => 'Giáng sinh - mở cửa rút gọn', 'name_en' => 'Christmas Eve', 'is_closed' => 0],
        ];

        foreach ($holidays as $holiday) {
            $exists = $this->db->table('branch_holidays')
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchIds[0])
                ->where('holiday_date', $holiday['date'])
                ->get()
                ->getRow();
            if ($exists) {
                continue;
            }
            $this->db->table('branch_holidays')->insert($this->filterColumns('branch_holidays', [
                'tenant_id' => $tenantId,
                'branch_id' => $branchIds[0],
                'holiday_date' => $holiday['date'],
                'name_vi' => $holiday['name_vi'],
                'name_en' => $holiday['name_en'],
                'is_closed' => $holiday['is_closed'],
                'note' => 'Dữ liệu mẫu',
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));
        }
    }

    private function ensureMaintenance(int $tenantId, array $branchIds, array $courtIds): void
    {
        if (!$this->db->tableExists('court_maintenance') || empty($courtIds)) {
            return;
        }

        $courtId = end($courtIds);
        $exists = $this->db->table('court_maintenance')->where('court_id', $courtId)->where('status !=', 'completed')->get()->getRow();
        if ($exists) {
            return;
        }

        $this->db->table('court_maintenance')->insert($this->filterColumns('court_maintenance', [
            'tenant_id' => $tenantId,
            'branch_id' => $branchIds[0] ?? null,
            'court_id' => $courtId,
            'start_time' => date('Y-m-d 08:00:00', strtotime('+1 day')),
            'end_time' => date('Y-m-d 12:00:00', strtotime('+1 day')),
            'reason' => 'Bảo trì mặt sân định kỳ',
            'maintenance_type' => 'routine',
            'priority' => 'medium',
            'title_vi' => 'Bảo trì mặt sân',
            'status' => 'scheduled',
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]));
    }

    private function ensureDevices(int $tenantId, array $branchIds, array $courtIds): void
    {
        if (!$this->db->tableExists('court_devices')) {
            return;
        }

        $devices = [
            ['type' => 'camera', 'name' => 'Camera sân 1', 'value' => 'recording'],
            ['type' => 'light', 'name' => 'Đèn sân 1', 'value' => 'on'],
            ['type' => 'fan', 'name' => 'Quạt sân 1', 'value' => 'off'],
            ['type' => 'locker', 'name' => 'Locker khu A', 'value' => 'ready'],
        ];

        foreach ($devices as $index => $device) {
            $code = 'DEMO-' . strtoupper($device['type']) . '-' . ($index + 1);
            $exists = $this->db->table('court_devices')->where('tenant_id', $tenantId)->where('code', $code)->get()->getRow();
            if ($exists) {
                continue;
            }
            $this->db->table('court_devices')->insert($this->filterColumns('court_devices', [
                'tenant_id' => $tenantId,
                'branch_id' => $branchIds[0] ?? null,
                'court_id' => $courtIds[$index] ?? null,
                'device_type' => $device['type'],
                'code' => $code,
                'name_vi' => $device['name'],
                'name_en' => ucfirst($device['type']) . ' Demo',
                'model' => 'DEMO-IOT-' . ($index + 1),
                'ip_address' => '192.168.10.' . (20 + $index),
                'status' => $index === 2 ? 'offline' : 'online',
                'is_active' => 1,
                'last_ping_at' => $this->now,
                'last_value' => $device['value'],
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));
        }
    }

    private function ensurePricingRules(int $tenantId, array $branchIds, array $courtTypeIds): void
    {
        if (!$this->db->tableExists('pricing_rules')) {
            return;
        }

        $rules = [
            ['code' => 'DEMO_BASE', 'name_vi' => 'Giá cơ bản', 'rule_type' => 'base_price', 'method' => 'fixed', 'adjustment' => 160000, 'multiplier' => 1],
            ['code' => 'DEMO_PEAK', 'name_vi' => 'Phụ thu giờ vàng', 'rule_type' => 'surge_pricing', 'method' => 'percentage', 'adjustment' => 30, 'multiplier' => 1.3],
            ['code' => 'DEMO_MEMBER', 'name_vi' => 'Ưu đãi hội viên', 'rule_type' => 'member_rate', 'method' => 'percentage', 'adjustment' => -15, 'multiplier' => 0.85],
        ];

        foreach ($rules as $index => $rule) {
            $exists = $this->db->table('pricing_rules')->where('tenant_id', $tenantId)->where('code', $rule['code'])->get()->getRow();
            if ($exists) {
                continue;
            }
            $this->db->table('pricing_rules')->insert($this->filterColumns('pricing_rules', [
                'tenant_id' => $tenantId,
                'branch_id' => $branchIds[0] ?? null,
                'court_type_id' => $courtTypeIds[0] ?? null,
                'code' => $rule['code'],
                'name_vi' => $rule['name_vi'],
                'name_en' => $rule['name_vi'],
                'rule_type' => $rule['rule_type'],
                'priority' => $index + 1,
                'apply_order' => ($index + 1) * 10,
                'calculation_method' => $rule['method'],
                'price_adjustment' => $rule['adjustment'],
                'price_multiplier' => $rule['multiplier'],
                'currency' => 'VND',
                'is_active' => 1,
                'apply_to_members' => $rule['rule_type'] === 'member_rate' ? 1 : 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));
            $ruleId = (int) $this->db->insertID();

            if ($rule['code'] === 'DEMO_PEAK' && $this->db->tableExists('pricing_rule_conditions')) {
                $this->db->table('pricing_rule_conditions')->insert($this->filterColumns('pricing_rule_conditions', [
                    'pricing_rule_id' => $ruleId,
                    'condition_type' => 'time_of_day',
                    'operator' => 'between',
                    'value' => '17:00',
                    'value_to' => '21:00',
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]));
            }
        }
    }

    private function ensureBookings(int $tenantId, array $branchIds, array $courtIds, array $userIds): void
    {
        if (!$this->db->tableExists('bookings') || !$this->db->tableExists('booking_items') || empty($courtIds)) {
            return;
        }

        $samples = [
            ['code' => 'DEMO-BK-001', 'name' => 'Nguyễn Minh Anh', 'phone' => '0903000001', 'date' => date('Y-m-d'), 'start' => '08:00:00', 'end' => '09:00:00', 'status' => 'paid', 'payment' => 'paid', 'court' => 0],
            ['code' => 'DEMO-BK-002', 'name' => 'Trần Quốc Bảo', 'phone' => '0903000002', 'date' => date('Y-m-d'), 'start' => '17:00:00', 'end' => '19:00:00', 'status' => 'checked_in', 'payment' => 'paid', 'court' => 1],
            ['code' => 'DEMO-BK-003', 'name' => 'Lê Hoàng Nam', 'phone' => '0903000003', 'date' => date('Y-m-d', strtotime('+1 day')), 'start' => '19:00:00', 'end' => '20:00:00', 'status' => 'pending', 'payment' => 'unpaid', 'court' => 2],
            ['code' => 'DEMO-BK-004', 'name' => 'Phạm Thu Hà', 'phone' => '0903000004', 'date' => date('Y-m-d', strtotime('-1 day')), 'start' => '18:00:00', 'end' => '20:00:00', 'status' => 'completed', 'payment' => 'paid', 'court' => 3],
            ['code' => 'DEMO-BK-005', 'name' => 'Đặng Gia Huy', 'phone' => '0903000005', 'date' => date('Y-m-d', strtotime('+2 day')), 'start' => '06:00:00', 'end' => '07:00:00', 'status' => 'cancelled', 'payment' => 'unpaid', 'court' => 4],
        ];

        foreach ($samples as $sample) {
            $exists = $this->db->table('bookings')->where('booking_code', $sample['code'])->get()->getRow();
            if ($exists) {
                continue;
            }

            $duration = (int) ((strtotime($sample['end']) - strtotime($sample['start'])) / 60);
            $amount = max(1, $duration / 60) * 160000;
            $paid = $sample['payment'] === 'paid' ? $amount : 0;
            $bookingData = [
                'tenant_id' => $tenantId,
                'branch_id' => $branchIds[0] ?? null,
                'player_id' => $userIds['player'] ?? null,
                'customer_name' => $sample['name'],
                'customer_phone' => $sample['phone'],
                'customer_email' => strtolower(str_replace(' ', '.', $this->removeVietnamese($sample['name']))) . '@demo.vn',
                'booking_code' => $sample['code'],
                'booking_date' => $sample['date'],
                'start_time' => $sample['start'],
                'end_time' => $sample['end'],
                'duration_minutes' => $duration,
                'total_amount' => $amount,
                'deposit_amount' => $amount * 0.3,
                'paid_amount' => $paid,
                'status' => $sample['status'],
                'payment_status' => $sample['payment'],
                'source' => 'admin',
                'note' => 'Dữ liệu mẫu',
                'expires_at' => $sample['status'] === 'pending' ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ];

            $this->db->table('bookings')->insert($this->filterColumns('bookings', $bookingData + [
                'net_amount' => $amount,
                'player_count' => 4,
                'auto_release_at' => $bookingData['expires_at'],
                'timeout_minutes' => 15,
            ]));
            $bookingId = (int) $this->db->insertID();

            $courtId = $courtIds[$sample['court']] ?? $courtIds[0];
            $this->db->table('booking_items')->insert($this->filterColumns('booking_items', [
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'court_id' => $courtId,
                'court_name' => 'Sân mẫu',
                'date' => $sample['date'],
                'start_time' => $sample['start'],
                'end_time' => $sample['end'],
                'price' => $amount,
                'base_price' => $amount,
                'dynamic_price' => $amount,
                'status' => $sample['status'] === 'cancelled' ? 'cancelled' : 'active',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));

            if ($this->db->tableExists('booking_qr_codes')) {
                $this->db->table('booking_qr_codes')->insert($this->filterColumns('booking_qr_codes', [
                    'tenant_id' => $tenantId,
                    'booking_id' => $bookingId,
                    'qr_token' => bin2hex(random_bytes(24)),
                    'expired_at' => $sample['date'] . ' 23:59:59',
                    'status' => $sample['status'] === 'cancelled' ? 'revoked' : 'active',
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]));
            }

            if ($this->db->tableExists('booking_logs')) {
                $this->db->table('booking_logs')->insert($this->filterColumns('booking_logs', [
                    'tenant_id' => $tenantId,
                    'booking_id' => $bookingId,
                    'action' => 'created',
                    'old_status' => null,
                    'new_status' => $sample['status'],
                    'message' => 'Tạo dữ liệu mẫu',
                    'created_by' => 1,
                    'created_at' => $this->now,
                ]));
            }
        }
    }

    private function filterColumns(string $table, array $data): array
    {
        $fields = $this->db->getFieldNames($table);
        return array_intersect_key($data, array_flip($fields));
    }

    private function removeVietnamese(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = preg_replace('/[^A-Za-z0-9 ]/', '', $value ?? '');
        return trim((string) $value);
    }
}
