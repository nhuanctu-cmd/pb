<?php

namespace Tests\Unit;

use App\Services\TournamentService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

/**
 * Kiểm tra vòng đời giải đấu độc lập với dữ liệu demo cố định.
 * Mỗi test tạo fixture riêng và chỉ dọn đúng các ID đã tạo.
 */
final class TournamentWorkflowServiceTest extends CIUnitTestCase
{
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();
        $branch = $db->table('branches')->where('tenant_id', 1)->where('deleted_at', null)->orderBy('id')->get(1)->getRow();
        if (! $branch) {
            $this->markTestSkipped('Database test chưa có branch mẫu cho tenant 1.');
        }

        $slug = 'test-workflow-' . bin2hex(random_bytes(5));
        $db->table('tournaments')->insert([
            'tenant_id' => 1,
            'branch_id' => (int) $branch->id,
            'name_vi' => 'Test Tournament Workflow',
            'name_en' => 'Test Tournament Workflow',
            'slug_vi' => $slug,
            'slug_en' => $slug,
            'status' => 'open',
            'start_date' => date('Y-m-d', strtotime('+2 days')),
            'end_date' => date('Y-m-d', strtotime('+3 days')),
            'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'registration_end' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'max_teams' => 8,
            'registration_fee' => 100000,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->created['tournament'] = (int) $db->insertID();
    }

    protected function tearDown(): void
    {
        $db = Database::connect();
        if (! empty($this->created['tournament'])) {
            $tournamentId = $this->created['tournament'];
            $db->query('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['tournament_schedule_locks', 'tournament_brackets', 'tournament_matches', 'tournament_registrations', 'tournament_categories', 'tournament_rules', 'tournament_sponsors'] as $table) {
                if ($db->tableExists($table)) $db->table($table)->where('tournament_id', $tournamentId)->delete();
            }
            $db->table('tournaments')->where('id', $tournamentId)->delete();
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
        }
        parent::tearDown();
    }

    public function testTournamentMovesFromOpenToRunningToCompleted(): void
    {
        $service = new TournamentService();
        $id = $this->created['tournament'];

        $running = $service->startTournament($id, 1);
        $this->assertTrue($running['success']);
        $this->assertSame('running', $running['tournament']->status);

        $completed = $service->completeTournament($id, 1);
        $this->assertTrue($completed['success']);
        $this->assertSame('completed', $completed['tournament']->status);
    }

    public function testCompletedTournamentCannotBeReopenedOrCancelled(): void
    {
        $service = new TournamentService();
        $id = $this->created['tournament'];
        $this->assertTrue($service->startTournament($id, 1)['success']);
        $this->assertTrue($service->completeTournament($id, 1)['success']);

        $this->assertFalse($service->openRegistration($id, 1)['success']);
        $this->assertFalse($service->cancelTournament($id, 1)['success']);
    }
}
