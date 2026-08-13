<?php

namespace Tests\Unit;

use App\Services\VenueOperationsService;
use CodeIgniter\Test\CIUnitTestCase;

final class VenueOperationsServiceTest extends CIUnitTestCase
{
    public function testControlRoomReturnsStableEmptyPayloadForUnknownTenant(): void
    {
        $payload = (new VenueOperationsService())->controlRoom(0, null, 'invalid-date');

        $this->assertSame(date('Y-m-d'), $payload['date']);
        $this->assertArrayHasKey('courts', $payload);
        $this->assertArrayHasKey('late', $payload);
        $this->assertArrayHasKey('unchecked', $payload);
        $this->assertSame(['live', 'available', 'late', 'unchecked'], array_keys($payload['stats']));
    }
}
