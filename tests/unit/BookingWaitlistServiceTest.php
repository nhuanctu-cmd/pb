<?php

namespace Tests\Unit;

use App\Services\BookingWaitlistService;
use CodeIgniter\Test\CIUnitTestCase;

class BookingWaitlistServiceTest extends CIUnitTestCase
{
    public function testRequestKeyIsDeterministicAndTenantScoped(): void
    {
        $service = new BookingWaitlistService();
        $first = $service->makeRequestKey(1, 10, 4, '2026-09-01', '18:00', '19:00');
        $same = $service->makeRequestKey(1, 10, 4, '2026-09-01', '18:00', '19:00');
        $otherTenant = $service->makeRequestKey(2, 10, 4, '2026-09-01', '18:00', '19:00');

        $this->assertSame($first, $same);
        $this->assertNotSame($first, $otherTenant);
        $this->assertSame(64, strlen($first));
    }
}
