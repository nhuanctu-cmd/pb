<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PlayerMembershipSeeder extends Seeder
{
    private string $now;

    public function run()
    {
        $this->now = date('Y-m-d H:i:s');

        foreach (['tenants', 'players', 'membership_packages', 'memberships', 'player_wallets', 'wallet_transactions', 'player_statistics'] as $table) {
            if (! $this->db->tableExists($table)) {
                echo "Missing table {$table}. Run migrations first.\n";
                return;
            }
        }

        $tenantId = $this->getTenantId();
        if (! $tenantId) {
            echo "No tenant found. Run CoreSeeder or DemoDataSeeder first.\n";
            return;
        }

        $packages = $this->seedPackages($tenantId);
        $players = $this->seedPlayers($tenantId);
        $this->seedMemberships($tenantId, $players, $packages);
        $this->seedWallets($tenantId, $players);
        $this->seedRanking($tenantId, $players);

        echo "Seeded player and membership demo data: 500 players, 5 packages, wallets, transactions, ranking.\n";
    }

    private function getTenantId(): ?int
    {
        $demo = $this->db->table('tenants')->where('code', 'DEMO-PB')->get()->getRow();
        if ($demo) {
            return (int) $demo->id;
        }

        $tenant = $this->db->table('tenants')->orderBy('id', 'ASC')->get(1)->getRow();
        return $tenant ? (int) $tenant->id : null;
    }

    private function seedPackages(int $tenantId): array
    {
        $packages = [
            ['name_vi' => 'Hoi vien 1 thang', 'name_en' => '1-Month Member', 'duration_days' => 30, 'price' => 500000, 'discount_percent' => 5, 'booking_priority' => 1],
            ['name_vi' => 'Hoi vien 3 thang', 'name_en' => '3-Month Member', 'duration_days' => 90, 'price' => 1350000, 'discount_percent' => 10, 'booking_priority' => 2],
            ['name_vi' => 'Hoi vien 6 thang', 'name_en' => '6-Month Member', 'duration_days' => 180, 'price' => 2500000, 'discount_percent' => 15, 'booking_priority' => 3],
            ['name_vi' => 'Hoi vien 1 nam', 'name_en' => 'Annual Member', 'duration_days' => 365, 'price' => 4600000, 'discount_percent' => 20, 'booking_priority' => 4],
            ['name_vi' => 'Hoi vien Pro', 'name_en' => 'Pro Member', 'duration_days' => 365, 'price' => 8000000, 'discount_percent' => 25, 'booking_priority' => 5],
        ];

        $ids = [];
        foreach ($packages as $package) {
            $row = $this->db->table('membership_packages')
                ->where('tenant_id', $tenantId)
                ->where('name_en', $package['name_en'])
                ->get()
                ->getRow();

            if (! $row) {
                $this->db->table('membership_packages')->insert($this->filterColumns('membership_packages', $package + [
                    'tenant_id' => $tenantId,
                    'status' => 'active',
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

    private function seedPlayers(int $tenantId): array
    {
        $existing = $this->db->table('players')
            ->select('id')
            ->where('tenant_id', $tenantId)
            ->like('player_code', 'DEMO-P', 'after')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (count($existing) >= 500) {
            return array_map('intval', array_column($existing, 'id'));
        }

        $firstNames = ['Minh', 'Anh', 'Bao', 'Chi', 'Dung', 'Ha', 'Huy', 'Khanh', 'Linh', 'Nam', 'Nhi', 'Phong', 'Quan', 'Thao', 'Trang', 'Tuan', 'Vy'];
        $lastNames = ['Nguyen', 'Tran', 'Le', 'Pham', 'Hoang', 'Huynh', 'Phan', 'Vu', 'Vo', 'Dang', 'Bui', 'Do', 'Ho', 'Ngo'];
        $levels = ['beginner', 'intermediate', 'advanced', 'pro'];
        $regions = ['District 1', 'District 2', 'District 7', 'Binh Thanh', 'Thu Duc'];

        $existingIds = array_map('intval', array_column($existing, 'id'));
        for ($i = count($existing) + 1; $i <= 500; $i++) {
            $level = $levels[$i % count($levels)];
            $rating = match ($level) {
                'pro' => random_int(1600, 2100),
                'advanced' => random_int(1300, 1599),
                'intermediate' => random_int(1000, 1299),
                default => random_int(700, 999),
            };

            $name = $lastNames[$i % count($lastNames)] . ' ' . $firstNames[$i % count($firstNames)] . ' ' . str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $this->db->table('players')->insert($this->filterColumns('players', [
                'tenant_id' => $tenantId,
                'player_code' => 'DEMO-P' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'full_name' => $name,
                'phone' => '0988' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'email' => 'player' . $i . '@demo-pickleball.vn',
                'gender' => ['male', 'female', 'other'][$i % 3],
                'birthday' => date('Y-m-d', strtotime('-' . random_int(18, 55) . ' years -' . random_int(0, 365) . ' days')),
                'avatar' => null,
                'region' => $regions[$i % count($regions)],
                'level' => $level,
                'rating_score' => $rating,
                'checkin_streak' => random_int(0, 14),
                'best_checkin_streak' => random_int(3, 30),
                'mvp_count' => random_int(0, 8),
                'status' => $i % 25 === 0 ? 'inactive' : 'active',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));
            $existingIds[] = (int) $this->db->insertID();
        }

        return $existingIds;
    }

    private function seedMemberships(int $tenantId, array $players, array $packages): void
    {
        if (empty($packages)) {
            return;
        }

        foreach ($players as $index => $playerId) {
            $exists = $this->db->table('memberships')
                ->where('tenant_id', $tenantId)
                ->where('player_id', $playerId)
                ->countAllResults();

            if ($exists > 0 || $index % 3 === 2) {
                continue;
            }

            $status = $index % 10 === 0 ? 'expired' : ($index % 13 === 0 ? 'cancelled' : 'active');
            $startDate = $status === 'expired'
                ? date('Y-m-d', strtotime('-120 days'))
                : date('Y-m-d', strtotime('-' . random_int(0, 20) . ' days'));
            $duration = $status === 'expired' ? 30 : [30, 90, 180, 365][$index % 4];
            $endDate = date('Y-m-d', strtotime($startDate . ' +' . $duration . ' days'));

            $this->db->table('memberships')->insert($this->filterColumns('memberships', [
                'tenant_id' => $tenantId,
                'player_id' => $playerId,
                'package_id' => $packages[$index % count($packages)],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]));
        }
    }

    private function seedWallets(int $tenantId, array $players): void
    {
        foreach ($players as $index => $playerId) {
            $wallet = $this->db->table('player_wallets')
                ->where('tenant_id', $tenantId)
                ->where('player_id', $playerId)
                ->get()
                ->getRow();

            if (! $wallet) {
                $balance = random_int(0, 20) * 50000;
                $this->db->table('player_wallets')->insert($this->filterColumns('player_wallets', [
                    'tenant_id' => $tenantId,
                    'player_id' => $playerId,
                    'balance' => $balance,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]));
                $walletId = (int) $this->db->insertID();
            } else {
                $walletId = (int) $wallet->id;
                $balance = (float) $wallet->balance;
            }

            $hasTxn = $this->db->table('wallet_transactions')->where('wallet_id', $walletId)->countAllResults();
            if ($hasTxn > 0) {
                continue;
            }

            $before = 0;
            $topup = max(100000, (float) $balance + 100000);
            $this->insertWalletTxn($tenantId, $playerId, $walletId, 'topup', $topup, $before, $topup, 'Sample topup');

            $finalBalance = $topup;
            if ($index % 2 === 0) {
                $pay = min(150000, $topup);
                $this->insertWalletTxn($tenantId, $playerId, $walletId, 'payment', $pay, $topup, $topup - $pay, 'Sample court payment');
                $finalBalance = $topup - $pay;
            }

            $this->db->table('player_wallets')->where('id', $walletId)->update($this->filterColumns('player_wallets', [
                'balance' => $finalBalance,
                'updated_at' => $this->now,
            ]));
        }
    }

    private function insertWalletTxn(int $tenantId, int $playerId, int $walletId, string $type, float $amount, float $before, float $after, string $note): void
    {
        $this->db->table('wallet_transactions')->insert($this->filterColumns('wallet_transactions', [
            'tenant_id' => $tenantId,
            'player_id' => $playerId,
            'wallet_id' => $walletId,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'ref_type' => 'seeder',
            'ref_id' => null,
            'note' => $note,
            'created_at' => $this->now,
            'updated_at' => $this->now,
        ]));
    }

    private function seedRanking(int $tenantId, array $players): void
    {
        foreach ($players as $playerId) {
            $player = $this->db->table('players')->where('id', $playerId)->get()->getRow();
            if (! $player) {
                continue;
            }

            $matches = random_int(0, 80);
            $wins = $matches > 0 ? random_int(0, $matches) : 0;
            $losses = $matches - $wins;
            $winRate = $matches > 0 ? round(($wins / $matches) * 100, 2) : 0;
            $rating = (int) $player->rating_score;

            $stats = $this->db->table('player_statistics')
                ->where('tenant_id', $tenantId)
                ->where('player_id', $playerId)
                ->get()
                ->getRow();

            $data = $this->filterColumns('player_statistics', [
                'tenant_id' => $tenantId,
                'player_id' => $playerId,
                'elo_rating' => $rating,
                'ranking_points' => max(0, $rating - 800),
                'total_matches' => $matches,
                'total_wins' => $wins,
                'total_losses' => $losses,
                'total_bookings' => random_int(0, 40),
                'checkin_count' => random_int(0, 80),
                'no_show_count' => random_int(0, 4),
                'win_rate' => $winRate,
                'current_streak' => (int) ($player->checkin_streak ?? 0),
                'best_streak' => (int) ($player->best_checkin_streak ?? 0),
                'mvp_count' => (int) ($player->mvp_count ?? 0),
                'achievements_count' => random_int(0, 6),
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);

            if ($stats) {
                unset($data['tenant_id'], $data['player_id'], $data['created_at']);
                $this->db->table('player_statistics')->where('id', $stats->id)->update($data);
            } else {
                $this->db->table('player_statistics')->insert($data);
            }

            if ($this->db->tableExists('player_ratings')) {
                $ratingRow = $this->db->table('player_ratings')
                    ->where('tenant_id', $tenantId)
                    ->where('player_id', $playerId)
                    ->where('scope_type', 'global')
                    ->get()
                    ->getRow();

                $ratingData = $this->filterColumns('player_ratings', [
                    'tenant_id' => $tenantId,
                    'player_id' => $playerId,
                    'scope_type' => 'global',
                    'scope_id' => null,
                    'region' => null,
                    'rating_type' => 'elo',
                    'rating' => $rating,
                    'games_played' => $matches,
                    'wins' => $wins,
                    'losses' => $losses,
                    'last_match_at' => $matches > 0 ? date('Y-m-d H:i:s', strtotime('-' . random_int(1, 30) . ' days')) : null,
                    'created_at' => $this->now,
                    'updated_at' => $this->now,
                ]);

                if ($ratingRow) {
                    unset($ratingData['tenant_id'], $ratingData['player_id'], $ratingData['scope_type'], $ratingData['created_at']);
                    $this->db->table('player_ratings')->where('id', $ratingRow->id)->update($ratingData);
                } else {
                    $this->db->table('player_ratings')->insert($ratingData);
                }
            }
        }
    }

    private function filterColumns(string $table, array $data): array
    {
        $fields = $this->db->getFieldNames($table);
        return array_intersect_key($data, array_flip($fields));
    }
}
