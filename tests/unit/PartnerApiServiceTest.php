<?php

namespace Tests\Unit;

use App\Models\PartnerApiKeyModel;
use App\Services\PartnerApiService;
use CodeIgniter\Test\CIUnitTestCase;

class PartnerApiServiceTest extends CIUnitTestCase
{
    private PartnerApiKeyModel $model;
    private string $name = 'test-partner-key';

    protected function setUp(): void
    {
        parent::setUp();
        $db = \Config\Database::connect();
        // pickball_test is a long-lived snapshot; keep this P8 contract test
        // runnable before the next full test-database refresh.
        if (! $db->tableExists('partner_api_keys')) {
            $db->query("CREATE TABLE partner_api_keys (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(180) NOT NULL, key_prefix VARCHAR(32) NOT NULL, key_hash CHAR(64) NOT NULL, scopes JSON NOT NULL, status ENUM('active','revoked') NOT NULL DEFAULT 'active', expires_at DATETIME NULL, last_used_at DATETIME NULL, revoked_at DATETIME NULL, created_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, PRIMARY KEY(id), UNIQUE KEY uq_partner_test_hash(key_hash), KEY idx_partner_test_tenant(tenant_id,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        $this->model = new PartnerApiKeyModel();
        $this->model->where('name', $this->name)->delete();
    }

    protected function tearDown(): void
    {
        $this->model->where('name', $this->name)->delete();
        parent::tearDown();
    }

    public function testCreateReturnsSecretOnceAndAuthenticatesByHash(): void
    {
        $service = new PartnerApiService();
        $result = $service->createKey(3, $this->name, ['players.read', 'rankings.read'], null);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('pk_live_', $result['key']);
        $row = $this->model->find($result['id']);
        $this->assertNotSame($result['key'], $row->key_hash);
        $this->assertSame($result['id'], (int) $service->authenticate($result['key'])->id);
        $this->assertTrue($service->hasScope($row, 'players.read'));
        $this->assertFalse($service->hasScope($row, 'clubs.read'));
    }

    public function testRevokedKeyCannotAuthenticate(): void
    {
        $service = new PartnerApiService();
        $result = $service->createKey(3, $this->name, ['clubs.read']);
        $this->assertTrue($service->revoke($result['id'], 3)['success']);
        $this->assertNull($service->authenticate($result['key']));
        $this->assertFalse($service->revoke($result['id'], 1)['success']);
    }

    public function testInvalidScopeIsRejected(): void
    {
        $result = (new PartnerApiService())->createKey(3, $this->name, ['payments.read']);
        $this->assertFalse($result['success']);
    }
}
