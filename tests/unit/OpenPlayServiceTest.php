<?php

namespace Tests\Unit;

use App\Services\OpenPlayService;
use CodeIgniter\Test\CIUnitTestCase;

class OpenPlayServiceTest extends CIUnitTestCase
{
    public function testCapacityRequiresAtLeastTwoAndHasRoom(): void
    {
        $this->assertTrue(OpenPlayService::canAdd(3, 4));
        $this->assertFalse(OpenPlayService::canAdd(4, 4));
        $this->assertFalse(OpenPlayService::canAdd(0, 1));
    }

    public function testTimeRangeIsAscending(): void
    {
        $this->assertTrue(OpenPlayService::isValidTimeRange('18:00', '20:00'));
        $this->assertFalse(OpenPlayService::isValidTimeRange('20:00', '18:00'));
    }
}
