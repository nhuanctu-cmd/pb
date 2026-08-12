<?php

namespace Tests\Unit;

use App\Services\LivestreamService;
use CodeIgniter\Test\CIUnitTestCase;

class LivestreamServiceTest extends CIUnitTestCase
{
    public function testOnlyHttpsUrlsAreAccepted(): void
    {
        $this->assertTrue(LivestreamService::isSafeUrl('https://youtube.com/watch?v=abc'));
        $this->assertFalse(LivestreamService::isSafeUrl('http://youtube.com/watch?v=abc'));
        $this->assertFalse(LivestreamService::isSafeUrl('javascript:alert(1)'));
    }

    public function testStatusTransitionProtectsLifecycle(): void
    {
        $this->assertTrue(LivestreamService::canTransition('draft', 'scheduled'));
        $this->assertTrue(LivestreamService::canTransition('scheduled', 'live'));
        $this->assertTrue(LivestreamService::canTransition('live', 'ended'));
        $this->assertFalse(LivestreamService::canTransition('ended', 'live'));
        $this->assertFalse(LivestreamService::canTransition('draft', 'live'));
    }

    public function testDateTimeFormatIsStrict(): void
    {
        $this->assertTrue(LivestreamService::isValidDateTime('2026-08-10 19:30'));
        $this->assertFalse(LivestreamService::isValidDateTime('10/08/2026 19:30'));
        $this->assertFalse(LivestreamService::isValidDateTime('2026-02-30 19:30'));
    }
}
