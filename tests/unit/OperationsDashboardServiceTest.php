<?php

namespace Tests\Unit;

use App\Services\OperationsDashboardService;
use CodeIgniter\Test\CIUnitTestCase;

class OperationsDashboardServiceTest extends CIUnitTestCase
{
    public function testInvalidDateFallsBackToToday(): void
    {
        $this->assertSame(date('Y-m-d'), OperationsDashboardService::normalizeDate('not-a-date'));
    }

    public function testValidDateIsPreserved(): void
    {
        $this->assertSame('2026-08-09', OperationsDashboardService::normalizeDate('2026-08-09'));
    }
}
