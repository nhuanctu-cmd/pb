<?php

namespace Tests\Unit;

use App\Services\WalkInService;
use CodeIgniter\Test\CIUnitTestCase;

class WalkInServiceTest extends CIUnitTestCase
{
    public function testSessionKeyIsTrimmedAndBounded(): void
    {
        $this->assertSame('desk-001', WalkInService::normalizeSessionKey('  desk-001  '));
        $this->assertSame(100, strlen(WalkInService::normalizeSessionKey(str_repeat('x', 140))));
    }

    public function testTimeRangeRequiresValidAscendingTimes(): void
    {
        $this->assertTrue(WalkInService::isValidTimeRange('09:00', '10:30'));
        $this->assertFalse(WalkInService::isValidTimeRange('10:30', '09:00'));
        $this->assertFalse(WalkInService::isValidTimeRange('9:00', '10:00'));
    }
}
