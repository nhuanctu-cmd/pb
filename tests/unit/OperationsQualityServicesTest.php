<?php

namespace Tests\Unit;

use App\Services\AvailabilityService;
use App\Services\DataQualityService;
use App\Services\QueueMonitorService;
use CodeIgniter\Test\CIUnitTestCase;

class OperationsQualityServicesTest extends CIUnitTestCase
{
    public function testAvailabilityRequiresTenantContext(): void
    {
        $result = (new AvailabilityService())->slots(1, date('Y-m-d'), 0);
        $this->assertFalse($result['success']);
        $this->assertSame('TENANT_REQUIRED', $result['code']);
    }

    public function testDataQualityEmptyTenantIsSafe(): void
    {
        $report = (new DataQualityService())->report(0);
        $this->assertSame(0, $report['total_issues']);
        $this->assertSame([], $report['checks']);
    }

    public function testQueueMonitorEmptyTenantIsSafe(): void
    {
        $report = (new QueueMonitorService())->report(0);
        $this->assertSame([], $report['summary']);
        $this->assertSame([], $report['failed']);
    }
}
