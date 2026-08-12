<?php

namespace Tests\Unit;

use App\Services\AvailabilityService;
use App\Services\DataQualityService;
use App\Services\QueueMonitorService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

class OperationsQualityServicesTest extends CIUnitTestCase
{
    public function testAvailabilityRequiresTenantContext(): void
    {
        $result = (new AvailabilityService())->slots(1, date('Y-m-d'), 0);
        $this->assertFalse($result['success']);
        $this->assertSame('TENANT_REQUIRED', $result['code']);
    }

    public function testDataQualityEmptyTenantIsSafe(): void
    {
        $report = (new DataQualityService())->report(0);
        $this->assertSame(0, $report['total_issues']);
        $this->assertSame([], $report['checks']);
    }

    public function testQueueMonitorEmptyTenantIsSafe(): void
    {
        $report = (new QueueMonitorService())->report(0);
        $this->assertSame([], $report['summary']);
        $this->assertSame([], $report['failed']);
    }

    public function testDataQualityFlagsRebuildQueueAnomalies(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('rating_rebuild_jobs')) {
            $this->markTestSkipped('Bảng rating_rebuild_jobs chưa tồn tại.');
            return;
        }

        $db->table('rating_rebuild_jobs')->where('tenant_id', 1)->whereIn('status', ['running', 'failed'])->delete();

        $runningJob = [
            'rating_provider_id' => 1,
            'tenant_id' => 1,
            'from_match_id' => null,
            'status' => 'running',
            'payload' => json_encode(['reason' => 'P3-QUALITY-stale']),
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 hours')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-10 hours')),
        ];
        if ($db->fieldExists('started_at', 'rating_rebuild_jobs')) {
            $runningJob['started_at'] = date('Y-m-d H:i:s', strtotime('-10 hours'));
        }
        if ($db->fieldExists('attempt_count', 'rating_rebuild_jobs')) {
            $runningJob['attempt_count'] = 0;
        }
        $db->table('rating_rebuild_jobs')->insert($runningJob);

        $failedJob = [
            'rating_provider_id' => 1,
            'tenant_id' => 1,
            'from_match_id' => null,
            'status' => 'failed',
            'payload' => json_encode(['reason' => 'P3-QUALITY-failed']),
            'error_message' => 'Manual test failure',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        ];
        if ($db->fieldExists('attempt_count', 'rating_rebuild_jobs')) {
            $failedJob['attempt_count'] = 2;
        }
        $db->table('rating_rebuild_jobs')->insert($failedJob);

        $report = (new DataQualityService())->report(1);
        $byCode = [];
        foreach ($report['checks'] as $check) {
            $byCode[$check['code']] = $check['count'];
        }
        $this->assertArrayHasKey('stale_rebuild_jobs', $byCode);
        $this->assertGreaterThanOrEqual(1, (int) $byCode['stale_rebuild_jobs']);
        $this->assertArrayHasKey('failed_rebuild_jobs', $byCode);
        $this->assertGreaterThanOrEqual(1, (int) $byCode['failed_rebuild_jobs']);
    }

    public function testDataQualityFlagsDisputeMatchMismatch(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('match_disputes') || ! $db->tableExists('matches') || ! $db->tableExists('match_results')) {
            $this->markTestSkipped('Bảng dispute/result chưa đủ cho kiểm tra governance consistency.');
            return;
        }

        $publicId = 'P3Q-' . strtoupper(bin2hex(random_bytes(4)));
        $db->table('matches')->insert([
            'tenant_id' => 1,
            'public_id' => $publicId,
            'source_type' => 'friendly',
            'discipline' => 'singles',
            'status' => 'official',
            'verification_status' => 'official',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $matchId = (int) $db->insertID();
        $db->table('match_results')->insert([
            'match_id' => $matchId,
            'status' => 'official',
            'version_no' => 1,
            'published_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('match_disputes')->insert([
            'tenant_id' => 1,
            'match_id' => $matchId,
            'opened_by' => 1,
            'reason_code' => 'result_dispute',
            'reason' => 'P3 quality mismatch fixture',
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $report = (new DataQualityService())->report(1);
        $byCode = [];
        foreach ($report['checks'] as $check) {
            $byCode[$check['code']] = $check['count'];
        }
        $this->assertArrayHasKey('dispute_match_status_inconsistent', $byCode);
        $this->assertGreaterThanOrEqual(1, (int) $byCode['dispute_match_status_inconsistent']);

        $db->table('match_disputes')->where('tenant_id', 1)->where('match_id', $matchId)->delete();
        $db->table('match_results')->where('match_id', $matchId)->delete();
        $db->table('matches')->where('id', $matchId)->delete();
    }

    public function testDataQualityFlagsDrawVersionConsistency(): void
    {
        $db = Database::connect();
        if (! $db->tableExists('tournament_draw_versions') || ! $db->tableExists('tournament_matches') || ! $db->tableExists('tournament_categories') || ! $db->tableExists('tournaments')) {
            $this->markTestSkipped('Thiếu bảng draw/version cho phép kiểm tra governance draw.');
            return;
        }
        if (! $db->fieldExists('draw_version_id', 'tournament_matches')) {
            $this->markTestSkipped('match chưa có draw_version_id để kiểm tra consistency.');
            return;
        }

        $tournament = $db->table('tournaments')
            ->where('tenant_id', 1)
            ->where('deleted_at', null)
            ->orderBy('id')
            ->get(1)
            ->getRowArray();
        if (! $tournament) {
            $this->markTestSkipped('Chưa có tournament tenant 1 cho môi trường test.');
            return;
        }

        $category = $db->table('tournament_categories')
            ->where('tenant_id', 1)
            ->where('tournament_id', (int) $tournament['id'])
            ->where('deleted_at', null)
            ->orderBy('id')
            ->get(1)
            ->getRowArray();
        if (! $category) {
            $this->markTestSkipped('Chưa có category cho tournament trong môi trường test.');
            return;
        }

        $createdVersionIds = [];
        $versionPayload = [
            'tenant_id' => 1,
            'tournament_id' => (int) $tournament['id'],
            'category_id' => (int) $category['id'],
            'draw_signature' => 'P4-' . bin2hex(random_bytes(6)),
            'draw_seed' => bin2hex(random_bytes(8)),
            'status' => 'active',
            'reason' => 'P4 governance test',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $db->table('tournament_draw_versions')->insert($versionPayload);
        $createdVersionIds[] = (int) $db->insertID();
        $db->table('tournament_draw_versions')->insert($versionPayload);
        $createdVersionIds[] = (int) $db->insertID();

        $report = (new DataQualityService())->report(1);
        $byCode = [];
        foreach ($report['checks'] as $check) {
            $byCode[$check['code']] = (int) $check['count'];
        }

        $this->assertArrayHasKey('draw_category_multi_active_version', $byCode);
        $this->assertGreaterThanOrEqual(1, (int) $byCode['draw_category_multi_active_version']);
        $this->assertArrayHasKey('draw_versions_without_active_matches', $byCode);
        $this->assertGreaterThanOrEqual(1, (int) $byCode['draw_versions_without_active_matches']);

        foreach ($createdVersionIds as $versionId) {
            $db->table('tournament_draw_versions')->where('id', $versionId)->delete();
        }
    }
}
