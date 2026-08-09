<?php

namespace Tests\Unit;

use App\Services\OpenPlayRotationService;
use CodeIgniter\Test\CIUnitTestCase;

class OpenPlayRotationHistoryTest extends CIUnitTestCase
{
    public function testWaitingTimeIsNeverNegative(): void
    {
        $this->assertSame(3600, OpenPlayRotationService::waitingSeconds('2026-08-09 10:00:00', '2026-08-09 11:00:00'));
        $this->assertSame(0, OpenPlayRotationService::waitingSeconds('2026-08-09 11:00:00', '2026-08-09 10:00:00'));
    }
}
