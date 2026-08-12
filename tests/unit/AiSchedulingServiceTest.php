<?php

namespace Tests\Unit;

use App\Services\AiSchedulingService;
use CodeIgniter\Test\CIUnitTestCase;

class AiSchedulingServiceTest extends CIUnitTestCase
{
    public function testDateRangeIsStrictAndBounded(): void
    {
        $this->assertTrue(AiSchedulingService::isValidDateRange('2026-08-10', '2026-08-10'));
        $this->assertTrue(AiSchedulingService::isValidDateRange('2026-08-01', '2026-08-31'));
        $this->assertFalse(AiSchedulingService::isValidDateRange('2026-08-31', '2026-08-01'));
        $this->assertFalse(AiSchedulingService::isValidDateRange('10-08-2026', '2026-08-10'));
        $this->assertFalse(AiSchedulingService::isValidDateRange('2026-08-01', '2026-09-02'));
    }

    public function testSlotBuilderKeepsCourtAndRestConstraints(): void
    {
        $slots = AiSchedulingService::buildSlots('2026-08-10', '2026-08-10', [7], 60, 30);

        $this->assertCount(9, $slots);
        $this->assertSame(7, $slots[0]['court_id']);
        $this->assertSame('08:00:00', $slots[0]['start_time']);
        $this->assertSame('09:00:00', $slots[0]['end_time']);
        $this->assertSame('09:30:00', $slots[1]['start_time']);
        $this->assertSame([], AiSchedulingService::buildSlots('2026-08-10', '2026-08-10', [], 60, 30));
    }
}
