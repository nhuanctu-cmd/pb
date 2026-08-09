<?php

namespace Tests\Unit;

use App\Services\RecurringBookingService;
use CodeIgniter\Test\CIUnitTestCase;

class RecurringBookingServiceTest extends CIUnitTestCase
{
    private RecurringBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecurringBookingService();
    }

    public function testWeeklyOccurrencesAndExclusionsAreDeterministic(): void
    {
        $dates = $this->service->buildOccurrenceDates(
            '2026-08-02',
            '2026-08-30',
            'weekly',
            1,
            [],
            ['2026-08-16']
        );

        $this->assertSame(['2026-08-02', '2026-08-09', '2026-08-23', '2026-08-30'], $dates);
    }

    public function testCustomDaysAndIntervalAreSupported(): void
    {
        $dates = $this->service->buildOccurrenceDates(
            '2026-08-03',
            '2026-08-23',
            'custom',
            2,
            [1, 3],
            []
        );

        $this->assertSame(['2026-08-03', '2026-08-05', '2026-08-17', '2026-08-19'], $dates);
    }

    public function testInvalidDateRangeProducesNoOccurrences(): void
    {
        $this->assertSame([], $this->service->buildOccurrenceDates(
            '2026-09-01',
            '2026-08-01',
            'weekly'
        ));
    }
}
