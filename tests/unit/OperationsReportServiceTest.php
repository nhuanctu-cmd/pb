<?php

namespace Tests\Unit;

use App\Services\OperationsReportService;
use CodeIgniter\Test\CIUnitTestCase;

class OperationsReportServiceTest extends CIUnitTestCase
{
    public function testRangeIsOrderedAndCapped(): void
    {
        [$from, $to] = OperationsReportService::normalizeRange('2026-08-09', '2027-12-31');
        $this->assertSame('2026-08-09', $from);
        $this->assertSame('2027-08-10', $to);
    }

    public function testInvalidDatesUseCurrentMonthDefaults(): void
    {
        [$from, $to] = OperationsReportService::normalizeRange('bad', 'bad');
        $this->assertSame(date('Y-m-01'), $from);
        $this->assertSame(date('Y-m-d'), $to);
    }
}
