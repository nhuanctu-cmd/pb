<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class TournamentPublicFeatureTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testTournamentIndexShowsDiscoverableEventData(): void
    {
        $response = $this->call('GET', '/tournaments');

        $response->assertOK();
        $response->assertSee('Lịch thi đấu');
        $response->assertSee('PUBLIC DATA CONTRACT');
        $response->assertSee('tp-card');
    }

    public function testTournamentDetailShowsScheduleAndCompetitionContext(): void
    {
        $response = $this->call('GET', '/tournaments/hcm-open-2027');

        $response->assertOK();
        $response->assertSee('Hạng mục');
        $response->assertSee('Lịch sân');
        $response->assertSee('Điều lệ');
        $response->assertSee('VENUE');
    }

    public function testTournamentRegistrationPageShowsEligibilityAndEventSummary(): void
    {
        $response = $this->call('GET', '/tournaments/hcm-open-2027/register');

        $response->assertOK();
        $response->assertSee('Thông tin');
        $response->assertSee('Eligibility');
        $response->assertSee('EVENT SUMMARY');
    }
}
