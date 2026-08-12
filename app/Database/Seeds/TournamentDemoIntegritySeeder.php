<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Chuẩn hóa fixture Demo Summer Cup thành một giải có dữ liệu nghiệp vụ
 * nhất quán: đội, đăng ký, lịch và cây knockout 4 đội.
 *
 * Seeder chỉ cập nhật các bản ghi có khóa demo-summer-cup/demo-tournament,
 * không xóa hoặc chạm vào dữ liệu vận hành của người dùng.
 */
class TournamentDemoIntegritySeeder extends Seeder
{
    private string $now;

    public function run()
    {
        $this->now = date('Y-m-d H:i:s');

        foreach (['tenants', 'tournaments', 'tournament_categories', 'tournament_matches', 'tournament_brackets', 'teams', 'players', 'courts'] as $table) {
            if (! $this->db->tableExists($table)) {
                echo "Bỏ qua TournamentDemoIntegritySeeder: thiếu bảng {$table}.\n";
                return;
            }
        }

        $tenants = $this->db->table('tenants')
            ->where('is_active', 1)
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResult();

        $processed = 0;
        foreach ($tenants as $tenant) {
            if ($this->seedTenant((int) $tenant->id)) {
                $processed++;
            }
        }

        echo "Đã chuẩn hóa dữ liệu giải đấu demo và cây knockout cho {$processed} tenant.\n";
    }

    private function seedTenant(int $tenantId): bool
    {
        $tournament = $this->db->table('tournaments')
            ->where('tenant_id', $tenantId)
            ->where('slug_vi', 'demo-summer-cup')
            ->where('deleted_at', null)
            ->get(1)
            ->getRow();
        if (! $tournament) {
            return false;
        }

        $category = $this->db->table('tournament_categories')
            ->where('tenant_id', $tenantId)
            ->where('tournament_id', (int) $tournament->id)
            ->where('name_vi', 'Đôi nam nữ phong trào')
            ->where('deleted_at', null)
            ->get(1)
            ->getRow();
        if (! $category) {
            return false;
        }

        $players = $this->db->table('players')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get(8)
            ->getResult();
        $courts = $this->db->table('courts')
            ->select('id')
            ->where('tenant_id', $tenantId)
            ->where('branch_id', (int) ($tournament->branch_id ?? 0))
            ->whereIn('status', ['available', 'occupied'])
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get(6)
            ->getResultArray();
        if (count($players) < 8 || count($courts) < 4) {
            return false;
        }

        $startDate = date('Y-m-d', strtotime('+7 days'));
        $endDate = date('Y-m-d', strtotime('+8 days'));
        $this->db->table('tournaments')->where('id', (int) $tournament->id)->where('tenant_id', $tenantId)->update($this->filterColumns('tournaments', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
            'verification_level' => 'verified',
            'description_vi' => 'Giải đấu mẫu 4 đội với đăng ký, lịch thi đấu, cây knockout và kết quả công khai.',
            'description_en' => 'Four-team demo tournament with registration, schedule, knockout bracket and public results.',
            'updated_at' => $this->now,
        ]));
        $this->db->table('tournament_categories')->where('id', (int) $category->id)->where('tenant_id', $tenantId)->update($this->filterColumns('tournament_categories', [
            'category_type' => 'mixed_double',
            'discipline' => 'mixed_doubles',
            'gender_category' => 'mixed',
            'team_size' => 2,
            'max_teams' => 4,
            'entry_capacity' => 4,
            'min_rating' => 800,
            'max_rating' => 1800,
            'registration_fee' => 250000,
            'eligibility_rules' => json_encode(['team_size' => 2, 'rating_range' => ['min' => 800, 'max' => 1800], 'approval_required' => true], JSON_UNESCAPED_UNICODE),
            'updated_at' => $this->now,
        ]));

        $teamIds = $this->ensureTournamentTeams($tenantId, (int) $tournament->id, $players);
        $this->ensureRegistrations($tenantId, (int) $tournament->id, (int) $category->id, $teamIds, $players);
        $matches = $this->ensureBracket($tenantId, (int) $tournament->id, (int) $category->id, $teamIds, array_map('intval', array_column($courts, 'id')), $startDate, $endDate);
        $this->ensureOperationalFixture($tenantId, (int) $tournament->id, (int) $category->id, $teamIds);
        $this->ensureLiveDisplays($tenantId, (int) $tournament->id, $category->id);
        $this->ensureTournamentTemplates($tenantId, (int) $tournament->id);
        $this->refreshAiProposal($tenantId, (int) $tournament->id, $matches, count($courts));

        return true;
    }

    private function ensureTournamentTeams(int $tenantId, int $tournamentId, array $players): array
    {
        $definitions = [
            ['name' => 'Demo Aces', 'rating' => 1680, 'captain' => 0, 'partner' => 1],
            ['name' => 'Demo Spin Masters', 'rating' => 1540, 'captain' => 2, 'partner' => 3],
            ['name' => 'Demo Topspin', 'rating' => 1380, 'captain' => 4, 'partner' => 5],
            ['name' => 'Demo Drop Shot', 'rating' => 1220, 'captain' => 6, 'partner' => 7],
        ];
        $teamIds = [];
        foreach ($definitions as $definition) {
            $row = $this->db->table('teams')
                ->where('tenant_id', $tenantId)
                ->where('team_name', $definition['name'])
                ->where('deleted_at', null)
                ->get(1)
                ->getRow();
            $data = [
                'tenant_id' => $tenantId,
                'team_name' => $definition['name'],
                'captain_player_id' => (int) $players[$definition['captain']]->id,
                'team_type' => 'mixed_double',
                'rating_avg' => $definition['rating'],
                'status' => 'active',
                'updated_at' => $this->now,
            ];
            if (! $row) {
                $data['created_at'] = $this->now;
                $this->db->table('teams')->insert($this->filterColumns('teams', $data));
                $teamId = (int) $this->db->insertID();
            } else {
                $teamId = (int) $row->id;
                $this->db->table('teams')->where('id', $teamId)->where('tenant_id', $tenantId)->update($this->filterColumns('teams', $data));
            }
            $teamIds[] = $teamId;

            if ($this->db->tableExists('team_members')) {
                foreach ([$definition['captain'], $definition['partner']] as $memberIndex => $playerIndex) {
                    $member = $this->db->table('team_members')
                        ->where('tenant_id', $tenantId)
                        ->where('team_id', $teamId)
                        ->where('player_id', (int) $players[$playerIndex]->id)
                        ->get(1)
                        ->getRow();
                    $memberData = [
                        'tenant_id' => $tenantId,
                        'team_id' => $teamId,
                        'player_id' => (int) $players[$playerIndex]->id,
                        'role' => $memberIndex === 0 ? 'captain' : 'member',
                        'status' => 'accepted',
                        'updated_at' => $this->now,
                    ];
                    if (! $member) {
                        $memberData['created_at'] = $this->now;
                        $this->db->table('team_members')->insert($this->filterColumns('team_members', $memberData));
                    } else {
                        $this->db->table('team_members')->where('id', (int) $member->id)->update($this->filterColumns('team_members', $memberData));
                    }
                }
            }
        }

        return $teamIds;
    }

    private function ensureRegistrations(int $tenantId, int $tournamentId, int $categoryId, array $teamIds, array $players): void
    {
        $existingRows = $this->db->table('tournament_registrations')
            ->where('tenant_id', $tenantId)
            ->where('tournament_id', $tournamentId)
            ->where('category_id', $categoryId)
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get(4)
            ->getResult();
        $usedIds = [];

        foreach ($teamIds as $index => $teamId) {
            $captain = $players[$index * 2];
            $partner = $players[$index * 2 + 1];
            // Tái sử dụng các dòng đăng ký demo cũ để không tạo bản ghi
            // trùng khi nâng cấp fixture từ player-only sang team registration.
            $row = $existingRows[$index] ?? null;
            if (! $row) {
                $row = $this->db->table('tournament_registrations')
                    ->where('tenant_id', $tenantId)
                    ->where('tournament_id', $tournamentId)
                    ->where('category_id', $categoryId)
                    ->where('team_id', $teamId)
                    ->where('deleted_at', null)
                    ->get(1)
                    ->getRow();
            }
            $data = [
                'tenant_id' => $tenantId,
                'tournament_id' => $tournamentId,
                'category_id' => $categoryId,
                'player_id' => (int) $captain->id,
                'team_id' => $teamId,
                'partner_player_id' => (int) $partner->id,
                'contact_name' => (string) $captain->full_name . ' / ' . (string) $partner->full_name,
                'contact_phone' => (string) ($captain->phone ?: '0900000000'),
                'payment_status' => 'paid',
                'approval_status' => 'approved',
                'registration_status' => 'confirmed',
                'eligibility_status' => 'passed',
                'checkin_status' => $index === 0 ? 'checked_in' : ($index === 3 ? 'no_show' : 'pending'),
                'checked_in_at' => $index === 0 ? $this->now : null,
                'no_show' => $index === 3 ? 1 : 0,
                'invoice_code' => 'INV-DEMO-SUMMER-TEAM-' . ($index + 1),
                'invoice_amount' => 250000,
                'updated_at' => $this->now,
            ];
            if (! $row) {
                $data['created_at'] = $this->now;
                $this->db->table('tournament_registrations')->insert($this->filterColumns('tournament_registrations', $data));
                $usedIds[] = (int) $this->db->insertID();
            } else {
                $usedIds[] = (int) $row->id;
                $this->db->table('tournament_registrations')->where('id', (int) $row->id)->update($this->filterColumns('tournament_registrations', $data));
            }

            if ($this->db->tableExists('tournament_checkins') && in_array($index, [0, 3], true)) {
                $checkinStatus = $index === 0 ? 'checked_in' : 'no_show';
                $checkin = $this->db->table('tournament_checkins')
                    ->where('tenant_id', $tenantId)
                    ->where('registration_id', (int) $usedIds[array_key_last($usedIds)])
                    ->where('player_id', (int) $captain->id)
                    ->get(1)->getRow();
                $checkinData = [
                    'tenant_id' => $tenantId, 'tournament_id' => $tournamentId, 'category_id' => $categoryId,
                    'registration_id' => (int) $usedIds[array_key_last($usedIds)], 'player_id' => (int) $captain->id,
                    'status' => $checkinStatus, 'checked_in_at' => $index === 0 ? $this->now : null,
                    'updated_at' => $this->now,
                ];
                if ($checkin) {
                    $this->db->table('tournament_checkins')->where('id', (int) $checkin->id)->update($this->filterColumns('tournament_checkins', $checkinData));
                } else {
                    $checkinData['created_at'] = $this->now;
                    $this->db->table('tournament_checkins')->insert($this->filterColumns('tournament_checkins', $checkinData));
                }
            }
        }

        // Dọn các bản ghi trùng do các phiên bản demo cũ đã từng tạo thêm đội.
        // Chỉ soft-delete đúng mã hóa đơn fixture này, không ảnh hưởng đăng ký thật.
        if ($usedIds && $this->db->fieldExists('deleted_at', 'tournament_registrations')) {
            $this->db->table('tournament_registrations')
                ->where('tenant_id', $tenantId)
                ->where('tournament_id', $tournamentId)
                ->where('category_id', $categoryId)
                ->groupStart()
                ->like('invoice_code', 'INV-DEMO-SUMMER-TEAM-', 'after')
                ->orLike('invoice_code', 'INV-DEMO-TOUR-', 'after')
                ->groupEnd()
                ->whereNotIn('id', $usedIds)
                ->where('deleted_at', null)
                ->update(['deleted_at' => $this->now, 'updated_at' => $this->now]);
        }
    }

    private function ensureBracket(int $tenantId, int $tournamentId, int $categoryId, array $teamIds, array $courtIds, string $startDate, string $endDate): array
    {
        $definitions = [
            ['position' => 'R1-1', 'round' => 1, 'name' => 'Vòng 1', 'no' => 1, 'a' => $teamIds[0], 'b' => $teamIds[3], 'court' => $courtIds[0], 'date' => $startDate, 'start' => '08:00:00'],
            ['position' => 'R1-2', 'round' => 1, 'name' => 'Vòng 1', 'no' => 2, 'a' => $teamIds[1], 'b' => $teamIds[2], 'court' => $courtIds[1], 'date' => $startDate, 'start' => '08:00:00'],
            ['position' => 'R1-3', 'round' => 1, 'name' => 'Vòng 1', 'no' => 3, 'a' => $teamIds[0], 'b' => $teamIds[1], 'court' => $courtIds[2], 'date' => $startDate, 'start' => '09:30:00'],
            ['position' => 'R1-4', 'round' => 1, 'name' => 'Vòng 1', 'no' => 4, 'a' => $teamIds[2], 'b' => $teamIds[3], 'court' => $courtIds[3], 'date' => $startDate, 'start' => '09:30:00'],
            ['position' => 'R2-1', 'round' => 2, 'name' => 'Bán kết', 'no' => 5, 'a' => null, 'b' => null, 'court' => $courtIds[0], 'date' => $endDate, 'start' => '08:00:00'],
            ['position' => 'R2-2', 'round' => 2, 'name' => 'Bán kết', 'no' => 6, 'a' => null, 'b' => null, 'court' => $courtIds[1], 'date' => $endDate, 'start' => '08:00:00'],
            ['position' => 'R3-1', 'round' => 3, 'name' => 'Chung kết', 'no' => 7, 'a' => null, 'b' => null, 'court' => $courtIds[2], 'date' => $endDate, 'start' => '10:00:00'],
        ];
        $matchIds = [];
        foreach ($definitions as $definition) {
            $bracket = $this->db->table('tournament_brackets')
                ->where('tenant_id', $tenantId)
                ->where('tournament_id', $tournamentId)
                ->where('category_id', $categoryId)
                ->where('bracket_position', $definition['position'])
                ->get(1)
                ->getRow();
            $match = $bracket ? $this->db->table('tournament_matches')->where('id', (int) $bracket->match_id)->where('tenant_id', $tenantId)->get(1)->getRow() : null;
            if (! $match) {
                $match = $this->db->table('tournament_matches')
                    ->where('tenant_id', $tenantId)
                    ->where('tournament_id', $tournamentId)
                    ->where('category_id', $categoryId)
                    ->where('match_no', $definition['no'])
                    ->get(1)
                    ->getRow();
            }
            $matchData = [
                'tenant_id' => $tenantId,
                'tournament_id' => $tournamentId,
                'category_id' => $categoryId,
                'group_id' => null,
                'round_name' => $definition['name'],
                'match_no' => $definition['no'],
                'court_id' => $definition['court'],
                'scheduled_date' => $definition['date'],
                'start_time' => $definition['start'],
                'end_time' => date('H:i:s', strtotime($definition['start'] . ' +45 minutes')),
                'team_a_id' => $definition['a'],
                'team_b_id' => $definition['b'],
                'winner_team_id' => null,
                'unified_match_id' => null,
                'status' => 'scheduled',
                'is_locked' => 0,
                'updated_at' => $this->now,
            ];
            if (! $match) {
                $matchData['created_at'] = $this->now;
                $this->db->table('tournament_matches')->insert($this->filterColumns('tournament_matches', $matchData));
                $matchId = (int) $this->db->insertID();
            } else {
                $matchId = (int) $match->id;
                $this->db->table('tournament_matches')->where('id', $matchId)->where('tenant_id', $tenantId)->update($this->filterColumns('tournament_matches', $matchData));
            }
            $matchIds[$definition['position']] = $matchId;
            $bracketData = [
                'tenant_id' => $tenantId,
                'tournament_id' => $tournamentId,
                'category_id' => $categoryId,
                'match_id' => $matchId,
                'bracket_position' => $definition['position'],
                'round_no' => $definition['round'],
                'updated_at' => $this->now,
            ];
            if (! $bracket) {
                $bracketData['created_at'] = $this->now;
                $this->db->table('tournament_brackets')->insert($this->filterColumns('tournament_brackets', $bracketData));
            } else {
                $this->db->table('tournament_brackets')->where('id', (int) $bracket->id)->update($this->filterColumns('tournament_brackets', $bracketData));
            }
        }

        $links = [
            'R1-1' => 'R2-1', 'R1-2' => 'R2-1',
            'R1-3' => 'R2-2', 'R1-4' => 'R2-2',
            'R2-1' => 'R3-1', 'R2-2' => 'R3-1',
        ];
        foreach ($links as $from => $to) {
            $this->db->table('tournament_brackets')
                ->where('tenant_id', $tenantId)
                ->where('tournament_id', $tournamentId)
                ->where('category_id', $categoryId)
                ->where('bracket_position', $from)
                ->update(['next_match_id' => $matchIds[$to], 'updated_at' => $this->now]);
        }
        foreach (['R2-1' => 'R1-1', 'R2-2' => 'R1-3', 'R3-1' => 'R2-1'] as $parent => $child) {
            $this->db->table('tournament_brackets')
                ->where('tenant_id', $tenantId)
                ->where('tournament_id', $tournamentId)
                ->where('category_id', $categoryId)
                ->where('bracket_position', $parent)
                ->update(['parent_match_id' => $matchIds[$child], 'updated_at' => $this->now]);
        }

        // Trận đang chờ thi đấu không được giữ điểm/kết quả của fixture cũ.
        foreach (['tournament_match_scores', 'tournament_score_logs'] as $table) {
            if ($this->db->tableExists($table)) {
                $this->db->table($table)
                    ->where('tenant_id', $tenantId)
                    ->whereIn('match_id', array_values($matchIds))
                    ->delete();
            }
        }

        return array_values($matchIds);
    }

    /**
     * Tạo trạng thái vận hành đủ rộng để Control Room và TV/LED có dữ liệu
     * thật: một trận xong, một trận đang live, một trận gọi VĐV, một trận trễ
     * và các trận kế tiếp đang chờ.
     */
    private function ensureOperationalFixture(int $tenantId, int $tournamentId, int $categoryId, array $teamIds): void
    {
        $brackets = $this->db->table('tournament_brackets')
            ->where('tenant_id', $tenantId)->where('tournament_id', $tournamentId)->where('category_id', $categoryId)
            ->whereIn('bracket_position', ['R1-1', 'R1-2', 'R1-3', 'R1-4', 'R2-1', 'R2-2', 'R3-1'])
            ->get()->getResult();
        $positions = [];
        foreach ($brackets as $bracket) {
            $match = $this->db->table('tournament_matches')->where('id', (int) $bracket->match_id)->where('tenant_id', $tenantId)->get(1)->getRow();
            if ($match) $positions[(string) $bracket->bracket_position] = $match;
        }

        $states = [
            'R1-1' => ['status' => 'completed', 'winner' => $teamIds[0] ?? null, 'note' => 'Đã xác nhận kết quả tại bàn trọng tài.', 'score' => [11, 8]],
            'R1-2' => ['status' => 'running', 'winner' => null, 'note' => 'Đang thi đấu · cập nhật điểm trực tiếp.', 'score' => [7, 6]],
            'R1-3' => ['status' => 'called', 'winner' => null, 'note' => 'Đã gọi VĐV · chuẩn bị vào sân.', 'score' => null],
            'R1-4' => ['status' => 'delayed', 'winner' => null, 'note' => 'Trễ 15 phút · chờ xác nhận check-in.', 'score' => null],
            'R2-1' => ['status' => 'scheduled', 'winner' => null, 'note' => 'Chờ kết quả vòng trước.', 'score' => null],
            'R2-2' => ['status' => 'scheduled', 'winner' => null, 'note' => 'Chờ kết quả vòng trước.', 'score' => null],
            'R3-1' => ['status' => 'scheduled', 'winner' => null, 'note' => 'Chung kết · chờ nhánh đấu.', 'score' => null],
        ];

        foreach ($states as $position => $state) {
            $match = $positions[$position] ?? null;
            if (! $match) continue;
            $scheduledAt = strtotime((string) $match->scheduled_date . ' ' . (string) $match->start_time);
            $data = [
                'status' => $state['status'], 'winner_team_id' => $state['winner'], 'operations_note' => $state['note'],
                'called_at' => $state['status'] === 'called' ? date('Y-m-d H:i:s', $scheduledAt - 600) : null,
                'actual_start_time' => $state['status'] === 'running' ? date('Y-m-d H:i:s', $scheduledAt - 300) : null,
                'completed_at' => $state['status'] === 'completed' ? date('Y-m-d H:i:s', $scheduledAt + 2400) : null,
                'updated_at' => $this->now,
            ];
            $this->db->table('tournament_matches')->where('id', (int) $match->id)->where('tenant_id', $tenantId)->update($this->filterColumns('tournament_matches', $data));

            if ($this->db->tableExists('tournament_match_scores')) {
                $score = $state['score'];
                $existing = $this->db->table('tournament_match_scores')->where('tenant_id', $tenantId)->where('match_id', (int) $match->id)->where('set_no', 1)->get(1)->getRow();
                if ($score) {
                    $scoreData = ['tenant_id' => $tenantId, 'match_id' => (int) $match->id, 'set_no' => 1, 'team_a_score' => $score[0], 'team_b_score' => $score[1], 'winner_team_id' => $state['winner'], 'updated_at' => $this->now];
                    if ($existing) $this->db->table('tournament_match_scores')->where('id', (int) $existing->id)->update($this->filterColumns('tournament_match_scores', $scoreData));
                    else $this->db->table('tournament_match_scores')->insert($this->filterColumns('tournament_match_scores', $scoreData + ['created_at' => $this->now]));
                    if ($this->db->tableExists('tournament_score_logs')) {
                        $log = $this->db->table('tournament_score_logs')->where('tenant_id', $tenantId)->where('match_id', (int) $match->id)->where('reason', 'Fixture Demo Summer Cup')->get(1)->getRow();
                        $logData = ['tenant_id' => $tenantId, 'match_id' => (int) $match->id, 'old_score_json' => json_encode(['a' => 0, 'b' => 0]), 'new_score_json' => json_encode(['a' => $score[0], 'b' => $score[1]]), 'reason' => 'Fixture Demo Summer Cup', 'created_at' => $this->now];
                        if ($log) $this->db->table('tournament_score_logs')->where('id', (int) $log->id)->update($this->filterColumns('tournament_score_logs', $logData));
                        else $this->db->table('tournament_score_logs')->insert($this->filterColumns('tournament_score_logs', $logData));
                    }
                } elseif ($existing) {
                    $this->db->table('tournament_match_scores')->where('id', (int) $existing->id)->delete();
                }
            }
        }
    }

    private function ensureLiveDisplays(int $tenantId, int $tournamentId, int $categoryId): void
    {
        if (! $this->db->tableExists('live_display_configs')) return;
        foreach ([['tv', 'Demo Summer Cup · TV / LED'], ['public', 'Demo Summer Cup · Public Scoreboard']] as [$mode, $name]) {
            $row = $this->db->table('live_display_configs')->where('tenant_id', $tenantId)->where('tournament_id', $tournamentId)->where('mode', $mode)->get(1)->getRow();
            $data = ['tenant_id' => $tenantId, 'tournament_id' => $tournamentId, 'display_name' => $name, 'mode' => $mode, 'show_sponsor' => 1, 'show_next_matches' => 1, 'refresh_seconds' => $mode === 'tv' ? 5 : 10, 'status' => 'active', 'updated_at' => $this->now];
            if ($row) $this->db->table('live_display_configs')->where('id', (int) $row->id)->update($this->filterColumns('live_display_configs', $data));
            else $this->db->table('live_display_configs')->insert($this->filterColumns('live_display_configs', $data + ['created_at' => $this->now]));
        }
    }

    private function ensureTournamentTemplates(int $tenantId, int $tournamentId): void
    {
        if (! $this->db->tableExists('tournament_templates')) return;
        $tournament = $this->db->table('tournaments')->where('id', $tournamentId)->where('tenant_id', $tenantId)->get(1)->getRow();
        if (! $tournament) return;
        $categories = $this->db->table('tournament_categories')->where('tenant_id', $tenantId)->where('tournament_id', $tournamentId)->where('deleted_at', null)->get()->getResult();
        $snapshot = ['branch_id' => (int) $tournament->branch_id, 'name_vi' => $tournament->name_vi, 'name_en' => $tournament->name_en, 'description_vi' => $tournament->description_vi, 'description_en' => $tournament->description_en, 'max_teams' => (int) $tournament->max_teams, 'registration_fee' => (float) $tournament->registration_fee, 'categories' => array_map(static fn (object $c): array => ['name_vi' => $c->name_vi, 'name_en' => $c->name_en, 'category_type' => $c->category_type, 'max_teams' => $c->max_teams, 'min_rating' => $c->min_rating, 'max_rating' => $c->max_rating, 'registration_fee' => $c->registration_fee, 'status' => $c->status], $categories), 'rule_content_vi' => 'Thi đấu chạm 11, cách 2, tối đa 15.', 'rule_content_en' => 'Games to 11, win by 2, cap at 15.', 'sponsors' => [['sponsor_name' => 'Demo Sports', 'website' => 'https://example.com', 'sort_order' => 1, 'status' => 'active']]];
        $row = $this->db->table('tournament_templates')->where('tenant_id', $tenantId)->where('source_tournament_id', $tournamentId)->where('name', 'Demo Summer Cup · Mẫu hàng tháng')->get(1)->getRow();
        $data = ['tenant_id' => $tenantId, 'source_tournament_id' => $tournamentId, 'name' => 'Demo Summer Cup · Mẫu hàng tháng', 'description' => 'Mẫu giải hàng tháng: đổi ngày, mở đăng ký và dùng lại cấu hình.', 'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE), 'is_active' => 1, 'updated_at' => $this->now];
        if ($row) $this->db->table('tournament_templates')->where('id', (int) $row->id)->update($this->filterColumns('tournament_templates', $data));
        else $this->db->table('tournament_templates')->insert($this->filterColumns('tournament_templates', $data + ['created_at' => $this->now]));
    }

    private function refreshAiProposal(int $tenantId, int $tournamentId, array $matchIds, int $courtCount): void
    {
        if (! $this->db->tableExists('ai_scheduling_requests')) {
            return;
        }
        $matches = $this->db->table('tournament_matches')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $matchIds)
            ->where('status', 'scheduled')
            ->orderBy('match_no', 'ASC')
            ->get()
            ->getResult();
        $suggestions = array_map(static fn (object $match): array => [
            'match_id' => (int) $match->id,
            'team_a_id' => (int) ($match->team_a_id ?? 0),
            'team_b_id' => (int) ($match->team_b_id ?? 0),
            'slot' => ['court_id' => (int) $match->court_id, 'date' => $match->scheduled_date, 'start_time' => $match->start_time, 'end_time' => $match->end_time],
        ], $matches);
        $request = $this->db->table('ai_scheduling_requests')
            ->where('tenant_id', $tenantId)
            ->where('tournament_id', $tournamentId)
            ->orderBy('id', 'ASC')
            ->get(1)
            ->getRow();
        $data = [
            'status' => 'completed',
            'result_json' => json_encode(['engine' => 'local_heuristic', 'provider_requested' => 'local', 'court_count' => $courtCount, 'match_count' => count($matches), 'suggestions' => $suggestions], JSON_UNESCAPED_UNICODE),
            'updated_at' => $this->now,
        ];
        if ($request) {
            $this->db->table('ai_scheduling_requests')->where('id', (int) $request->id)->update($this->filterColumns('ai_scheduling_requests', $data));
        }
    }

    private function filterColumns(string $table, array $data): array
    {
        return array_intersect_key($data, array_flip($this->db->getFieldNames($table)));
    }
}
