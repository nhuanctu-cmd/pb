<?php

namespace Tests\Unit;

use App\Services\MembershipService;
use CodeIgniter\Test\CIUnitTestCase;

class MembershipServiceTest extends CIUnitTestCase
{
    private MembershipService $service;
    private array $membershipIds = [];
    private int $playerId = 0;
    private int $packageId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MembershipService();
        $db = \Config\Database::connect();
        $player = $db->table('players')->where('id', 3)->where('tenant_id', 1)->get()->getRow();
        $this->playerId = (int) ($player->id ?? 0);
        if (! $this->playerId) {
            $db->table('players')->insert(['tenant_id' => 1, 'player_code' => 'MEMBERSHIP-TEST-' . bin2hex(random_bytes(3)), 'full_name' => 'Membership Test Player', 'status' => 'active']);
            $this->playerId = (int) $db->insertID();
        }
        $package = $db->table('membership_packages')->where('tenant_id', 1)->where('status', 'active')->where('deleted_at', null)->orderBy('id')->get()->getRow();
        if (! $package) {
            $db->table('membership_packages')->insert(['tenant_id' => 1, 'name_vi' => 'Test package', 'name_en' => 'Test package', 'duration_days' => 30, 'price' => 100000, 'status' => 'active']);
            $package = $db->table('membership_packages')->where('tenant_id', 1)->where('status', 'active')->orderBy('id', 'DESC')->get()->getRow();
        }
        $this->packageId = (int) ($package->id ?? 0);
    }

    protected function tearDown(): void
    {
        if ($this->membershipIds !== []) {
            \Config\Database::connect()->table('memberships')
                ->whereIn('id', $this->membershipIds)->delete();
        }
        parent::tearDown();
    }

    public function testBuyPackageIsTenantScoped(): void
    {
        $membershipId = $this->service->buyPackage($this->playerId, $this->packageId, 1, 2);
        $this->assertNotNull($membershipId);
        $this->membershipIds[] = $membershipId;

        $membership = model(\App\Models\MembershipModel::class)->find($membershipId);
        $this->assertSame(1, (int) $membership->tenant_id);
        $this->assertSame($this->playerId, (int) $membership->player_id);
        $this->assertNull($this->service->buyPackage($this->playerId, 1, 2, 2));
    }

    public function testPlayerCannotCancelMembershipFromAnotherTenant(): void
    {
        $membershipId = $this->service->buyPackage($this->playerId, $this->packageId, 1, 2);
        $this->assertNotNull($membershipId);
        $this->membershipIds[] = $membershipId;

        $this->assertFalse($this->service->cancel($membershipId, 2));
        $this->assertSame('active', model(\App\Models\MembershipModel::class)->find($membershipId)->status);
    }
}
