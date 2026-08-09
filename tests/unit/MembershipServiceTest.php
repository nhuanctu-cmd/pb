<?php

namespace Tests\Unit;

use App\Services\MembershipService;
use CodeIgniter\Test\CIUnitTestCase;

class MembershipServiceTest extends CIUnitTestCase
{
    private MembershipService $service;
    private array $membershipIds = [];
    private int $playerId = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MembershipService();
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
        $membershipId = $this->service->buyPackage($this->playerId, 1, 1, 2);
        $this->assertNotNull($membershipId);
        $this->membershipIds[] = $membershipId;

        $membership = model(\App\Models\MembershipModel::class)->find($membershipId);
        $this->assertSame(1, (int) $membership->tenant_id);
        $this->assertSame($this->playerId, (int) $membership->player_id);
        $this->assertNull($this->service->buyPackage($this->playerId, 1, 2, 2));
    }

    public function testPlayerCannotCancelMembershipFromAnotherTenant(): void
    {
        $membershipId = $this->service->buyPackage($this->playerId, 1, 1, 2);
        $this->assertNotNull($membershipId);
        $this->membershipIds[] = $membershipId;

        $this->assertFalse($this->service->cancel($membershipId, 2));
        $this->assertSame('active', model(\App\Models\MembershipModel::class)->find($membershipId)->status);
    }
}
