<?php

namespace Tests\Unit;

use App\Services\TournamentSchedulerService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class TournamentSchedulerServiceTest extends CIUnitTestCase
{
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();

        $branch = $db->table('branches')->where('tenant_id', 1)->where('deleted_at', null)->orderBy('id')->get(1)->getRow();
        if (! $branch) {
            $this->markTestSkipped('Thiếu branch tenant 1 để tạo giải test.');
        }

        $players = $db->table('players')->where('tenant_id', 1)->where('status', 'active')->where('deleted_at', null)->orderBy('id')->get(4)->getResult();
        if (count($players) < 4) {
            $this->markTestSkipped('Thiếu tối thiểu 4 player tenant 1 để tạo dữ liệu test.');
        }

        $this->created['player_ids'] = array_map(static fn ($row): int => (int) $row->id, $players);

        $db->table('tournaments')->insert([
            'tenant_id' => 1,
            'branch_id' => (int) $branch->id,
            'name_vi' => 'Test Tournament Scheduler',
            'name_en' => 'Test Tournament Scheduler',
            'slug_vi' => 'test-scheduler-' . bin2hex(random_bytes(4)),
            'slug_en' => 'test-scheduler-' . bin2hex(random_bytes(4)),
            'status' => 'open',
            'start_date' => date('Y-m-d', strtotime('+3 days')),
            'end_date' => date('Y-m-d', strtotime('+4 days')),
            'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'registration_end' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'max_teams' => 8,
            'registration_fee' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->created['tournament_id'] = (int) $db->insertID();

        $db->table('tournament_categories')->insert([
            'tenant_id' => 1,
            'tournament_id' => (int) $this->created['tournament_id'],
            'name_vi' => 'Singles',
            'name_en' => 'Singles',
            'category_type' => 'single_male',
            'max_teams' => 8,
            'registration_fee' => 0,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->created['category_id'] = (int) $db->insertID();

        $this->seedParticipants();
    }

    protected function tearDown(): void
    {
        $db = Database::connect();
        if (! empty($this->created['tournament_id'])) {
            $db->query('SET FOREIGN_KEY_CHECKS = 0');
            foreach ([
                'tournament_matches',
                'tournament_brackets',
                'tournament_schedule_locks',
                'tournament_draw_versions',
                'tournament_groups',
                'tournament_group_teams',
                'tournament_teams',
            ] as $table) {
                if ($db->tableExists($table)) {
                    $db->table($table)->where('tenant_id', 1)->where('tournament_id', (int) $this->created['tournament_id'])->delete();
                }
            }

            if (! empty($this->created['category_id'])) {
                $db->table('tournament_categories')->where('id', (int) $this->created['category_id'])->delete();
            }
            foreach ($this->created['team_ids'] ?? [] as $teamId) {
                if ($db->tableExists('teams')) {
                    $db->table('teams')->where('id', $teamId)->delete();
                }
            }

            $db->table('tournaments')->where('id', (int) $this->created['tournament_id'])->where('tenant_id', 1)->delete();
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
        }

        parent::tearDown();
    }

    public function testRebuildReturnsSameResultForSameSeedSnapshot(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('tournament_draw_versions') || ! $db->fieldExists('draw_version_id', 'tournament_matches')) {
            $this->markTestSkipped('Chưa có migration draw versioning để test phiên bản draw.');
        }

        $service = new TournamentSchedulerService();
        $service->generateGroups((int) $this->created['category_id'], 1);
        $service->seedTeams((int) $this->created['category_id']);
        $first = $service->generateKnockoutBracketWithOptions((int) $this->created['category_id'], [
            'force' => true,
            'actor_id' => 1,
            'reason' => 'first build',
        ]);
        $firstRound = $this->collectRoundOneTeams($first);
        $this->assertCount(4, $firstRound);

        $second = $service->generateKnockoutBracketWithOptions((int) $this->created['category_id'], [
            'force' => false,
            'actor_id' => 1,
            'reason' => 'replay same snapshot',
        ]);
        $this->assertCount(count($first), $second);
        $this->assertEquals(array_column($first, 'id'), array_column($second, 'id'));

        $latest = $db->table('tournament_draw_versions')
            ->where('tenant_id', 1)
            ->where('tournament_id', (int) $this->created['tournament_id'])
            ->where('category_id', (int) $this->created['category_id'])
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        $this->assertNotNull($latest);
        $this->assertNotEmpty($latest->draw_signature);

        $versioned = array_map(static fn ($match) => (int) ($match->draw_version_id ?? 0), $first);
        $this->assertCount(1, array_unique($versioned));
    }

    public function testForceRebuildRejectedWhenLockedAndAllowedWhenForced(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('tournament_draw_versions') || ! $db->fieldExists('draw_version_id', 'tournament_matches')) {
            $this->markTestSkipped('Chưa có migration draw versioning để test hành vi publish lock.');
        }

        $service = new TournamentSchedulerService();
        $service->generateGroups((int) $this->created['category_id'], 1);
        $service->seedTeams((int) $this->created['category_id']);
        $service->generateKnockoutBracketWithOptions((int) $this->created['category_id'], ['force' => true, 'actor_id' => 1, 'reason' => 'init']);

        if ($db->tableExists('tournament_schedule_locks') && $db->tableExists('tournament_categories')) {
            $db->table('tournament_schedule_locks')->insert([
                'tenant_id' => 1,
                'tournament_id' => (int) $this->created['tournament_id'],
                'lock_type' => 'time',
                'ref_id' => (int) $this->created['category_id'],
                'reason' => 'test lock',
            ]);
        }

        try {
            $service->generateKnockoutBracketWithOptions((int) $this->created['category_id'], ['force' => false, 'actor_id' => 1]);
            $this->fail('Expected RuntimeException when category is publish-locked.');
        } catch (\RuntimeException) {
            // expected exception
        }

        $forced = $service->generateKnockoutBracketWithOptions((int) $this->created['category_id'], [
            'force' => true,
            'actor_id' => 1,
            'reason' => 'forced rebuild',
        ]);
        $this->assertNotEmpty($forced);
    }

    public function testDifferentSeedIndexChangesOrderButDeterministic(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('tournament_draw_versions') || ! $db->fieldExists('draw_version_id', 'tournament_matches')) {
            $this->markTestSkipped('Chưa có migration draw versioning để test seed reproducibility.');
        }

        $service = new TournamentSchedulerService();
        $service->generateGroups((int) $this->created['category_id'], 1);
        $service->seedTeams((int) $this->created['category_id']);
        $base = $service->generateKnockoutBracketWithOptions((int) $this->created['category_id'], [
            'force' => true,
            'seed_index' => 0,
            'actor_id' => 1,
            'reason' => 'seed0',
        ]);
        $rerun = $service->generateKnockoutBracketWithOptions((int) $this->created['category_id'], [
            'force' => true,
            'seed_index' => 1,
            'actor_id' => 1,
            'reason' => 'seed1',
        ]);

        $this->assertNotEquals(array_column($base, 'id'), array_column($rerun, 'id'));
    }

    private function seedParticipants(): void
    {
        $db = Database::connect();
        $teamIds = [];
        foreach ($this->created['player_ids'] as $index => $playerId) {
            $db->table('teams')->insert([
                'tenant_id' => 1,
                'team_name' => 'Test Team #' . ($index + 1),
                'captain_player_id' => (int) $playerId,
                'team_type' => 'group',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $teamIds[] = (int) $db->insertID();
        }
        $this->created['team_ids'] = $teamIds;

        if ($db->tableExists('tournament_teams')) {
            $fields = $db->getFieldNames('tournament_teams');
            foreach ($teamIds as $index => $teamId) {
                $teamColumn = in_array('team_id', $fields, true) ? 'team_id' : (in_array('id', $fields, true) ? 'id' : null);
                if (! $teamColumn) {
                    continue;
                }

                $row = [
                    'tournament_id' => (int) $this->created['tournament_id'],
                    'category_id' => (int) $this->created['category_id'],
                    $teamColumn => $teamId,
                    'seed_no' => $index + 1,
                ];
                if (in_array('tenant_id', $fields, true)) {
                    $row['tenant_id'] = 1;
                }
                if (in_array('club_id', $fields, true)) {
                    $row['club_id'] = null;
                }
                if (in_array('rating', $fields, true)) {
                    $row['rating'] = 3 + ($index * 0.2);
                }
                $db->table('tournament_teams')->insert($row);
            }
            return;
        }

        if ($db->tableExists('tournament_group_teams') && $db->tableExists('tournament_groups')) {
            $db->table('tournament_groups')->insert([
                'tenant_id' => 1,
                'tournament_id' => (int) $this->created['tournament_id'],
                'category_id' => (int) $this->created['category_id'],
                'group_name' => 'G1',
                'sort_order' => 1,
            ]);
            $groupId = (int) $db->insertID();
            foreach ($teamIds as $index => $teamId) {
                $db->table('tournament_group_teams')->insert([
                    'tenant_id' => 1,
                    'group_id' => $groupId,
                    'team_id' => (int) $teamId,
                    'seed_no' => $index + 1,
                ]);
            }
        }
    }

    private function collectRoundOneTeams(array $matches): array
    {
        $teams = [];
        foreach ($matches as $match) {
            if (($match->round_name ?? '') === 'Knockout R1') {
                if ((int) ($match->team_a_id ?? 0)) {
                    $teams[] = (int) $match->team_a_id;
                }
                if ((int) ($match->team_b_id ?? 0)) {
                    $teams[] = (int) $match->team_b_id;
                }
            }
        }

        return $teams;
    }
}
