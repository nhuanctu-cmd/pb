<?php

namespace Tests\Unit;

use App\Contracts\RatingProviderInterface;
use App\Services\InternalRatingProvider;
use CodeIgniter\Test\CIUnitTestCase;

class TrustCompetitionFoundationTest extends CIUnitTestCase
{
    public function testRatingProviderContractIsProviderNeutral(): void
    {
        $methods = get_class_methods(RatingProviderInterface::class);

        $this->assertContains('findPlayer', $methods);
        $this->assertContains('getRatingHistory', $methods);
        $this->assertContains('submitMatchIfSupported', $methods);
        $this->assertContains('getReliabilityIfAvailable', $methods);
        $this->assertInstanceOf(RatingProviderInterface::class, new InternalRatingProvider());
    }

    public function testFoundationMigrationContainsAllFiveTrustBoundaries(): void
    {
        $migration = file_get_contents(APPPATH . 'Database/Migrations/2026-08-09-373000_CreateTrustCompetitionFoundations.php');

        foreach (['governance_authorities', 'ruleset_versions', 'data_provenance_records', 'player_rating_provider_links', 'match_sides'] as $table) {
            $this->assertStringContainsString($table, $migration);
        }
    }

    public function testArchitectureAuditDefinesRequiredDecisionOutputs(): void
    {
        $audit = file_get_contents(ROOTPATH . 'docs/FOUNDATION_TRUST_COMPETITION_AUDIT.md');

        foreach (['Current Governance Gap', 'Current Ruleset Gap', 'Provenance Data Flow', 'Unified Match Graph ERD', 'Migration Plan', 'Tests', 'Implementation Phases'] as $heading) {
            $this->assertStringContainsString($heading, $audit);
        }
    }
}
