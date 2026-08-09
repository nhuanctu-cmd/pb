<?php

namespace Tests\Unit;

use App\Services\CoachingService;
use CodeIgniter\Test\CIUnitTestCase;

class CoachingServiceTest extends CIUnitTestCase
{
    public function testTimeRangeRequiresStrictAscendingClockValues(): void
    {
        $this->assertTrue(CoachingService::isValidTimeRange('08:00', '09:30'));
        $this->assertFalse(CoachingService::isValidTimeRange('09:30', '09:30'));
        $this->assertFalse(CoachingService::isValidTimeRange('9:00', '10:00'));
    }

    public function testDateTimeRangeRequiresParsableAscendingValues(): void
    {
        $this->assertTrue(CoachingService::isValidDateTimeRange('2026-08-10 08:00:00', '2026-08-10 09:00:00'));
        $this->assertFalse(CoachingService::isValidDateTimeRange('2026-08-10 09:00:00', '2026-08-10 08:00:00'));
    }
}
