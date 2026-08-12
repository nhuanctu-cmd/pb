<?php

namespace Tests\Unit;

use App\Database\Migrations\SeedCanonicalProfilesFromLegacyRatings;
use App\Services\RatingImportService;
use App\Services\ResultCorrectionService;
use App\Services\UnifiedMatchService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class RatingWorkflowEdgeCasesTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        require_once APPPATH . 'Database/Migrations/2026-08-09-373000_SeedCanonicalProfilesFromLegacyRatings.php';
        require_once APPPATH . 'Database/Migrations/2026-08-09-370000_CreateRatingEngineV1.php';
        $db = Database::connect();
        if (! $db->tableExists('player_rating_profiles')) {
            (new \App\Database\Migrations\CreateRatingEngineV1(Database::forge('tests')))->up();
        }
        if (! $db->tableExists('result_correction_requests')) {
            $db->query("CREATE TABLE IF NOT EXISTS result_correction_requests (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, match_id INT UNSIGNED NOT NULL, original_result_version_id BIGINT UNSIGNED NOT NULL, requested_result JSON NOT NULL, reason TEXT NOT NULL, evidence JSON NULL, requester_id INT UNSIGNED NULL, reviewer_id INT UNSIGNED NULL, status VARCHAR(20) NOT NULL DEFAULT 'open', decision_reason TEXT NULL, new_result_version_id BIGINT UNSIGNED NULL, created_at DATETIME NULL, reviewed_at DATETIME NULL)");
        }
        if (! $db->tableExists('rating_import_jobs')) {
            $db->query("CREATE TABLE IF NOT EXISTS rating_import_jobs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, source_type VARCHAR(30) NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'uploaded', created_by INT UNSIGNED NULL, source_name VARCHAR(150) NULL, metadata JSON NULL, error_message TEXT NULL, created_at DATETIME NULL, updated_at DATETIME NULL)");
        }
        if (! $db->tableExists('rating_import_rows')) {
            $db->query("CREATE TABLE IF NOT EXISTS rating_import_rows (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, job_id BIGINT UNSIGNED NOT NULL, `row_number` INT UNSIGNED NOT NULL, raw_data JSON NOT NULL, normalized_data JSON NULL, player_id INT UNSIGNED NULL, identity_status VARCHAR(20) NOT NULL DEFAULT 'unmatched', validation_status VARCHAR(20) NOT NULL DEFAULT 'pending', verification_status VARCHAR(20) NOT NULL DEFAULT 'pending', validation_errors JSON NULL, created_at DATETIME NULL, updated_at DATETIME NULL)");
        }
        if (! $db->tableExists('player_skill_claims')) {
            $db->query('CREATE TABLE IF NOT EXISTS player_skill_claims (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NULL, player_id INT UNSIGNED NULL, discipline_id INT UNSIGNED NULL, source_type VARCHAR(30) NULL, claimed_rating DECIMAL(6,3) NULL, external_reference VARCHAR(120) NULL, verification_status VARCHAR(20) NULL, evidence JSON NULL, claimed_at DATETIME NULL, created_at DATETIME NULL, updated_at DATETIME NULL)');
        }
    }

    public function testCorrectionApprovalAppendsVersionAndReversesOriginalImpact(): void
    {
        $db = Database::connect();
        $playerOne = $this->createPlayer($db, 'Correction Test A');
        $playerTwo = $this->createPlayer($db, 'Correction Test B');
        $matches = new UnifiedMatchService();

        $created = $matches->create([
            'source_type' => 'friendly',
            'participants' => [
                ['player_id' => $playerOne, 'side' => 1],
                ['player_id' => $playerTwo, 'side' => 2],
            ],
        ], 1, 1);
        $this->assertTrue($created['success']);
        $matchId = (int) $created['match']['match']->id;
        $this->assertTrue($matches->submitResult($matchId, ['winner_side' => 1, 'games' => [['side_a_score' => 11, 'side_b_score' => 8]]], 1, 1)['success']);
        $this->assertTrue($matches->confirmResult($matchId, 1, 1)['success']);
        $this->assertTrue($matches->publishOfficial($matchId, 1, 1)['success']);
        $this->assertTrue(service('ratingNetworkService')->applyOfficialMatch($matchId, 1)['success']);

        $corrections = new ResultCorrectionService();
        $requested = $corrections->request($matchId, 1, ['winner_side' => 2, 'games' => [['side_a_score' => 8, 'side_b_score' => 11]]], 'Official score correction', ['source' => 'referee']);
        $this->assertTrue($requested['success']);

        $approved = $corrections->approve((int) $requested['request']->id, 1, 'Verified against score sheet', 1);
        $this->assertTrue($approved['success']);
        $this->assertNotSame(1, (int) $approved['new_result_version_id']);
        $this->assertSame('approved', $approved['request']->status);
        $this->assertGreaterThan(0, $db->table('rating_transactions')->where('match_id', $matchId)->where('transaction_type', 'reversal')->countAllResults());
    }

    public function testImportMarksRepeatedExternalReferenceAsDuplicate(): void
    {
        $db = Database::connect();
        $playerId = $this->createPlayer($db, 'Import Duplicate Test');
        $import = new RatingImportService();
        $rows = [
            ['player_id' => $playerId, 'full_name' => 'Import Duplicate Test', 'rating' => 3.2, 'discipline' => 'singles', 'external_reference' => 'provider-match-001'],
            ['player_id' => $playerId, 'full_name' => 'Import Duplicate Test', 'rating' => 3.2, 'discipline' => 'singles', 'external_reference' => 'provider-match-001'],
        ];

        $uploaded = $import->upload(1, 'club', $rows, 1, 'duplicate-fixture');
        $this->assertTrue($uploaded['success']);
        $this->assertTrue($import->preview(1, (int) $uploaded['job_id'])['success']);
        $this->assertTrue($import->matchIdentities(1, (int) $uploaded['job_id'])['success']);

        $statuses = $db->table('rating_import_rows')->select('identity_status')->where('job_id', (int) $uploaded['job_id'])->orderBy('row_number')->get()->getResultArray();
        $this->assertSame(['matched', 'duplicate'], array_column($statuses, 'identity_status'));
    }

    public function testLegacyBackfillCreatesIdempotentSeedTransaction(): void
    {
        $db = Database::connect();
        $playerId = $this->createPlayer($db, 'Legacy Backfill Test');
        $db->table('player_ratings')->where('player_id', $playerId)->delete();
        $db->table('rating_transactions')->where('player_id', $playerId)->where('reason', 'LEGACY_RATING_MIGRATION')->delete();
        $db->table('player_rating_profiles')->where('player_id', $playerId)->delete();
        $db->table('player_ratings')->insert(['tenant_id' => 1, 'player_id' => $playerId, 'scope_type' => 'global', 'scope_id' => null, 'rating_type' => 'elo', 'rating' => 1200, 'games_played' => 12, 'wins' => 8, 'losses' => 4, 'last_match_at' => '2026-08-01 10:00:00']);

        // Migration uses the configured default connection; Database::forge()
        // is not a Forge instance in the long-lived snapshot test harness.
        $migration = new SeedCanonicalProfilesFromLegacyRatings();
        $migration->up();
        $migration->up();

        $seedRows = $db->table('rating_transactions')->where('tenant_id', 1)->where('player_id', $playerId)->where('transaction_type', 'seed')->where('reason', 'LEGACY_RATING_MIGRATION')->get()->getResult();
        $this->assertCount(1, $seedRows);
        $this->assertSame(4.0, (float) $seedRows[0]->after_rating);
        $this->assertSame(1, $db->table('player_rating_profiles')->where('tenant_id', 1)->where('player_id', $playerId)->countAllResults());
    }

    private function createPlayer($db, string $name): int
    {
        $db->table('players')->insert(['tenant_id' => 1, 'player_code' => 'EDGE-' . strtoupper(bin2hex(random_bytes(4))), 'full_name' => $name, 'status' => 'active']);
        return (int) $db->insertID();
    }
}
