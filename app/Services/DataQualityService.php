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
        $this->add($checks, 'orphan_bookings', 'Booking thiếu branch cùng tenant', 'critical', $this->count("SELECT COUNT(*) total FROM bookings b LEFT JOIN branches br ON br.id = b.branch_id WHERE b.tenant_id = ? AND (br.id IS NULL OR br.tenant_id <> b.tenant_id)", [$tenantId]));
        $this->add($checks, 'cross_tenant_booking_items', 'Booking item lệch tenant với booking/court', 'critical', $this->count("SELECT COUNT(*) total FROM booking_items bi JOIN bookings b ON b.id = bi.booking_id LEFT JOIN courts c ON c.id = bi.court_id WHERE b.tenant_id = ? AND (bi.tenant_id <> b.tenant_id OR c.id IS NULL OR c.tenant_id <> b.tenant_id)", [$tenantId]));
        $this->add($checks, 'overlapping_bookings', 'Booking active bị chồng giờ trên cùng sân', 'warning', $this->count("SELECT COUNT(*) total FROM booking_items a JOIN booking_items b ON b.court_id = a.court_id AND b.id > a.id AND b.start_time < a.end_time AND b.end_time > a.start_time JOIN bookings ba ON ba.id = a.booking_id JOIN bookings bb ON bb.id = b.booking_id AND bb.booking_date = ba.booking_date WHERE ba.tenant_id = ? AND ba.booking_date >= CURDATE() AND ba.deleted_at IS NULL AND bb.deleted_at IS NULL AND a.status = 'active' AND b.status = 'active' AND ba.status NOT IN ('cancelled','refunded','expired') AND bb.status NOT IN ('cancelled','refunded','expired')", [$tenantId]));
        // player_competitive_profiles is keyed by player_id and intentionally has no tenant_id.
        // Tenant isolation comes from the scoped players row; ignore soft-deleted profiles.
        $this->add($checks, 'players_without_profile', 'Player chưa có competitive profile', 'info', $this->count("SELECT COUNT(*) total FROM players p LEFT JOIN player_competitive_profiles cp ON cp.player_id = p.id AND cp.deleted_at IS NULL WHERE p.tenant_id = ? AND p.deleted_at IS NULL AND cp.id IS NULL", [$tenantId], 'players', 'player_competitive_profiles'));
        $this->add($checks, 'open_integrity_flags', 'Rating integrity flag chưa xử lý', 'warning', $this->count("SELECT COUNT(*) total FROM rating_integrity_flags WHERE tenant_id = ? AND status = 'open'", [$tenantId], 'rating_integrity_flags'));
        $this->add($checks, 'official_without_provenance', 'Match official thiếu provenance', 'warning', $this->count("SELECT COUNT(*) total FROM matches WHERE tenant_id = ? AND status = 'official' AND (provenance_id IS NULL OR provenance_id = 0)", [$tenantId], 'matches'));
        $this->add($checks, 'failed_jobs', 'Queue job thất bại cần xử lý', 'critical', $this->count("SELECT COUNT(*) total FROM jobs WHERE tenant_id = ? AND failed_at IS NOT NULL", [$tenantId], 'jobs'));
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
}
