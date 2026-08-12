<?php

namespace Tests\Unit;

use App\Services\TournamentCheckInService;
use App\Models\TournamentModel;
use App\Models\TournamentCategoryModel;
use App\Models\TournamentRegistrationModel;
use App\Models\TournamentCheckinModel;
use App\Models\PlayerModel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class TournamentCheckInServiceTest extends CIUnitTestCase
{
    protected TournamentCheckInService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TournamentCheckInService();

        $db = Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->table('tournament_checkins')->truncate();
        $db->table('tournament_registrations')->truncate();
        $db->table('tournament_categories')->truncate();
        $db->table('tournaments')->truncate();
        $db->table('player_competitive_profiles')->truncate();
        $db->table('players')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function seedTournamentAndRegistration(): array
    {
        $db = Database::connect();

        $db->table('tournaments')->insert([
            'tenant_id' => 1,
            'branch_id' => 1,
            'name_vi' => 'HCM Open 2027',
            'name_en' => 'HCM Open 2027',
            'slug_vi' => 'hcm-open-2027',
            'slug_en' => 'hcm-open-2027',
            'status' => 'closed',
            'registration_start' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'registration_end' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'start_date' => date('Y-m-d', strtotime('+14 days')),
            'end_date' => date('Y-m-d', strtotime('+16 days')),
            'max_teams' => 32,
            'registration_fee' => 0,
        ]);
        $tournamentId = (int) $db->insertID();

        $db->table('tournament_categories')->insert([
            'tenant_id' => 1,
            'tournament_id' => $tournamentId,
            'name_vi' => 'Men Singles Open',
            'category_type' => 'single_male',
            'status' => 'active',
        ]);
        $categoryId = (int) $db->insertID();

        $db->table('players')->insert([
            'tenant_id' => 1,
            'full_name' => 'Nguyễn Văn A',
            'status' => 'active',
        ]);
        $playerId = (int) $db->insertID();
        $db->table('player_competitive_profiles')->insert([
            'player_id' => $playerId,
            'national_player_id' => 'VN-PKL-' . str_pad((string) $playerId, 10, '0', STR_PAD_LEFT),
            'status' => 'verified',
            'privacy_level' => 'public',
        ]);

        $db->table('tournament_registrations')->insert([
            'tenant_id' => 1,
            'tournament_id' => $tournamentId,
            'category_id' => $categoryId,
            'player_id' => $playerId,
            'team_id' => null,
            'contact_name' => 'Nguyễn Văn A',
            'contact_phone' => '0909123456',
            'approval_status' => 'approved',
            'payment_status' => 'paid',
        ]);
        $registrationId = (int) $db->insertID();

        return [$tournamentId, $categoryId, $playerId, $registrationId];
    }

    public function testCheckIn()
    {
        [$tournamentId, $categoryId, $playerId, $registrationId] = $this->seedTournamentAndRegistration();

        $result = $this->service->checkInPlayer($registrationId, $playerId, 1);
        $this->assertTrue($result['success']);
        $this->assertEquals('checked_in', $result['checkin']->status);

        $registration = model(TournamentRegistrationModel::class)->find($registrationId);
        $this->assertNotNull($registration->checked_in_at);
    }

    public function testNoShow()
    {
        [$tournamentId, $categoryId, $playerId, $registrationId] = $this->seedTournamentAndRegistration();

        $result = $this->service->markNoShow($registrationId, $playerId);
        $this->assertTrue($result['success']);
    }

    public function testCannotCheckInTwice()
    {
        [$tournamentId, $categoryId, $playerId, $registrationId] = $this->seedTournamentAndRegistration();

        $this->service->checkInPlayer($registrationId, $playerId, 1);
        $result = $this->service->checkInPlayer($registrationId, $playerId, 1);

        $this->assertFalse($result['success']);
    }
}
