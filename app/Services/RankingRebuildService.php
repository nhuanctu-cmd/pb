<?php

namespace App\Services;

use Config\Database;

/** Replays official immutable results into the append-only ranking ledger. */
class RankingRebuildService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function run(array $filters = []): array
    {
        if (! $this->db->tableExists('ranking_point_ledgers') || ! $this->db->tableExists('matches')) {
            return ['success' => false, 'message' => 'Ranking foundation migration chưa được chạy.'];
        }
        $tenantId = isset($filters['tenant_id']) && (int) $filters['tenant_id'] > 0 ? (int) $filters['tenant_id'] : null;
        $authorityCode = (string) ($filters['authority'] ?? 'national-pickleball');
        $authority = $this->db->table('ranking_authorities')->where('code', $authorityCode)->where('status', 'active')->get()->getRow();
        $policy = $authority ? $this->db->table('ranking_policies')->where('authority_id', $authority->id)->where('status', 'active')->orderBy('id', 'DESC')->get()->getRow() : null;
        if (! $authority || ! $policy) return ['success' => false, 'message' => 'Ranking authority/policy không hoạt động.'];
        $rules = is_string($policy->rules ?? null) ? (json_decode($policy->rules, true) ?: []) : (array) ($policy->rules ?? []);
        $dryRun = ! empty($filters['dry_run']);

        $query = $this->db->table('matches')->where('status', 'official')->where('verification_status', 'official')->orderBy('completed_at', 'ASC')->orderBy('id', 'ASC');
        if ($tenantId !== null) $query->where('tenant_id', $tenantId);
        $matches = $query->get()->getResult();
        $processed = 0; $missing = 0; $drift = []; $created = 0;
        foreach ($matches as $match) {
            $result = $this->db->table('match_results')->where('match_id', $match->id)->where('status', 'official')->get()->getRow();
            if (! $result) continue;
            $participants = $this->db->table('match_participants')->where('match_id', $match->id)->orderBy('side')->orderBy('sort_order')->get()->getResult();
            if (count($participants) < 2) continue;
            $processed++;
            foreach ($participants as $participant) {
                $isDraw = empty($result->winner_side);
                $isWinner = ! $isDraw && (int) $participant->side === (int) $result->winner_side;
                $points = $isDraw ? (float) ($rules['draw'] ?? 5) : ($isWinner ? (float) ($rules['win'] ?? 10) : (float) ($rules['loss'] ?? 1));
                $key = sprintf('ranking:%s:%s:%s', $policy->id, $match->id, $participant->player_id);
                $existing = $this->db->table('ranking_point_ledgers')->where('idempotency_key', $key)->get()->getRow();
                if ($existing) {
                    if (abs((float) $existing->points - $points) > 0.0001) $drift[] = ['match_id' => (int) $match->id, 'player_id' => (int) $participant->player_id, 'expected' => $points, 'actual' => (float) $existing->points];
                    continue;
                }
                $missing++;
                if ($dryRun) continue;
                $inserted = $this->db->table('ranking_point_ledgers')->insert([
                    'authority_id' => $authority->id, 'policy_id' => $policy->id,
                    'tenant_id' => $match->tenant_id ?: $tenantId, 'player_id' => $participant->player_id,
                    'match_id' => $match->id, 'points' => $points,
                    'reason' => $isDraw ? 'ranking_rebuild_draw' : ($isWinner ? 'ranking_rebuild_win' : 'ranking_rebuild_loss'),
                    'idempotency_key' => $key, 'metadata' => json_encode(['rebuild' => true, 'result_version' => $result->version_no], JSON_UNESCAPED_UNICODE),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($inserted) $created++;
            }
        }
        $snapshot = null;
        if (! $dryRun && $tenantId !== null && $this->db->tableExists('ranking_snapshots')) {
            $snapshot = service('rankingNetworkService')->createSnapshot((string) ($filters['snapshot_date'] ?? date('Y-m-d')), $authorityCode, $tenantId);
        }
        return ['success' => true, 'dry_run' => $dryRun, 'processed_matches' => $processed, 'missing_entries' => $missing, 'created_entries' => $created, 'drift' => $drift, 'snapshot' => $snapshot];
    }
}
