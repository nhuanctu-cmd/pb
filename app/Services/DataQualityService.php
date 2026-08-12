<?php

namespace App\Services;

use Config\Database;

/** Read-only tenant data health checks for operations and platform support. */
class DataQualityService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function report(int $tenantId): array
    {
        if ($tenantId <= 0) return ['tenant_id' => $tenantId, 'total_issues' => 0, 'checks' => []];
        $checks = [];
        $this->add($checks, 'migration_tournament_draw_versions', 'Migration draw version chưa đồng bộ với schema', 'warning', $this->migrationCheck('2026-08-12-220000_AddTournamentDrawVersions', 'tournament_draw_versions', 'draw_version_id', 'tournament_matches'));
        $this->add($checks, 'migration_rating_rebuild_job_hardening', 'Migration hardening rebuild jobs chưa đồng bộ', 'warning', $this->migrationCheck('2026-08-12-210000_HardenRatingRebuildJobs', 'rating_rebuild_jobs', 'idempotency_key', 'rating_rebuild_jobs'));
        $this->add($checks, 'orphan_bookings', 'Booking thiếu branch cùng tenant', 'critical', $this->count("SELECT COUNT(*) total FROM bookings b LEFT JOIN branches br ON br.id = b.branch_id WHERE b.tenant_id = ? AND (br.id IS NULL OR br.tenant_id <> b.tenant_id)", [$tenantId]));
        $this->add($checks, 'cross_tenant_booking_items', 'Booking item lệch tenant với booking/court', 'critical', $this->count("SELECT COUNT(*) total FROM booking_items bi JOIN bookings b ON b.id = bi.booking_id LEFT JOIN courts c ON c.id = bi.court_id WHERE b.tenant_id = ? AND (bi.tenant_id <> b.tenant_id OR c.id IS NULL OR c.tenant_id <> b.tenant_id)", [$tenantId]));
        $this->add($checks, 'overlapping_bookings', 'Booking active bị chồng giờ trên cùng sân', 'warning', $this->count("SELECT COUNT(*) total FROM booking_items a JOIN booking_items b ON b.court_id = a.court_id AND b.id > a.id AND b.start_time < a.end_time AND b.end_time > a.start_time JOIN bookings ba ON ba.id = a.booking_id JOIN bookings bb ON bb.id = b.booking_id AND bb.booking_date = ba.booking_date WHERE ba.tenant_id = ? AND ba.booking_date >= CURDATE() AND ba.deleted_at IS NULL AND bb.deleted_at IS NULL AND a.status = 'active' AND b.status = 'active' AND ba.status NOT IN ('cancelled','refunded','expired') AND bb.status NOT IN ('cancelled','refunded','expired')", [$tenantId]));
        // player_competitive_profiles is keyed by player_id and intentionally has no tenant_id.
        // Tenant isolation comes from the scoped players row; ignore soft-deleted profiles.
        $this->add($checks, 'players_without_profile', 'Player chưa có competitive profile', 'info', $this->count("SELECT COUNT(*) total FROM players p LEFT JOIN player_competitive_profiles cp ON cp.player_id = p.id AND cp.deleted_at IS NULL WHERE p.tenant_id = ? AND p.deleted_at IS NULL AND cp.id IS NULL", [$tenantId], 'players', 'player_competitive_profiles'));
        $this->add($checks, 'open_integrity_flags', 'Rating integrity flag chưa xử lý', 'warning', $this->count("SELECT COUNT(*) total FROM rating_integrity_flags WHERE tenant_id = ? AND status = 'open'", [$tenantId], 'rating_integrity_flags'));
        if ($this->hasColumn('matches', 'provenance_id')) {
            $this->add($checks, 'official_without_provenance', 'Match official thiếu provenance', 'warning', $this->count("SELECT COUNT(*) total FROM matches WHERE tenant_id = ? AND status = 'official' AND (provenance_id IS NULL OR provenance_id = 0)", [$tenantId], 'matches'));
        } else {
            $this->add($checks, 'official_without_provenance', 'Match official thiếu provenance', 'warning', 0);
        }
        $hasRatingRebuildTable = $this->db->tableExists('rating_rebuild_jobs');
        $failedJobsQuery = $this->hasColumn('jobs', 'tenant_id')
            ? "SELECT COUNT(*) total FROM jobs WHERE tenant_id = ? AND failed_at IS NOT NULL"
            : "SELECT COUNT(*) total FROM jobs WHERE failed_at IS NOT NULL";
        $failedJobsParams = $this->hasColumn('jobs', 'tenant_id') ? [$tenantId] : [];
        $this->add($checks, 'failed_jobs', 'Queue job thất bại cần xử lý', 'critical', $this->count($failedJobsQuery, $failedJobsParams, 'jobs'));
        if ($hasRatingRebuildTable && $this->hasColumn('rating_rebuild_jobs', 'started_at')) {
            $this->add($checks, 'stale_rebuild_jobs', 'Rating rebuild job đang chạy quá lâu', 'critical', $this->count("SELECT COUNT(*) total FROM rating_rebuild_jobs WHERE tenant_id = ? AND status = 'running' AND (started_at IS NOT NULL AND started_at < (NOW() - INTERVAL 4 HOUR))", [$tenantId], 'rating_rebuild_jobs'));
        } elseif ($hasRatingRebuildTable && $this->hasColumn('rating_rebuild_jobs', 'updated_at')) {
            $this->add($checks, 'stale_rebuild_jobs', 'Rating rebuild job đang chạy quá lâu', 'warning', $this->count("SELECT COUNT(*) total FROM rating_rebuild_jobs WHERE tenant_id = ? AND status = 'running' AND updated_at < (NOW() - INTERVAL 4 HOUR)", [$tenantId], 'rating_rebuild_jobs'));
        } else {
            $this->add($checks, 'stale_rebuild_jobs', 'Rating rebuild job đang chạy quá lâu', 'warning', 0);
        }
        if ($hasRatingRebuildTable && $this->hasColumn('rating_rebuild_jobs', 'status')) {
            $this->add($checks, 'failed_rebuild_jobs', 'Rating rebuild job lỗi', 'warning', $this->count("SELECT COUNT(*) total FROM rating_rebuild_jobs WHERE tenant_id = ? AND status = 'failed'", [$tenantId], 'rating_rebuild_jobs'));
        } else {
            $this->add($checks, 'failed_rebuild_jobs', 'Rating rebuild job lỗi', 'warning', 0);
        }
        if ($this->tableHasColumns('tournament_matches', ['draw_version_id']) && $this->db->tableExists('tournament_draw_versions')) {
            $this->add($checks, 'draw_matches_orphan_versions', 'Match có draw_version_id nhưng không còn draw version', 'warning', $this->count("SELECT COUNT(*) total FROM tournament_matches tm LEFT JOIN tournament_draw_versions dv ON dv.id = tm.draw_version_id WHERE tm.tenant_id = ? AND tm.draw_version_id IS NOT NULL AND tm.draw_version_id > 0 AND dv.id IS NULL", [$tenantId], 'tournament_matches', 'tournament_draw_versions'));
            $this->add($checks, 'draw_versions_without_active_matches', 'Draw version đang active/draft nhưng không có trận', 'warning', $this->count("SELECT COUNT(*) total FROM tournament_draw_versions tdv WHERE tdv.tenant_id = ? AND tdv.status IN ('active', 'draft') AND NOT EXISTS (SELECT 1 FROM tournament_matches tm WHERE tm.draw_version_id = tdv.id)", [$tenantId], 'tournament_draw_versions', 'tournament_matches'));
            $this->add($checks, 'draw_category_multi_active_version', 'Mỗi hạng mục có >1 draw version đang active', 'warning', $this->count("SELECT COUNT(*) total FROM (SELECT tenant_id, tournament_id, category_id, COUNT(*) cnt FROM tournament_draw_versions WHERE tenant_id = ? AND status = 'active' GROUP BY tenant_id, tournament_id, category_id HAVING cnt > 1) multi", [$tenantId], 'tournament_draw_versions'));
        } else {
            $this->add($checks, 'draw_matches_orphan_versions', 'Match có draw_version_id nhưng không còn draw version', 'ok', 0);
            $this->add($checks, 'draw_versions_without_active_matches', 'Draw version đang active/draft nhưng không có trận', 'ok', 0);
            $this->add($checks, 'draw_category_multi_active_version', 'Mỗi hạng mục có >1 draw version đang active', 'ok', 0);
        }
        $this->add($checks, 'dispute_match_status_inconsistent', 'Trạng thái Match/Match_Result chưa khớp dispute', 'critical', $this->count("SELECT COUNT(*) total FROM match_disputes d JOIN matches m ON m.id = d.match_id LEFT JOIN match_results mr ON mr.match_id = m.id WHERE d.tenant_id = ? AND d.status IN ('open','reviewing') AND (m.status <> 'disputed' OR (mr.status <> 'disputed' OR mr.status IS NULL))", [$tenantId], 'match_disputes', 'matches', 'match_results'));
        $this->add($checks, 'dispute_closed_match_status_inconsistent', 'Dispute đã kết thúc nhưng match chưa đồng bộ về official', 'warning', $this->count("SELECT COUNT(*) total FROM match_disputes d JOIN matches m ON m.id = d.match_id LEFT JOIN match_results mr ON mr.match_id = m.id WHERE d.tenant_id = ? AND d.status IN ('upheld','rejected','resolved') AND (m.status <> 'official' OR (mr.status <> 'official' OR mr.status IS NULL))", [$tenantId], 'match_disputes', 'matches', 'match_results'));
        $this->add($checks, 'missing_policy_defaults', 'Tenant thiếu policy mặc định', 'critical', max(0, 5 - $this->count("SELECT COUNT(*) total FROM tenant_data_policies WHERE tenant_id = ? AND status = 'active'", [$tenantId], 'tenant_data_policies')));
        $issues = array_sum(array_map(static fn (array $check): int => (int) $check['count'], $checks));
        return ['tenant_id' => $tenantId, 'generated_at' => date('Y-m-d H:i:s'), 'total_issues' => $issues, 'checks' => $checks];
    }

    private function add(array &$checks, string $code, string $label, string $severity, int $count): void
    {
        $checks[] = ['code' => $code, 'label' => $label, 'severity' => $severity, 'count' => max(0, $count), 'status' => $count > 0 ? 'attention' : 'ok'];
    }

    private function count(string $sql, array $params, ?string ...$tables): int
    {
        foreach ($tables as $table) if ($table && ! $this->db->tableExists($table)) return 0;
        $row = $this->db->query($sql, $params)->getRow();
        return (int) ($row->total ?? 0);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->db->tableExists($table) && $this->db->fieldExists($column, $table);
    }

    private function migrationCheck(string $version, string $table, string $requiredColumn, string $schemaTable): int
    {
        if (! $this->db->tableExists('migrations')) {
            return 0;
        }

        $ran = (int) $this->db->table('migrations')->where('version', $version)->countAllResults();
        $schemaHasTable = $this->db->tableExists($schemaTable);
        $schemaHasColumn = $schemaHasTable && $this->db->fieldExists($requiredColumn, $schemaTable);

        if (! $ran && $schemaHasTable) {
            return 1;
        }
        if ($ran && ! $schemaHasTable) {
            return 1;
        }
        if ($ran && ! $schemaHasColumn) {
            return 1;
        }
        if (! $ran && ! $schemaHasTable) {
            return 0;
        }

        if ($table !== $schemaTable) {
            $expectedTable = $this->db->tableExists($table);
            if (! $expectedTable) {
                return 1;
            }
        }

        return 0;
    }

    private function tableHasColumns(string $table, array $columns): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }
        foreach ($columns as $column) {
            if (! $this->db->fieldExists($column, $table)) {
                return false;
            }
        }
        return true;
    }
}
