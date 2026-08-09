<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DynamicPricingSeeder extends Seeder
{
    public function run()
    {
        if (! $this->db->tableExists('pricing_rules')) {
            return;
        }

        $tenantId = 1;
        $rules = [
            [
                'code' => 'REGULAR_HOURLY',
                'name_vi' => 'Giờ thường',
                'name_en' => 'Regular hourly',
                'priority' => 10,
                'price_type' => 'hourly',
                'price_amount' => 150000,
                'member_price_amount' => 130000,
            ],
            [
                'code' => 'MEMBER_STANDARD',
                'name_vi' => 'Giá hội viên',
                'name_en' => 'Member price',
                'priority' => 40,
                'price_type' => 'hourly',
                'price_amount' => 150000,
                'member_price_amount' => 120000,
            ],
            [
                'code' => 'WEEKEND_RATE',
                'name_vi' => 'Cuối tuần',
                'name_en' => 'Weekend rate',
                'priority' => 60,
                'price_type' => 'hourly',
                'price_amount' => 180000,
                'member_price_amount' => 155000,
                'day_of_week' => '6,7',
            ],
            [
                'code' => 'HAPPY_HOUR_10_14',
                'name_vi' => 'Happy hour 10:00-14:00',
                'name_en' => 'Happy hour 10AM-2PM',
                'priority' => 70,
                'price_type' => 'hourly',
                'price_amount' => 90000,
                'member_price_amount' => 80000,
                'start_time' => '10:00:00',
                'end_time' => '14:00:00',
            ],
            [
                'code' => 'PEAK_18_22',
                'name_vi' => 'Giờ cao điểm 18:00-22:00',
                'name_en' => 'Peak hours 6PM-10PM',
                'priority' => 80,
                'price_type' => 'hourly',
                'price_amount' => 220000,
                'member_price_amount' => 190000,
                'start_time' => '18:00:00',
                'end_time' => '22:00:00',
            ],
            [
                'code' => 'HOLIDAY_RATE',
                'name_vi' => 'Ngày lễ',
                'name_en' => 'Holiday rate',
                'priority' => 100,
                'price_type' => 'hourly',
                'price_amount' => 250000,
                'member_price_amount' => 220000,
                'is_holiday' => 1,
            ],
        ];

        foreach ($rules as $rule) {
            $existing = $this->db->table('pricing_rules')
                ->where('tenant_id', $tenantId)
                ->where('code', $rule['code'])
                ->where('deleted_at', null)
                ->get()
                ->getRow();

            $data = array_merge([
                'tenant_id' => $tenantId,
                'branch_id' => null,
                'court_type_id' => null,
                'court_id' => null,
                'start_date' => null,
                'end_date' => null,
                'start_time' => null,
                'end_time' => null,
                'day_of_week' => null,
                'is_holiday' => 0,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ], $rule);

            if ($existing) {
                unset($data['created_at']);
                $this->db->table('pricing_rules')->where('id', $existing->id)->update($data);
                $ruleId = (int) $existing->id;
            } else {
                $this->db->table('pricing_rules')->insert($data);
                $ruleId = (int) $this->db->insertID();
            }

            $this->syncConditions($tenantId, $ruleId, $rule);
        }

        $this->seedSampleMembership($tenantId);
        $this->seedSampleDynamicBookings($tenantId);
    }

    private function syncConditions(int $tenantId, int $ruleId, array $rule): void
    {
        $this->db->table('pricing_rule_conditions')->where('pricing_rule_id', $ruleId)->delete();

        if (! empty($rule['day_of_week'])) {
            $this->db->table('pricing_rule_conditions')->insert([
                'tenant_id' => $tenantId,
                'pricing_rule_id' => $ruleId,
                'condition_type' => 'weekday',
                'operator' => 'in',
                'value' => $rule['day_of_week'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (! empty($rule['start_time']) && ! empty($rule['end_time'])) {
            $this->db->table('pricing_rule_conditions')->insert([
                'tenant_id' => $tenantId,
                'pricing_rule_id' => $ruleId,
                'condition_type' => 'time_range',
                'operator' => 'between',
                'value' => substr($rule['start_time'], 0, 5),
                'value_to' => substr($rule['end_time'], 0, 5),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (! empty($rule['is_holiday'])) {
            $this->db->table('pricing_rule_conditions')->insert([
                'tenant_id' => $tenantId,
                'pricing_rule_id' => $ruleId,
                'condition_type' => 'holiday',
                'operator' => 'equals',
                'value' => '1',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($rule['code'] === 'MEMBER_STANDARD') {
            $this->db->table('pricing_rule_conditions')->insert([
                'tenant_id' => $tenantId,
                'pricing_rule_id' => $ruleId,
                'condition_type' => 'member_level',
                'operator' => 'equals',
                'value' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function seedSampleMembership(int $tenantId): void
    {
        if (! $this->db->tableExists('players') || ! $this->db->tableExists('membership_packages') || ! $this->db->tableExists('memberships')) {
            return;
        }

        $player = $this->db->table('players')->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->get()->getRow();
        $package = $this->db->table('membership_packages')->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->get()->getRow();
        if (! $player || ! $package) {
            return;
        }

        $existing = $this->db->table('memberships')
            ->where('tenant_id', $tenantId)
            ->where('player_id', $player->id)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        $data = [
            'tenant_id' => $tenantId,
            'player_id' => $player->id,
            'package_id' => $package->id,
            'start_date' => date('Y-m-d', strtotime('-7 days')),
            'end_date' => date('Y-m-d', strtotime('+60 days')),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            unset($data['created_at']);
            $this->db->table('memberships')->where('id', $existing->id)->update($data);
        } else {
            $this->db->table('memberships')->insert($data);
        }
    }

    private function seedSampleDynamicBookings(int $tenantId): void
    {
        if (! $this->db->tableExists('bookings') || ! $this->db->tableExists('booking_items') || ! $this->db->tableExists('courts')) {
            return;
        }

        $branch = $this->db->table('branches')->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->get()->getRow();
        $court = $this->db->table('courts')->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->get()->getRow();
        $member = $this->db->table('memberships')->where('tenant_id', $tenantId)->where('status', 'active')->where('deleted_at', null)->get()->getRow();
        $player = $member
            ? $this->db->table('players')->where('id', $member->player_id)->get()->getRow()
            : $this->db->table('players')->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->get()->getRow();

        if (! $branch || ! $court) {
            return;
        }

        $samples = [
            [
                'code' => 'DEMO-DP-REGULAR',
                'name' => 'Khach gio thuong',
                'phone' => '0901000001',
                'date' => '2026-07-06',
                'start' => '15:00:00',
                'end' => '16:00:00',
                'player_id' => null,
            ],
            [
                'code' => 'DEMO-DP-PEAK',
                'name' => 'Khach gio cao diem',
                'phone' => '0901000002',
                'date' => '2026-07-06',
                'start' => '18:00:00',
                'end' => '19:00:00',
                'player_id' => null,
            ],
            [
                'code' => 'DEMO-DP-WEEKEND',
                'name' => 'Khach cuoi tuan',
                'phone' => '0901000003',
                'date' => '2026-07-11',
                'start' => '15:00:00',
                'end' => '16:00:00',
                'player_id' => null,
            ],
            [
                'code' => 'DEMO-DP-MEMBER',
                'name' => $player->full_name ?? 'Hoi vien demo',
                'phone' => $player->phone ?? '0901000004',
                'date' => '2026-07-06',
                'start' => '15:00:00',
                'end' => '16:00:00',
                'player_id' => $member->player_id ?? null,
            ],
            [
                'code' => 'DEMO-DP-HOLIDAY',
                'name' => 'Khach ngay le',
                'phone' => '0901000005',
                'date' => '2026-09-02',
                'start' => '15:00:00',
                'end' => '16:00:00',
                'player_id' => null,
            ],
            [
                'code' => 'DEMO-DP-HAPPY',
                'name' => 'Khach happy hour',
                'phone' => '0901000006',
                'date' => '2026-07-06',
                'start' => '10:00:00',
                'end' => '11:00:00',
                'player_id' => null,
            ],
        ];

        $pricingService = new \App\Services\PricingService();

        foreach ($samples as $sample) {
            $duration = (strtotime($sample['end']) - strtotime($sample['start'])) / 60;
            $existing = $this->db->table('bookings')->where('booking_code', $sample['code'])->get()->getRow();
            $bookingData = [
                'tenant_id' => $tenantId,
                'branch_id' => $branch->id,
                'player_id' => $sample['player_id'],
                'customer_name' => $sample['name'],
                'customer_phone' => $sample['phone'],
                'customer_email' => strtolower(str_replace('DEMO-DP-', '', $sample['code'])) . '@demo.local',
                'booking_code' => $sample['code'],
                'booking_date' => $sample['date'],
                'start_time' => $sample['start'],
                'end_time' => $sample['end'],
                'duration_minutes' => $duration,
                'total_amount' => 0,
                'deposit_amount' => 0,
                'paid_amount' => 0,
                'status' => 'reserved',
                'payment_status' => 'unpaid',
                'source' => 'admin',
                'note' => 'Du lieu mau dynamic pricing',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $bookingId = (int) $existing->id;
                unset($bookingData['booking_code'], $bookingData['created_at']);
                $this->db->table('bookings')->where('id', $bookingId)->update($bookingData);
                $this->db->table('booking_items')->where('booking_id', $bookingId)->delete();
                $this->db->table('dynamic_price_logs')->where('booking_id', $bookingId)->delete();
            } else {
                $this->db->table('bookings')->insert($bookingData);
                $bookingId = (int) $this->db->insertID();
            }

            $price = $pricingService->getPrice(
                $tenantId,
                (int) $branch->id,
                (int) $court->id,
                $sample['date'],
                $sample['start'],
                $sample['end'],
                $sample['player_id'] ? (int) $sample['player_id'] : null,
                $bookingId
            );

            $finalPrice = (float) ($price['final_price'] ?? 0);
            $this->db->table('booking_items')->insert([
                'tenant_id' => $tenantId,
                'booking_id' => $bookingId,
                'court_id' => $court->id,
                'start_time' => $sample['start'],
                'end_time' => $sample['end'],
                'price' => $finalPrice,
                'base_price' => (float) ($price['base_price'] ?? 0),
                'dynamic_price' => $finalPrice,
                'pricing_detail' => json_encode($price['breakdown'] ?? [], JSON_UNESCAPED_UNICODE),
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('bookings')->where('id', $bookingId)->update([
                'total_amount' => $finalPrice,
                'deposit_amount' => $finalPrice * 0.3,
                'pricing_rule_id' => $price['selected_rule']->id ?? null,
                'price_breakdown' => json_encode($price['breakdown'] ?? [], JSON_UNESCAPED_UNICODE),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
