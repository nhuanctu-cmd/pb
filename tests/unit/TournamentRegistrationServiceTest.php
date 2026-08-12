<?php

namespace Tests\Unit;

use App\Services\TournamentRegistrationService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class TournamentRegistrationServiceTest extends CIUnitTestCase
{
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();
        $branch = $db->table('branches')->where('tenant_id', 1)->where('deleted_at', null)->orderBy('id')->get(1)->getRow();
        $players = $db->table('players')->where('tenant_id', 1)->where('status', 'active')->where('deleted_at', null)->orderBy('id')->get(2)->getResult();

        if (! $branch || count($players) < 2) {
            $this->markTestSkipped('Database test cần branch và tối thiểu 2 player active cho tenant 1.');
        }

        $this->created['playerA'] = (int) $players[0]->id;
        $this->created['playerB'] = (int) $players[1]->id;
        $slug = 'test-reg-' . bin2hex(random_bytes(5));

        $db->table('tournaments')->insert([
            'tenant_id' => 1,
            'branch_id' => (int) $branch->id,
            'name_vi' => 'Test Registration Tournament',
            'name_en' => 'Test Registration Tournament',
            'slug_vi' => $slug,
            'slug_en' => $slug,
            'status' => 'open',
            'start_date' => date('Y-m-d', strtotime('+3 days')),
            'end_date' => date('Y-m-d', strtotime('+4 days')),
            'registration_start' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'registration_end' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'max_teams' => 1,
            'registration_fee' => 90000,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->created['tournament'] = (int) $db->insertID();

        $db->table('tournament_categories')->insert([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'name_vi' => 'Singles',
            'name_en' => 'Singles',
            'category_type' => 'single_male',
            'max_teams' => 1,
            'registration_fee' => 90000,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->created['category'] = (int) $db->insertID();
    }

    protected function tearDown(): void
    {
        $db = Database::connect();
        if (! empty($this->created['tournament'])) {
            $tournamentId = $this->created['tournament'];
            $db->query('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['tournament_registrations', 'tournament_categories', 'tournament_matches', 'tournament_schedule_locks', 'tournament_brackets', 'tournament_rules', 'tournament_sponsors'] as $table) {
                if ($db->tableExists($table)) $db->table($table)->where('tournament_id', $tournamentId)->delete();
            }
            $db->table('tournaments')->where('id', $tournamentId)->delete();
            $db->query('SET FOREIGN_KEY_CHECKS = 1');
        }
        parent::tearDown();
    }

    public function testAdminQuickApprovePassesAndApprovesRegistration(): void
    {
        $service = new TournamentRegistrationService();
        $result = $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerA'],
            'contact_name' => 'Auto Test A',
            'contact_phone' => '0900111222',
            'payment_status' => 'paid',
            'quick_approve' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('approved', $result['registration']->approval_status);
        $this->assertSame('confirmed', $result['registration']->registration_status);
    }

    public function testAdminWaitlistWhenCategoryCapacityReached(): void
    {
        $service = new TournamentRegistrationService();
        $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerA'],
            'contact_name' => 'Auto Test A',
            'contact_phone' => '0900111222',
            'payment_status' => 'paid',
            'quick_approve' => true,
        ]);
        $second = $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerB'],
            'contact_name' => 'Auto Test B',
            'contact_phone' => '0900111333',
            'payment_status' => 'paid',
            'quick_approve' => true,
        ]);

        $this->assertTrue($second['success']);
        $this->assertSame('pending', $second['registration']->approval_status);
        $this->assertSame('waitlisted', $second['registration']->registration_status);
    }

    public function testAdminRejectsInvalidPartnerPlayer(): void
    {
        $service = new TournamentRegistrationService();
        $result = $service->registerAdmin([
            'tenant_id' => 1,
            'tournament_id' => $this->created['tournament'],
            'category_id' => $this->created['category'],
            'player_id' => $this->created['playerA'],
            'partner_player_id' => 999999,
            'contact_name' => 'Auto Test A',
            'contact_phone' => '0900111222',
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('không thuộc tenant', (string) $result['message']);
    }
}
