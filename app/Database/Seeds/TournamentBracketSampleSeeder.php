<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Fixture cây đấu cho các giải Demo Series.
 *
 * Dùng player_id như participant id cho nội dung đơn, để bracket có thể
 * hiển thị đúng tên VĐV mà không cần tạo đội giả cho nội dung single.
 */
class TournamentBracketSampleSeeder extends Seeder
{
    private string $now;

    public function run()
    {
        $this->now = date('Y-m-d H:i:s');
        foreach (['tenants', 'tournaments', 'tournament_categories', 'tournament_registrations', 'tournament_matches', 'tournament_brackets', 'players', 'courts'] as $table) {
            if (! $this->db->tableExists($table)) {
                echo "Bỏ qua TournamentBracketSampleSeeder: thiếu bảng {$table}.\n";
                return;
            }
        }

        $categories = $this->db->table('tournament_categories c')
            ->select('c.*, t.name_vi as tournament_name, t.start_date, t.end_date, t.branch_id')
            ->join('tournaments t', 't.id = c.tournament_id AND t.tenant_id = c.tenant_id', 'inner')
            ->where('c.category_type', 'single_male')
            ->like('t.name_vi', 'Demo Series', 'after')
            ->where('c.deleted_at', null)
            ->where('t.deleted_at', null)
            ->get()->getResult();

        $processed = 0;
        foreach ($categories as $category) {
            $registrations = $this->db->table('tournament_registrations r')
                ->select('r.player_id, r.contact_name, p.full_name, p.player_code, p.rating_score')
                ->join('players p', 'p.id = r.player_id AND p.tenant_id = r.tenant_id', 'left')
                ->where('r.tenant_id', (int) $category->tenant_id)
                ->where('r.tournament_id', (int) $category->tournament_id)
                ->where('r.category_id', (int) $category->id)
                ->where('r.approval_status', 'approved')
                ->where('r.registration_status', 'confirmed')
                ->where('r.deleted_at', null)
                ->where('r.player_id IS NOT NULL', null, false)
                ->orderBy('r.id', 'ASC')->get(8)->getResult();
            if (count($registrations) < 4) {
                continue;
            }

            $playerIds = array_map(static fn ($row): int => (int) $row->player_id, $registrations);
            $courtIds = array_map('intval', array_column($this->db->table('courts')
                ->select('id')->where('tenant_id', (int) $category->tenant_id)->where('branch_id', (int) $category->branch_id)
                ->whereIn('status', ['available', 'occupied'])->orderBy('sort_order', 'ASC')->get(4)->getResultArray(), 'id'));
            if (! $courtIds) {
                continue;
            }

            $this->seedBracket($category, $playerIds, $courtIds);
            $processed++;
        }

        echo "Đã tạo/cập nhật cây đấu mẫu cho {$processed} hạng mục Demo Series.\n";
    }

    private function seedBracket(object $category, array $playerIds, array $courtIds): void
    {
        $startDate = $category->start_date ?: date('Y-m-d', strtotime('+7 days'));
        $endDate = $category->end_date ?: $startDate;
        $definitions = [
            ['position' => 'R1-1', 'round' => 1, 'name' => 'Vòng 1', 'no' => 1, 'a' => $playerIds[0], 'b' => $playerIds[7] ?? null, 'winner' => $playerIds[0], 'status' => 'completed', 'court' => $courtIds[0], 'date' => $startDate, 'start' => '08:00:00'],
            ['position' => 'R1-2', 'round' => 1, 'name' => 'Vòng 1', 'no' => 2, 'a' => $playerIds[3] ?? null, 'b' => $playerIds[4] ?? null, 'winner' => $playerIds[4] ?? null, 'status' => 'completed', 'court' => $courtIds[1 % count($courtIds)], 'date' => $startDate, 'start' => '08:00:00'],
            ['position' => 'R1-3', 'round' => 1, 'name' => 'Vòng 1', 'no' => 3, 'a' => $playerIds[1] ?? null, 'b' => $playerIds[6] ?? null, 'winner' => $playerIds[1] ?? null, 'status' => 'completed', 'court' => $courtIds[2 % count($courtIds)], 'date' => $startDate, 'start' => '09:00:00'],
            ['position' => 'R1-4', 'round' => 1, 'name' => 'Vòng 1', 'no' => 4, 'a' => $playerIds[2] ?? null, 'b' => $playerIds[5] ?? null, 'winner' => $playerIds[2] ?? null, 'status' => 'completed', 'court' => $courtIds[3 % count($courtIds)], 'date' => $startDate, 'start' => '09:00:00'],
            ['position' => 'R2-1', 'round' => 2, 'name' => 'Bán kết', 'no' => 5, 'a' => $playerIds[0], 'b' => $playerIds[4] ?? null, 'winner' => null, 'status' => 'scheduled', 'court' => $courtIds[0], 'date' => $endDate, 'start' => '08:00:00'],
            ['position' => 'R2-2', 'round' => 2, 'name' => 'Bán kết', 'no' => 6, 'a' => $playerIds[1] ?? null, 'b' => $playerIds[2] ?? null, 'winner' => null, 'status' => 'scheduled', 'court' => $courtIds[1 % count($courtIds)], 'date' => $endDate, 'start' => '08:00:00'],
            ['position' => 'R3-1', 'round' => 3, 'name' => 'Chung kết', 'no' => 7, 'a' => null, 'b' => null, 'winner' => null, 'status' => 'scheduled', 'court' => $courtIds[2 % count($courtIds)], 'date' => $endDate, 'start' => '10:00:00'],
        ];
        $matchIds = [];
        foreach ($definitions as $definition) {
            $bracket = $this->db->table('tournament_brackets')
                ->where('tenant_id', (int) $category->tenant_id)->where('category_id', (int) $category->id)
                ->where('bracket_position', $definition['position'])->get(1)->getRow();
            $match = $bracket ? $this->db->table('tournament_matches')->where('id', (int) $bracket->match_id)->where('tenant_id', (int) $category->tenant_id)->get(1)->getRow() : null;
            $data = [
                'tenant_id' => (int) $category->tenant_id, 'tournament_id' => (int) $category->tournament_id, 'category_id' => (int) $category->id,
                'group_id' => null, 'round_name' => $definition['name'], 'match_no' => $definition['no'], 'court_id' => $definition['court'],
                'scheduled_date' => $definition['date'], 'start_time' => $definition['start'], 'end_time' => date('H:i:s', strtotime($definition['start'] . ' +45 minutes')),
                'team_a_id' => $definition['a'], 'team_b_id' => $definition['b'], 'winner_team_id' => $definition['winner'], 'status' => $definition['status'], 'is_locked' => 0,
                'updated_at' => $this->now,
            ];
            if (! $match) {
                $data['created_at'] = $this->now;
                $this->db->table('tournament_matches')->insert($this->filter('tournament_matches', $data));
                $matchId = (int) $this->db->insertID();
            } else {
                $matchId = (int) $match->id;
                $this->db->table('tournament_matches')->where('id', $matchId)->where('tenant_id', (int) $category->tenant_id)->update($this->filter('tournament_matches', $data));
            }
            $matchIds[$definition['position']] = $matchId;
            $bracketData = ['tenant_id' => (int) $category->tenant_id, 'tournament_id' => (int) $category->tournament_id, 'category_id' => (int) $category->id, 'match_id' => $matchId, 'bracket_position' => $definition['position'], 'round_no' => $definition['round'], 'updated_at' => $this->now];
            if (! $bracket) {
                $bracketData['created_at'] = $this->now;
                $this->db->table('tournament_brackets')->insert($this->filter('tournament_brackets', $bracketData));
            } else {
                $this->db->table('tournament_brackets')->where('id', (int) $bracket->id)->update($this->filter('tournament_brackets', $bracketData));
            }
        }
        foreach (['R1-1' => 'R2-1', 'R1-2' => 'R2-1', 'R1-3' => 'R2-2', 'R1-4' => 'R2-2', 'R2-1' => 'R3-1', 'R2-2' => 'R3-1'] as $from => $to) {
            $this->db->table('tournament_brackets')->where('tenant_id', (int) $category->tenant_id)->where('category_id', (int) $category->id)->where('bracket_position', $from)->update(['next_match_id' => $matchIds[$to], 'updated_at' => $this->now]);
        }
        foreach (['R2-1' => 'R1-1', 'R2-2' => 'R1-3', 'R3-1' => 'R2-1'] as $parent => $child) {
            $this->db->table('tournament_brackets')->where('tenant_id', (int) $category->tenant_id)->where('category_id', (int) $category->id)->where('bracket_position', $parent)->update(['parent_match_id' => $matchIds[$child], 'updated_at' => $this->now]);
        }
    }

    private function filter(string $table, array $data): array
    {
        return array_intersect_key($data, array_flip($this->db->getFieldNames($table)));
    }
}
