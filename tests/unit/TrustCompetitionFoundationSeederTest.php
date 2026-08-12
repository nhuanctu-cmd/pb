<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

class TrustCompetitionFoundationSeederTest extends CIUnitTestCase
{
    public function testSeederDocumentsTheCompleteFoundationFixture(): void
    {
        $source = file_get_contents(APPPATH . 'Database/Seeds/TrustCompetitionFoundationSeeder.php');

        foreach (['governance_authorities', 'ruleset_versions', 'data_provenance_records', 'player_rating_provider_links', 'provider_rating_records', 'match_sides', 'match_integrity_flags', 'appeals', 'result_correction_requests'] as $table) {
            $this->assertStringContainsString($table, $source);
        }

        $this->assertStringContainsString('foundation-demo', $source);
        $this->assertStringContainsString('DEMO-RATING-', $source);
        $this->assertStringContainsString('DEMO-SANCTION-', $source);
    }

    public function testSeederIsWiredIntoCommercialDemoSeed(): void
    {
        $source = file_get_contents(APPPATH . 'Database/Seeds/CommercialDemoSeeder.php');

        $this->assertStringContainsString('TrustCompetitionFoundationSeeder', $source);
    }
}
