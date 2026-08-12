<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

/** Contract smoke test cho các màn hình vận hành giải đấu trên schema Pickleball. */
final class TournamentAdminFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    private int $tournamentId = 0;
    private int $categoryId = 0;
    private array $playerIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();
        $branch = $db->table('branches')->where('tenant_id', 1)->where('deleted_at', null)->orderBy('id')->get(1)->getRow();
        $players = $db->table('players')->where('tenant_id', 1)->where('status', 'active')->where('deleted_at', null)->orderBy('id')->get(2)->getResult();
        if (! $branch || count($players) < 2) {
            $this->markTestSkipped('Database test chưa có branch và player mẫu.');
        }
        $this->playerIds = [(int) $players[0]->id, (int) $players[1]->id];

        $slug = 'test-admin-contract-' . bin2hex(random_bytes(5));
        $db->table('tournaments')->insert([
            'tenant_id' => 1, 'branch_id' => (int) $branch->id,
            'name_vi' => 'Test Admin Contract Cup', 'name_en' => 'Test Admin Contract Cup',
            'slug_vi' => $slug, 'slug_en' => $slug, 'status' => 'open',
            'start_date' => date('Y-m-d', strtotime('+3 days')), 'end_date' => date('Y-m-d', strtotime('+4 days')),
            'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')), 'registration_end' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'max_teams' => 8, 'registration_fee' => 100000,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->tournamentId = (int) $db->insertID();
        $db->table('tournament_categories')->insert([
            'tenant_id' => 1, 'tournament_id' => $this->tournamentId,
            'name_vi' => 'Test Singles', 'name_en' => 'Test Singles', 'category_type' => 'single_male',
            'max_teams' => 8, 'registration_fee' => 100000, 'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->categoryId = (int) $db->insertID();
        foreach ($this->playerIds as $index => $playerId) {
            $db->table('tournament_registrations')->insert([
                'tenant_id' => 1, 'tournament_id' => $this->tournamentId, 'category_id' => $this->categoryId,
                'player_id' => $playerId, 'contact_name' => 'Contract Player ' . ($index + 1), 'contact_phone' => '09000000' . ($index + 1),
                'payment_status' => 'paid', 'approval_status' => 'approved', 'registration_status' => 'confirmed', 'eligibility_status' => 'passed',
                'invoice_code' => 'TEST-CONTRACT-' . ($index + 1), 'invoice_amount' => 100000,
            ]);
        }
        $db->table('tournament_matches')->insert([
            'tenant_id' => 1, 'tournament_id' => $this->tournamentId, 'category_id' => $this->categoryId,
            'round_name' => 'Vòng 1', 'match_no' => 1, 'team_a_id' => $this->playerIds[0], 'team_b_id' => $this->playerIds[1],
            'status' => 'scheduled', 'is_locked' => 0, 'scheduled_date' => date('Y-m-d', strtotime('+3 days')), 'start_time' => '08:00:00',
        ]);
        $matchId = (int) $db->insertID();
        $db->table('tournament_brackets')->insert([
            'tenant_id' => 1, 'tournament_id' => $this->tournamentId, 'category_id' => $this->categoryId,
            'match_id' => $matchId, 'bracket_position' => 'R1-1', 'round_no' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $db = Database::connect();
        if ($this->tournamentId) {
            $db->query('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['tournament_schedule_locks', 'tournament_brackets', 'tournament_matches', 'tournament_registrations', 'tournament_categories', 'tournament_rules', 'tournament_sponsors'] as $table) {
                if ($db->tableExists($table)) $db->table($table)->where('tournament_id', $this->tournamentId)->delete();
            }
            $db->table('tournaments')->where('id', $this->tournamentId)->delete();
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
        }
        parent::tearDown();
    }

    private function adminSession(): array
    {
        return ['isLoggedIn' => true, 'userId' => 1, 'user_id' => 1, 'username' => 'admin', 'is_superadmin' => true, 'tenant_id' => 1, 'branch_id' => 1, 'locale' => 'vi'];
    }

    public function testTournamentOperationsScreensLoadWithFixture(): void
    {
        $session = $this->adminSession();
        $show = $this->withSession($session)->call('GET', '/admin/tournaments/show/' . $this->tournamentId);
        $show->assertOK();
        $show->assertSee('Test Admin Contract Cup');
        $registrations = $this->withSession($session)->call('GET', '/admin/tournaments/registrations/' . $this->tournamentId);
        $registrations->assertOK();
        $registrations->assertSee('Contract Player 1');
        $scheduler = $this->withSession($session)->call('GET', '/admin/tournaments/scheduler?category_id=' . $this->categoryId);
        $scheduler->assertOK();
        $scheduler->assertSee('Lưu lịch');
        $bracket = $this->withSession($session)->call('GET', '/admin/tournaments/bracket?category_id=' . $this->categoryId);
        $bracket->assertOK();
        $bracket->assertSee('Vòng 1');
    }

    public function testRegistrationAndScheduleExportsReturnCsv(): void
    {
        $session = $this->adminSession();
        $registrations = $this->withSession($session)->call('GET', '/admin/tournaments/registrations/' . $this->tournamentId . '/export');
        $registrations->assertOK();
        $this->assertStringContainsString('text/csv', strtolower((string) $registrations->response()->getHeaderLine('Content-Type')));

        $schedule = $this->withSession($session)->call('GET', '/admin/tournaments/scheduler/export?category_id=' . $this->categoryId);
        $schedule->assertOK();
        $this->assertStringContainsString('text/csv', strtolower((string) $schedule->response()->getHeaderLine('Content-Type')));
    }
}
