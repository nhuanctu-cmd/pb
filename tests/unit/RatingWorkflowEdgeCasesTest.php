<?php

namespace Tests\Unit;

use App\Database\Migrations\SeedCanonicalProfilesFromLegacyRatings;
use App\Services\MatchGovernanceService;
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
        $fixtureReferences = ['import-gov-approve', 'import-gov-reject', 'provider-match-001', 'canonical-demo-match-001'];
        $fixtureJobs = ['duplicate-fixture', 'governance-approve', 'governance-reject'];
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
            $db->query('CREATE TABLE IF NOT EXISTS player_skill_claims (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NULL, player_id INT UNSIGNED NULL, discipline_id INT UNSIGNED NULL, source_type VARCHAR(30) NULL, source_organization_id INT UNSIGNED NULL, source_user_id INT UNSIGNED NULL, claimed_rating DECIMAL(6,3) NULL, external_provider VARCHAR(50) NULL, external_reference VARCHAR(120) NULL, verification_status VARCHAR(20) NULL, notes TEXT NULL, evidence JSON NULL, claimed_at DATETIME NULL, expires_at DATETIME NULL, created_at DATETIME NULL, updated_at DATETIME NULL)');
        } else {
            $columns = ['source_organization_id', 'source_user_id', 'external_provider', 'notes', 'expires_at'];
            foreach ($columns as $column) {
                if (! $db->fieldExists($column, 'player_skill_claims')) {
                    $alter = match ($column) {
                        'source_organization_id', 'source_user_id' => "ADD COLUMN {$column} INT UNSIGNED NULL",
                        'external_provider' => "ADD COLUMN external_provider VARCHAR(50) NULL",
                        'notes' => "ADD COLUMN notes TEXT NULL",
                        default => "ADD COLUMN {$column} DATETIME NULL",
                    };
                    $db->query("ALTER TABLE player_skill_claims {$alter}");
                }
            }
        }
        if ($db->tableExists('player_skill_claims')) {
            foreach ($fixtureReferences as $reference) {
                $db->table('player_skill_claims')->where('external_reference', $reference)->delete();
            }
        }
        if ($db->tableExists('rating_import_jobs')) {
            if ($db->table('rating_import_jobs')->whereIn('source_name', $fixtureJobs)->countAllResults() > 0) {
                $jobIds = array_column($db->table('rating_import_jobs')->select('id')->whereIn('source_name', $fixtureJobs)->get()->getResultArray(), 'id');
                if ($jobIds) {
                    $db->table('rating_import_rows')->whereIn('job_id', $jobIds)->delete();
                }
            }
            $db->table('rating_import_jobs')->whereIn('source_name', $fixtureJobs)->delete();
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

    public function testDoublesMatchWritesFourImpactRowsUsingTeamAverageInputs(): void
    {
        $db = Database::connect();
        $playerIds = [
            $this->createPlayer($db, 'Doubles Test A1'),
            $this->createPlayer($db, 'Doubles Test A2'),
            $this->createPlayer($db, 'Doubles Test B1'),
            $this->createPlayer($db, 'Doubles Test B2'),
        ];
        $matches = new UnifiedMatchService();
        $created = $matches->create([
            'source_type' => 'friendly',
            'discipline' => 'doubles',
            'participants' => [
                ['player_id' => $playerIds[0], 'side' => 1],
                ['player_id' => $playerIds[1], 'side' => 1],
                ['player_id' => $playerIds[2], 'side' => 2],
                ['player_id' => $playerIds[3], 'side' => 2],
            ],
        ], 1, 1);
        $this->assertTrue($created['success']);
        $matchId = (int) $created['match']['match']->id;

        $this->assertTrue($matches->submitResult($matchId, ['winner_side' => 1, 'games' => [['side_a_score' => 11, 'side_b_score' => 9]]], 1, 1)['success']);
        $this->assertTrue($matches->confirmResult($matchId, 1, 1)['success']);
        $this->assertTrue($matches->publishOfficial($matchId, 1, 1)['success']);
        $this->assertTrue(service('ratingNetworkService')->applyOfficialMatch($matchId, 1)['success']);

        $impactRows = $db->table('rating_transactions')->where('match_id', $matchId)->where('transaction_type', 'impact')->get()->getResult();
        $this->assertCount(4, $impactRows);
        foreach ($impactRows as $row) {
            $participant = $db->table('match_participants')->where('match_id', $matchId)->where('player_id', (int) $row->player_id)->get()->getRow();
            $this->assertNotNull($participant);
            if ((int) $participant->side === 1) {
                $this->assertGreaterThan(0, (float) $row->rating_delta, 'Winner side players should gain rating in doubles match.');
            } else {
                $this->assertLessThan(0, (float) $row->rating_delta, 'Loser side players should lose rating in doubles match.');
            }
        }
    }

    public function testGovernanceImportApproveAndRejectFlow(): void
    {
        $db = Database::connect();
        $playerId = $this->createPlayer($db, 'Import Governance Test');
        $import = new RatingImportService();

        $approveRows = [
            ['player_id' => $playerId, 'full_name' => 'Import Governance Test', 'rating' => 3.5, 'discipline' => 'singles', 'external_reference' => 'import-gov-approve'],
        ];
        $approveUpload = $import->upload(1, 'club', $approveRows, 1, 'governance-approve');
        $this->assertTrue($approveUpload['success']);
        $jobIdApprove = (int) $approveUpload['job_id'];
        $this->assertTrue($import->preview(1, $jobIdApprove)['success']);
        $this->assertTrue($import->matchIdentities(1, $jobIdApprove)['success']);
        $this->assertTrue($import->validate(1, $jobIdApprove)['success']);
        $approved = $import->reviewForGovernance(1, $jobIdApprove, 'approve', null, 1);
        $this->assertTrue($approved['success']);
        $this->assertSame('imported', $approved['status']);
        $approvedClaims = (int) $db->table('player_skill_claims')
            ->where('player_id', $playerId)
            ->where('tenant_id', 1)
            ->where('external_reference', 'import-gov-approve')
            ->where('verification_status', 'verified')
            ->countAllResults();
        $this->assertSame(1, $approvedClaims);

        $rejectRows = [
            ['player_id' => $playerId, 'full_name' => 'Import Governance Test', 'rating' => 3.1, 'discipline' => 'singles', 'external_reference' => 'import-gov-reject'],
        ];
        $rejectUpload = $import->upload(1, 'club', $rejectRows, 1, 'governance-reject');
        $this->assertTrue($rejectUpload['success']);
        $jobIdReject = (int) $rejectUpload['job_id'];
        $this->assertTrue($import->preview(1, $jobIdReject)['success']);
        $this->assertTrue($import->matchIdentities(1, $jobIdReject)['success']);
        $this->assertTrue($import->validate(1, $jobIdReject)['success']);

        $rejectMissing = $import->reviewForGovernance(1, $jobIdReject, 'reject', '', 1);
        $this->assertFalse($rejectMissing['success']);
        $rejected = $import->reviewForGovernance(1, $jobIdReject, 'reject', 'Reference quality not enough', 1);
        $this->assertTrue($rejected['success']);
        $this->assertSame('rejected', $rejected['status']);
        $this->assertSame(1, (int) $db->table('rating_import_jobs')->where('id', $jobIdReject)->where('status', 'rejected')->countAllResults());
    }

    public function testRatingHistoryIncludesSeedAndAdjustmentTransactions(): void
    {
        $db = Database::connect();
        $playerId = $this->createPlayer($db, 'History Tx Test');
        $db->table('player_ratings')->where('player_id', $playerId)->delete();
        $db->table('rating_transactions')->where('player_id', $playerId)->where('reason', 'LEGACY_RATING_MIGRATION')->delete();
        $db->table('player_rating_profiles')->where('player_id', $playerId)->delete();
        $db->table('player_ratings')->insert([
            'tenant_id' => 1,
            'player_id' => $playerId,
            'scope_type' => 'global',
            'scope_id' => null,
            'rating_type' => 'elo',
            'rating' => 1250,
            'games_played' => 20,
            'wins' => 13,
            'losses' => 7,
            'last_match_at' => '2026-08-01 10:00:00',
        ]);

        $migration = new SeedCanonicalProfilesFromLegacyRatings();
        $migration->up();
        $seedHistory = service('ratingEngine')->history(1, $playerId, 'singles', 20);
        $types = array_map(static fn (object $row): string => (string) $row->transaction_type, $seedHistory);
        $this->assertContains('seed', $types);

        $adjusted = service('ratingAdjustmentService')->adjust(1, $playerId, 'singles', 4.250, 'P1 history test adjustment', 1);
        $this->assertTrue($adjusted['success']);

        $history = service('ratingEngine')->history(1, $playerId, 'singles', 20);
        $types = array_map(static fn (object $row): string => (string) $row->transaction_type, $history);
        $this->assertContains('adjustment', $types);
        $this->assertGreaterThanOrEqual(2, count($types), 'Mục tiêu lịch sử nên có ít nhất giao dịch seed + adjustment.');
        $this->assertArrayHasKey('transaction_type', (array) $history[0]);
        $this->assertArrayHasKey('processed_at', (array) $history[0]);
        $this->assertArrayHasKey('reason', (array) $history[0]);

        $timestamps = array_map(static fn (object $row): string => (string) $row->created_at, $history);
        $sorted = $timestamps;
        rsort($sorted, SORT_STRING);
        $this->assertSame($sorted, $timestamps, 'Lịch sử rating phải trả về theo thứ tự mới nhất trước cho biểu đồ timeline.');
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

    public function testImportIdentityMatchingUsesNationalIdAndClaims(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('player_competitive_profiles') || ! $db->tableExists('player_identity_claims')) {
            $this->markTestSkipped('Bảng nhận diện chưa được migrate trong môi trường test hiện tại.');
            return;
        }
        if (! $db->tableExists('player_skill_claims')) {
            $this->markTestSkipped('player_skill_claims chưa được migrate trong môi trường test hiện tại.');
            return;
        }

        $playerOne = $this->createPlayer($db, 'Import Identity Player One');
        $playerTwo = $this->createPlayer($db, 'Import Identity Player Two');
        $db->table('player_identity_claims')->whereIn('claim_value', ['claims2@test.dev', '0358000001'])->delete();
        $db->table('player_competitive_profiles')->where('national_player_id', 'VN-NAT-001')->delete();
        $db->table('player_competitive_profiles')->where('player_id', $playerOne)->delete();

        $db->table('player_competitive_profiles')->insert([
            'player_id' => $playerOne,
            'national_player_id' => 'VN-NAT-001',
            'display_name' => 'Import Identity Player One',
            'province_id' => null,
            'city_id' => null,
            'club_id' => null,
            'status' => 'verified',
            'created_at' => '2026-08-09 10:00:00',
            'updated_at' => '2026-08-09 10:00:00',
            'deleted_at' => null,
        ]);

        $db->table('player_identity_claims')->insert([
            'player_id' => $playerTwo,
            'claim_type' => 'email',
            'claim_value' => 'claims2@test.dev',
            'is_primary' => 1,
            'created_at' => '2026-08-09 10:00:00',
            'updated_at' => '2026-08-09 10:00:00',
        ]);
        $db->table('player_identity_claims')->insert([
            'player_id' => $playerTwo,
            'claim_type' => 'phone',
            'claim_value' => '0358000001',
            'is_primary' => 1,
            'created_at' => '2026-08-09 10:00:00',
            'updated_at' => '2026-08-09 10:00:00',
        ]);

        $import = new RatingImportService();
        $rows = [
            [
                'full_name' => 'Import Identity Player One',
                'rating' => 3.9,
                'discipline' => 'singles',
                'national_player_id' => 'VN-NAT-001',
                'external_reference' => 'identity-nat-001',
            ],
            [
                'full_name' => 'Không trùng',
                'rating' => 3.6,
                'discipline' => 'singles',
                'email' => 'claims2@test.dev',
                'phone' => '0358000001',
                'external_reference' => 'identity-claim-001',
            ],
        ];
        $upload = $import->upload(1, 'external_provider', $rows, 1, 'identity-matching');
        $this->assertTrue($upload['success']);
        $this->assertTrue($import->preview(1, (int) $upload['job_id'])['success']);
        $this->assertTrue($import->matchIdentities(1, (int) $upload['job_id'])['success']);

        $rowsStatus = $db->table('rating_import_rows')->select('row_number, identity_status, player_id')->where('job_id', (int) $upload['job_id'])->orderBy('row_number')->get()->getResultArray();
        $this->assertSame(2, count($rowsStatus));
        $this->assertSame('matched', $rowsStatus[0]['identity_status']);
        $this->assertSame((string) $playerOne, (string) $rowsStatus[0]['player_id']);
        $this->assertSame('matched', $rowsStatus[1]['identity_status']);
        $this->assertSame((string) $playerTwo, (string) $rowsStatus[1]['player_id']);
    }

    public function testDisputedMatchReversalFlowMarksMatchAndTransactions(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('match_disputes') || ! $db->tableExists('match_results')) {
            $this->markTestSkipped('Bảng dispute/result chưa được migrate trong môi trường test hiện tại.');
            return;
        }

        $playerOne = $this->createPlayer($db, 'Dispute Match A');
        $playerTwo = $this->createPlayer($db, 'Dispute Match B');
        $matches = new UnifiedMatchService();
        $governance = new MatchGovernanceService();

        $created = $matches->create([
            'source_type' => 'friendly',
            'discipline' => 'singles',
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

        $this->assertSame(2, (int) $db->table('rating_transactions')->where('match_id', $matchId)->where('transaction_type', 'impact')->where('status', 'applied')->countAllResults());

        $opened = $governance->open($matchId, 1, 1, [
            'reason' => 'Kết quả được phản ánh sai điểm.',
            'reason_code' => 'result_dispute',
        ]);
        $this->assertTrue($opened['success']);
        $this->assertSame('disputed', (string) $db->table('matches')->where('id', $matchId)->get()->getRow()->status);
        $this->assertSame('disputed', (string) $db->table('match_results')->where('match_id', $matchId)->get()->getRow()->status);

        $resolved = $governance->resolve((int) $opened['dispute']->id, 1, 1, 'upheld', 'Phản ánh sự kiện sai lệch dữ liệu từ source.');
        $this->assertTrue($resolved['success']);
        $this->assertSame('upheld', (string) $db->table('match_disputes')->where('id', (int) $opened['dispute']->id)->get()->getRow()->status);

        $this->assertSame(2, (int) $db->table('rating_transactions')->where('match_id', $matchId)->where('transaction_type', 'reversal')->where('status', 'applied')->countAllResults());
        $this->assertSame(2, (int) $db->table('rating_transactions')->where('match_id', $matchId)->where('transaction_type', 'impact')->where('status', 'reversed')->countAllResults());

        if (! $db->tableExists('rating_rebuild_jobs')) {
            $this->assertTrue(true);
            return;
        }
        $queued = $db->table('rating_rebuild_jobs')
            ->where('tenant_id', 1)
            ->where('from_match_id', $matchId)
            ->where('status', 'queued')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();
        $this->assertNotNull($queued);
        $payload = json_decode((string) $queued->payload, true) ?: [];
        $this->assertSame('dispute-upheld', (string) ($payload['reason'] ?? ''));
    }

    public function testDisputeRejectKeepsMatchOfficialAndPreservesImpact(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('match_disputes') || ! $db->tableExists('match_results')) {
            $this->markTestSkipped('Bảng dispute/result chưa được migrate trong môi trường test hiện tại.');
            return;
        }

        $playerOne = $this->createPlayer($db, 'Dispute Reject Match A');
        $playerTwo = $this->createPlayer($db, 'Dispute Reject Match B');
        $matches = new UnifiedMatchService();
        $governance = new MatchGovernanceService();

        $created = $matches->create([
            'source_type' => 'friendly',
            'discipline' => 'singles',
            'participants' => [
                ['player_id' => $playerOne, 'side' => 1],
                ['player_id' => $playerTwo, 'side' => 2],
            ],
        ], 1, 1);
        $this->assertTrue($created['success']);
        $matchId = (int) $created['match']['match']->id;

        $this->assertTrue($matches->submitResult($matchId, ['winner_side' => 1, 'games' => [['side_a_score' => 11, 'side_b_score' => 6]]], 1, 1)['success']);
        $this->assertTrue($matches->confirmResult($matchId, 1, 1)['success']);
        $this->assertTrue($matches->publishOfficial($matchId, 1, 1)['success']);
        $this->assertTrue(service('ratingNetworkService')->applyOfficialMatch($matchId, 1)['success']);

        $opened = $governance->open($matchId, 1, 1, ['reason' => 'Lệch điểm do mạng.', 'reason_code' => 'result_dispute']);
        $this->assertTrue($opened['success']);
        $this->assertSame('disputed', (string) $db->table('matches')->where('id', $matchId)->get()->getRow()->status);

        $resolved = $governance->resolve((int) $opened['dispute']->id, 1, 1, 'rejected', 'Không đủ bằng chứng.');
        $this->assertTrue($resolved['success']);
        $this->assertSame('rejected', (string) $db->table('match_disputes')->where('id', (int) $opened['dispute']->id)->get()->getRow()->status);
        $this->assertSame('official', (string) $db->table('matches')->where('id', $matchId)->get()->getRow()->status);
        $this->assertSame('official', (string) $db->table('match_results')->where('match_id', $matchId)->get()->getRow()->status);
        $this->assertSame(0, (int) $db->table('rating_transactions')->where('match_id', $matchId)->where('transaction_type', 'reversal')->where('status', 'applied')->countAllResults());
        $this->assertSame(2, (int) $db->table('rating_transactions')->where('match_id', $matchId)->where('transaction_type', 'impact')->where('status', 'applied')->countAllResults());
    }

    public function testCorrectionApprovalEnqueuesRatingRebuildAfterGovernanceCorrection(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('rating_rebuild_jobs')) {
            $this->markTestSkipped('Bảng rating_rebuild_jobs chưa được migrate trong môi trường test hiện tại.');
            return;
        }
        if (! $db->tableExists('result_correction_requests')) {
            $this->markTestSkipped('Bảng correction requests chưa được migrate trong môi trường test hiện tại.');
            return;
        }

        $playerOne = $this->createPlayer($db, 'Rebuild Queue Player One');
        $playerTwo = $this->createPlayer($db, 'Rebuild Queue Player Two');
        $matches = new UnifiedMatchService();
        $corrections = new ResultCorrectionService();

        $created = $matches->create([
            'source_type' => 'friendly',
            'discipline' => 'singles',
            'participants' => [
                ['player_id' => $playerOne, 'side' => 1],
                ['player_id' => $playerTwo, 'side' => 2],
            ],
        ], 1, 1);
        $this->assertTrue($created['success']);
        $matchId = (int) $created['match']['match']->id;

        $this->assertTrue($matches->submitResult($matchId, ['winner_side' => 1, 'games' => [['side_a_score' => 11, 'side_b_score' => 9]]], 1, 1)['success']);
        $this->assertTrue($matches->confirmResult($matchId, 1, 1)['success']);
        $this->assertTrue($matches->publishOfficial($matchId, 1, 1)['success']);
        $this->assertTrue(service('ratingNetworkService')->applyOfficialMatch($matchId, 1)['success']);

        $requested = $corrections->request($matchId, 1, ['winner_side' => 2, 'games' => [['side_a_score' => 9, 'side_b_score' => 11]]], 'Score correction in test', ['source' => 'integration']);
        $this->assertTrue($requested['success']);

        $db->table('rating_rebuild_jobs')->where('tenant_id', 1)->where('from_match_id', $matchId)->delete();
        $approved = $corrections->approve((int) $requested['request']->id, 1, 'Đã đối chiếu video', 1);
        $this->assertTrue($approved['success']);

        $queuedJob = $db->table('rating_rebuild_jobs')
            ->where('tenant_id', 1)
            ->where('from_match_id', $matchId)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();
        $this->assertNotNull($queuedJob);
        $this->assertSame('queued', (string) $queuedJob->status);
        $payload = json_decode((string) $queuedJob->payload, true) ?: [];
        $this->assertSame('correction-approved', (string) ($payload['reason'] ?? ''));
    }

    public function testRatingRebuildRejectsInvalidMatchRangeFilters(): void
    {
        $service = new \App\Services\RatingRebuildService();
        $result = $service->run([
            'tenant_id' => 1,
            'from_match_id' => 20,
            'to_match_id' => 10,
        ]);
        $this->assertFalse($result['success']);
        $this->assertSame('from_match_id phải nhỏ hơn hoặc bằng to_match_id.', $result['message']);
    }

    public function testRatingRebuildQueueFromMatchIsIdempotentByKey(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('rating_rebuild_jobs')) {
            $this->markTestSkipped('Bảng rating_rebuild_jobs chưa có trong môi trường test.');
            return;
        }
        if (! $db->tableExists('rating_providers')) {
            $this->markTestSkipped('Bảng rating_providers chưa có trong môi trường test.');
            return;
        }

        $service = new \App\Services\RatingRebuildService();
        $tenantId = 1;
        $fromMatchId = 900000 + random_int(0, 99999);
        $db->table('rating_rebuild_jobs')->where('tenant_id', $tenantId)->where('from_match_id', $fromMatchId)->like('payload', '"reason":"p3-rebuild-idem"', 'both')->delete();

        $first = $service->queueFromMatch($tenantId, $fromMatchId, null, 'p3-rebuild-idem', ['discipline' => 'singles', 'provider' => 'internal-v1']);
        $second = $service->queueFromMatch($tenantId, $fromMatchId, null, 'p3-rebuild-idem', ['discipline' => 'singles', 'provider' => 'internal-v1']);
        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first, $second);
        $this->assertSame(1, (int) $db->table('rating_rebuild_jobs')
            ->where('tenant_id', $tenantId)
            ->where('id', $first)
            ->countAllResults());
        $db->table('rating_rebuild_jobs')->where('id', $first)->delete();
    }

    public function testRatingRebuildProcessQueuedJobMarksCompleted(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('rating_rebuild_jobs')) {
            $this->markTestSkipped('Bảng rating_rebuild_jobs chưa có trong môi trường test.');
            return;
        }

        $service = new \App\Services\RatingRebuildService();
        $playerOne = $this->createPlayer($db, 'Replay Match A');
        $playerTwo = $this->createPlayer($db, 'Replay Match B');
        $matches = new UnifiedMatchService();

        $created = $matches->create([
            'source_type' => 'friendly',
            'discipline' => 'singles',
            'participants' => [
                ['player_id' => $playerOne, 'side' => 1],
                ['player_id' => $playerTwo, 'side' => 2],
            ],
        ], 1, 1);
        $this->assertTrue($created['success']);
        $matchId = (int) $created['match']['match']->id;
        $this->assertTrue($matches->submitResult($matchId, ['winner_side' => 1, 'games' => [['side_a_score' => 11, 'side_b_score' => 9]]], 1, 1)['success']);
        $this->assertTrue($matches->confirmResult($matchId, 1, 1)['success']);
        $this->assertTrue($matches->publishOfficial($matchId, 1, 1)['success']);
        $this->assertTrue(service('ratingNetworkService')->applyOfficialMatch($matchId, 1)['success']);

        $db->table('rating_rebuild_jobs')->where('tenant_id', 1)->where('status', 'queued')->like('payload', '"reason":"p3-rebuild-process"', 'both')->delete();
        $jobId = $service->queueFromMatch(1, $matchId, null, 'p3-rebuild-process', ['discipline' => 'singles', 'provider' => 'internal-v1']);
        $this->assertIsInt($jobId);

        $processResult = $service->processQueuedJobs(['tenant_id' => 1, 'limit' => 10]);
        $this->assertTrue($processResult['success']);
        $this->assertGreaterThanOrEqual(1, (int) ($processResult['processed_jobs'] ?? 0));
        $job = $db->table('rating_rebuild_jobs')->where('id', $jobId)->get()->getRow();
        $this->assertNotNull($job);
        $this->assertSame('completed', (string) $job->status);
        if (property_exists($job, 'completed_at')) {
            $this->assertNotEmpty((string) $job->completed_at);
        } else {
            $this->assertSame('completed', (string) $job->status);
        }
    }

    public function testRatingRebuildDryRunDoesNotPersistProfilesWhenNoMatches(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('player_rating_profiles') || ! $db->tableExists('rating_rebuild_jobs')) {
            $this->markTestSkipped('Chưa đủ bảng nền tảng để chạy rebuild dry-run.');
            return;
        }

        $initial = $db->table('player_rating_profiles')->where('tenant_id', 1)->countAllResults();
        $result = (new \App\Services\RatingRebuildService())->run([
            'tenant_id' => 1,
            'dry_run' => true,
            'from_match_id' => 999999,
            'to_match_id' => 999999,
        ]);
        $this->assertTrue($result['success']);
        $this->assertTrue((bool) $result['dry_run']);
        $this->assertSame($initial, (int) $db->table('player_rating_profiles')->where('tenant_id', 1)->countAllResults());
    }

    private function createPlayer($db, string $name): int
    {
        $db->table('players')->insert(['tenant_id' => 1, 'player_code' => 'EDGE-' . strtoupper(bin2hex(random_bytes(4))), 'full_name' => $name, 'status' => 'active']);
        return (int) $db->insertID();
    }
}
