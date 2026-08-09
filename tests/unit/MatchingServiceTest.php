<?php

namespace Tests\Unit;

use App\Services\MatchingService;
use CodeIgniter\Test\CIUnitTestCase;

class MatchingServiceTest extends CIUnitTestCase
{
    public function testTimeRangeMustBeAscending(): void
    {
        $this->assertTrue(MatchingService::isValidTimeRange('18:00', '19:30'));
        $this->assertFalse(MatchingService::isValidTimeRange('19:30', '18:00'));
    }
}
