<?php

namespace Tests\Unit;

use App\Services\TenantPlanService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * TC M1 — TenantPlanService: gói dịch vụ, hạn mức, tính năng
 * Dữ liệu: pickball_test đã seed TenantPlanSeeder
 */
class TenantPlanServiceTest extends CIUnitTestCase
{
    protected TenantPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TenantPlanService();
    }

    /** Tenant 1 đang ở gói Pro (active) */
    public function testTenantHasCurrentPlan(): void
    {
        $plan = $this->service->getCurrentPlan(1);

        $this->assertNotNull($plan);
        $this->assertSame('pro', $plan['plan_code']);
        $this->assertSame('active', $plan['status']);
    }

    /** Gói Pro có tính năng POS & tournament, không có ai_scheduling */
    public function testFeatureFlags(): void
    {
        $this->assertTrue($this->service->hasFeature(1, 'pos'));
        $this->assertTrue($this->service->hasFeature(1, 'tournament'));
        $this->assertFalse($this->service->hasFeature(1, 'ai_scheduling'));
    }

    /** Kiểm tra hạn mức trả về cấu trúc đúng + max của gói Pro */
    public function testCheckLimitStructure(): void
    {
        $limit = $this->service->checkLimit(1, 'courts');

        $this->assertArrayHasKey('allowed', $limit);
        $this->assertArrayHasKey('used', $limit);
        $this->assertArrayHasKey('max', $limit);
        $this->assertSame(20, $limit['max']); // Pro: max 20 sân
        $this->assertGreaterThanOrEqual(0, $limit['used']);
    }

    /** Ghi nhận usage theo tháng */
    public function testTrackUsage(): void
    {
        $metric = 'test_metric_' . uniqid();

        $this->service->trackUsage(1, $metric, 3);
        $this->service->trackUsage(1, $metric, 2);

        $this->assertSame(5, $this->service->getUsage(1, $metric));

        \Config\Database::connect()->table('tenant_usage')->where('metric', $metric)->delete();
    }

    /** Đăng ký gói mới: gói cũ bị hủy, gói mới hiệu lực */
    public function testSubscribeReplacesCurrentPlan(): void
    {
        $db = \Config\Database::connect();
        $enterpriseId = $db->table('tenant_plans')->where('code', 'enterprise')->get()->getRow('id');

        // Tenant 2 đang trial free → nâng lên enterprise
        $newId = $this->service->subscribe(2, (int) $enterpriseId, 'active');

        $this->assertNotNull($newId);

        $plan = $this->service->getCurrentPlan(2);
        $this->assertSame('enterprise', $plan['plan_code']);

        // Enterprise: không giới hạn (-1) → luôn allowed
        $limit = $this->service->checkLimit(2, 'courts');
        $this->assertTrue($limit['allowed']);
        $this->assertSame(-1, $limit['max']);

        // Khôi phục: trả tenant 2 về free trial
        $freeId = $db->table('tenant_plans')->where('code', 'free')->get()->getRow('id');
        $this->service->subscribe(2, (int) $freeId, 'trial', 30);
    }
}
