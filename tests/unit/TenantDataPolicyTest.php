<?php

namespace Tests\Unit;

use App\Services\TenantDataPolicy;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class TenantDataPolicyTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $db = Database::connect();
        $db->query("CREATE TABLE IF NOT EXISTS tenant_data_policies (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, tenant_id INT UNSIGNED NULL, resource_type VARCHAR(80) NOT NULL, access_scope VARCHAR(30) NOT NULL, effect VARCHAR(10) NOT NULL DEFAULT 'deny', visibility VARCHAR(20) NOT NULL DEFAULT 'private', requires_consent TINYINT(1) NOT NULL DEFAULT 0, version VARCHAR(20) NOT NULL DEFAULT 'v1', status VARCHAR(20) NOT NULL DEFAULT 'active', configuration JSON NULL, created_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, PRIMARY KEY(id), KEY idx_policy_tenant(tenant_id,resource_type,access_scope,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        Database::connect()->table('tenant_data_policies')->where('tenant_id', 901)->delete();
    }

    protected function tearDown(): void
    {
        Database::connect()->table('tenant_data_policies')->where('tenant_id', 901)->delete();
        parent::tearDown();
    }

    public function testOperationalDataCannotCrossTenant(): void
    {
        $policy = new TenantDataPolicy();
        $this->assertTrue($policy->canAccess(1, 1));
        $this->assertFalse($policy->canAccess(2, 1));
        $this->assertSame('TENANT_ISOLATION', $policy->assertAccess(2, 1)['code']);
    }

    public function testPublishedNetworkDataNeedsExplicitPublishedFlag(): void
    {
        $policy = new TenantDataPolicy();
        $this->assertFalse($policy->canAccess(2, 1, TenantDataPolicy::PLATFORM_PUBLIC, false, false));
        $this->assertFalse($policy->canAccess(2, 1, TenantDataPolicy::PLATFORM_PUBLIC, false, true));
        $this->assertTrue($policy->canAccess(1, 1, TenantDataPolicy::PLATFORM_PUBLIC, false, true));
    }

    public function testDefaultsAreIdempotentAndRestrictedStaysDenied(): void
    {
        $policy = new TenantDataPolicy();
        $first = $policy->ensureDefaults(901);
        $second = $policy->ensureDefaults(901);

        $this->assertTrue($first['success']);
        $this->assertSame(5, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertFalse($policy->canAccess(901, 901, TenantDataPolicy::RESTRICTED, true, true));
        $this->assertCount(5, $policy->policies(901));
    }
}
