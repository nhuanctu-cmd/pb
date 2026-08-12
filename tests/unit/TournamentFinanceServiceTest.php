<?php

namespace Tests\Unit;

use App\Services\TournamentFinanceService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class TournamentFinanceServiceTest extends CIUnitTestCase
{
    public function testTournamentFinanceSummaryReturnsConsistentShapeWithLegacyTenantSchema(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('tournaments') || ! $db->tableExists('tournament_registrations')) {
            $this->markTestSkipped('Chưa đủ bảng tournament cho môi trường test.');
        }

        $tournament = $db->table('tournaments')
            ->where('tenant_id', 1)
            ->where('deleted_at', null)
            ->orderBy('id', 'ASC')
            ->get(1)
            ->getRow();

        if (! $tournament) {
            $this->markTestSkipped('Chưa có tournament sample cho tenant 1.');
        }

        $summary = (new TournamentFinanceService())->summary(1, (int) $tournament->id);

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('source', $summary);
        $this->assertArrayHasKey('currency', $summary);
        $this->assertArrayHasKey('registration_count', $summary);
        $this->assertArrayHasKey('expected_revenue', $summary);
        $this->assertArrayHasKey('collected_revenue', $summary);
        $this->assertArrayHasKey('outstanding_revenue', $summary);
        $this->assertNotEmpty($summary['currency']);
        $this->assertSame(3, strlen((string) $summary['currency']));
    }
}

