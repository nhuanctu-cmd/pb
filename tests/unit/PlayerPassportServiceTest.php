<?php

namespace Tests\Unit;

use App\Services\PlayerPassportService;
use App\Models\PlayerModel;
use App\Models\PlayerCompetitiveProfileModel;
use App\Models\PlayerIdentityClaimModel;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class PlayerPassportServiceTest extends CIUnitTestCase
{
    protected $refresh = true;
    protected $seed;
    protected PlayerPassportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlayerPassportService();

        // Clean tables for isolation, respecting FK constraints
        $db = Database::connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $db->table('player_competitive_profiles')->truncate();
        $db->table('player_identity_claims')->truncate();
        $db->table('players')->truncate();
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testEnsurePassportCreatesNationalPlayerId()
    {
        $db = Database::connect();
        $db->table('players')->insert([
            'tenant_id' => 1,
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0909123456',
            'email' => 'a@example.com',
            'status' => 'active',
        ]);
        $playerId = (int) $db->insertID();

        $result = $this->service->ensurePassport($playerId);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['profile']->national_player_id);
        $this->assertMatchesRegularExpression('/^VNP-[0-9]{11}$/', $result['profile']->national_player_id);
        $this->assertEquals('unverified', $result['profile']->status);

        // Verify identity claims created
        $claims = model(PlayerIdentityClaimModel::class)->findByPlayer($playerId);
        $this->assertCount(2, $claims);
    }

    public function testEnsurePassportReturnsExisting()
    {
        $db = Database::connect();
        $db->table('players')->insert([
            'tenant_id' => 1,
            'full_name' => 'Trần Thị B',
            'status' => 'active',
        ]);
        $playerId = (int) $db->insertID();

        $first = $this->service->ensurePassport($playerId);
        $second = $this->service->ensurePassport($playerId);

        $this->assertEquals($first['profile']->national_player_id, $second['profile']->national_player_id);
    }

    public function testVerifyPlayer()
    {
        $db = Database::connect();
        $db->table('players')->insert([
            'tenant_id' => 1,
            'full_name' => 'Lê Văn C',
            'status' => 'active',
        ]);
        $playerId = (int) $db->insertID();
        $this->service->ensurePassport($playerId);

        $result = $this->service->verifyPlayer($playerId, 'verified');
        $this->assertTrue($result['success']);

        $profile = model(PlayerCompetitiveProfileModel::class)->findByPlayerId($playerId);
        $this->assertEquals('verified', $profile->status);
        $this->assertNotNull($profile->verified_at);
    }

    public function testUpdatePrivacy()
    {
        $db = Database::connect();
        $db->table('players')->insert([
            'tenant_id' => 1,
            'full_name' => 'Phạm Thị D',
            'status' => 'active',
        ]);
        $playerId = (int) $db->insertID();
        $this->service->ensurePassport($playerId);

        $result = $this->service->updatePrivacy($playerId, 'club');
        $this->assertTrue($result['success']);

        $profile = model(PlayerCompetitiveProfileModel::class)->findByPlayerId($playerId);
        $this->assertEquals('club', $profile->privacy_level);
    }

    public function testNationalCardTokenIsSignedAndRejectsTampering()
    {
        $token = $this->service->createCardToken('VNP-00000001234', 3600);
        $this->assertSame('VNP-00000001234', $this->service->verifyCardToken($token)['npi']);
        $this->assertNull($this->service->verifyCardToken($token . 'tampered'));
    }
}
