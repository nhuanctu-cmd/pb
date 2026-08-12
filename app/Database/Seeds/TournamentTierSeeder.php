<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TournamentTierSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $db = $this->db;

        $tiers = [
            ['code' => 'tier_s', 'name_vi' => 'Tier S', 'name_en' => 'Tier S', 'point_multiplier' => 10.00, 'default_rating_weight' => 1.25, 'sort_order' => 1],
            ['code' => 'tier_a', 'name_vi' => 'Tier A', 'name_en' => 'Tier A', 'point_multiplier' => 8.00, 'default_rating_weight' => 1.10, 'sort_order' => 2],
            ['code' => 'tier_b', 'name_vi' => 'Tier B', 'name_en' => 'Tier B', 'point_multiplier' => 5.00, 'default_rating_weight' => 1.00, 'sort_order' => 3],
            ['code' => 'tier_c', 'name_vi' => 'Tier C', 'name_en' => 'Tier C', 'point_multiplier' => 3.00, 'default_rating_weight' => 0.80, 'sort_order' => 4],
            ['code' => 'tier_d', 'name_vi' => 'Tier D', 'name_en' => 'Tier D', 'point_multiplier' => 1.00, 'default_rating_weight' => 0.60, 'sort_order' => 5],
        ];

        foreach ($tiers as $tier) {
            $existing = $db->table('tournament_tiers')->where('code', $tier['code'])->where('tenant_id', null)->get()->getRowArray();
            if ($existing) {
                continue;
            }
            $db->table('tournament_tiers')->insert(array_merge($tier, [
                'tenant_id' => null,
                'ranking_authority_id' => null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        echo "Tournament tiers seeded.\n";
    }
}
