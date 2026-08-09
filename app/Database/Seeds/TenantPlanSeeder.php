<?php

namespace App\Database\Seeds;

use App\Services\TenantPlanService;
use CodeIgniter\Database\Seeder;

/**
 * Seed gói dịch vụ SaaS + gán gói cho tenant demo
 */
class TenantPlanSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // Bỏ qua nếu đã seed
        if ($this->db->table('tenant_plans')->countAllResults() > 0) {
            echo "tenant_plans đã có dữ liệu — bỏ qua.\n";
            return;
        }

        $plans = [
            [
                'code'           => 'free',
                'name_vi'        => 'Miễn phí',
                'name_en'        => 'Free',
                'description_vi' => 'Gói khởi đầu cho sân nhỏ: đặt sân, quản lý sân cơ bản.',
                'description_en' => 'Starter plan for small courts: bookings, basic court management.',
                'max_branches'   => 1,
                'max_courts'     => 4,
                'max_players'    => 100,
                'max_staff'      => 3,
                'price_monthly'  => 0,
                'price_yearly'   => 0,
                'features'       => json_encode(['booking', 'court']),
            ],
            [
                'code'           => 'pro',
                'name_vi'        => 'Chuyên nghiệp',
                'name_en'        => 'Pro',
                'description_vi' => 'Đầy đủ vận hành: POS, giải đấu, ví người chơi, báo cáo.',
                'description_en' => 'Full operations: POS, tournaments, player wallets, reports.',
                'max_branches'   => 3,
                'max_courts'     => 20,
                'max_players'    => 2000,
                'max_staff'      => 20,
                'price_monthly'  => 499000,
                'price_yearly'   => 4990000,
                'features'       => json_encode(['booking', 'court', 'pos', 'tournament', 'wallet', 'membership', 'report']),
            ],
            [
                'code'           => 'enterprise',
                'name_vi'        => 'Doanh nghiệp',
                'name_en'        => 'Enterprise',
                'description_vi' => 'Không giới hạn + AI scheduling, API, tích hợp Zalo.',
                'description_en' => 'Unlimited + AI scheduling, API access, Zalo integration.',
                'max_branches'   => -1,
                'max_courts'     => -1,
                'max_players'    => -1,
                'max_staff'      => -1,
                'price_monthly'  => 1999000,
                'price_yearly'   => 19990000,
                'features'       => json_encode(['*']),
            ],
        ];

        foreach ($plans as $plan) {
            $plan['is_active']  = 1;
            $plan['created_at'] = $now;
            $plan['updated_at'] = $now;
            $this->db->table('tenant_plans')->insert($plan);
        }

        // Gán gói cho tenant demo
        $planService = new TenantPlanService();
        $proId  = (int) $this->db->table('tenant_plans')->where('code', 'pro')->get()->getRow('id');
        $freeId = (int) $this->db->table('tenant_plans')->where('code', 'free')->get()->getRow('id');

        if ($proId && $this->db->table('tenants')->where('id', 1)->countAllResults()) {
            $planService->subscribe(1, $proId, 'active');
        }
        if ($freeId && $this->db->table('tenants')->where('id', 2)->countAllResults()) {
            $planService->subscribe(2, $freeId, 'trial', 30);
        }

        echo "Đã tạo 3 gói dịch vụ (free/pro/enterprise) + gán gói cho tenant demo.\n";
    }
}
