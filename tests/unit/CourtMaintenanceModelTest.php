<?php

namespace Tests\Unit;

use App\Models\CourtMaintenanceModel;
use CodeIgniter\Test\CIUnitTestCase;

class CourtMaintenanceModelTest extends CIUnitTestCase
{
    public function testOpenEndedMaintenanceOverlapsFutureBooking(): void
    {
        $this->assertTrue(CourtMaintenanceModel::intervalsOverlap('2026-08-09 10:00:00', null, '2026-08-09 11:00:00', '2026-08-09 12:00:00'));
    }

    public function testAdjacentIntervalsDoNotOverlap(): void
    {
        $this->assertFalse(CourtMaintenanceModel::intervalsOverlap('2026-08-09 10:00:00', '2026-08-09 11:00:00', '2026-08-09 11:00:00', '2026-08-09 12:00:00'));
    }
}
