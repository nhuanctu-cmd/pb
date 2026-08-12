<?php

namespace Tests\Unit;

use App\Services\TournamentPrintCenterService;
use CodeIgniter\Test\CIUnitTestCase;

class TournamentPrintCenterServiceTest extends CIUnitTestCase
{
    public function testPrintCatalogContainsAllOperationalDocuments(): void
    {
        $this->assertCount(12, TournamentPrintCenterService::DOCUMENT_TYPES);
        $this->assertContains('participants', TournamentPrintCenterService::DOCUMENT_TYPES);
        $this->assertContains('checkin', TournamentPrintCenterService::DOCUMENT_TYPES);
        $this->assertContains('bracket', TournamentPrintCenterService::DOCUMENT_TYPES);
    }

    public function testInvalidDocumentCannotReadTournamentData(): void
    {
        $this->assertNull((new TournamentPrintCenterService())->getDocument('not-a-document', 1, 1));
    }

    public function testTournamentPaginationNormalizesInputAndKeepsTenantScope(): void
    {
        $result = (new TournamentPrintCenterService())->getTournamentsPaginated(999999, 0, 100, 'not-found');

        $this->assertSame(0, $result['total']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(48, $result['perPage']);
        $this->assertSame(1, $result['pages']);
        $this->assertSame([], $result['items']);
    }
}
