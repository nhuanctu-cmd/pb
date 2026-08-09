<?php

namespace Tests\Unit;

use App\Services\SettingService;
use App\Models\SettingModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC — Settings: tenant override ưu tiên hơn global default
 */
class SettingServiceTest extends CIUnitTestCase
{
    protected SettingService $service;
    protected int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SettingService();

        // Dọn dữ liệu test key
        \Config\Database::connect()->table('settings')
            ->where('key', 'unit_test_key')
            ->orWhere('key', 'unit_numeric_key')
            ->delete();
    }

    protected function tearDown(): void
    {
        \Config\Database::connect()->table('settings')
            ->where('key', 'unit_test_key')
            ->orWhere('key', 'unit_numeric_key')
            ->delete();
        parent::tearDown();
    }

    public function testGetReturnsDefaultWhenNoSetting(): void
    {
        $this->assertSame('fallback', $this->service->get('unit_test_key', 'fallback', $this->tenantId));
    }

    public function testGetGlobalDefault(): void
    {
        (new SettingModel())->setSetting('unit_test_key', 'global_value', null, 'text', 'general');

        $this->assertSame('global_value', $this->service->get('unit_test_key', 'fallback', $this->tenantId));
    }

    public function testGetTenantOverride(): void
    {
        (new SettingModel())->setSetting('unit_test_key', 'global_value', null, 'text', 'general');
        (new SettingModel())->setSetting('unit_test_key', 'tenant_value', $this->tenantId, 'text', 'general');

        $this->assertSame('tenant_value', $this->service->get('unit_test_key', 'fallback', $this->tenantId));
    }

    public function testCastBoolean(): void
    {
        (new SettingModel())->setSetting('unit_test_key', '1', $this->tenantId, 'boolean', 'general');

        $this->assertTrue($this->service->get('unit_test_key', false, $this->tenantId));
    }

    public function testCastNumber(): void
    {
        (new SettingModel())->setSetting('unit_numeric_key', '42.5', $this->tenantId, 'number', 'general');

        $this->assertSame(42.5, $this->service->get('unit_numeric_key', 0, $this->tenantId));
    }
}
