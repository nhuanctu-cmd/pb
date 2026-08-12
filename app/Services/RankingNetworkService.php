<?php

namespace App\Services;

use App\Models\MatchParticipantModel;
use App\Models\MatchResultModel;
use App\Models\RankingAuthorityModel;
use App\Models\RankingPointLedgerModel;
use App\Models\RankingPolicyModel;
use App\Models\RankingSnapshotModel;
use App\Models\UnifiedMatchModel;

class RankingNetworkService
{
    private RankingAuthorityModel $authorityModel;
    private RankingPolicyModel $policyModel;
    private RankingPointLedgerModel $ledgerModel;
    private RankingSnapshotModel $snapshotModel;
    private UnifiedMatchModel $matchModel;
    private MatchParticipantModel $participantModel;
    private MatchResultModel $resultModel;

    public function __construct()
    {
        $this->authorityModel = model(RankingAuthorityModel::class);
        $this->policyModel = model(RankingPolicyModel::class);
        $this->ledgerModel = model(RankingPointLedgerModel::class);
        $this->snapshotModel = model(RankingSnapshotModel::class);
        $this->matchModel = model(UnifiedMatchModel::class);
        $this->participantModel = model(MatchParticipantModel::class);
        $this->resultModel = model(MatchResultModel::class);
    }

    public function applyOfficialMatch(int $matchId, ?int $tenantId = null): array
    {
        $matchQuery = $this->matchModel->where('id', $matchId)->where('status', 'official');
        if ($tenantId !== null && $tenantId > 0) $matchQuery->where('tenant_id', $tenantId);
        $match = $matchQuery->first();
        $result = $this->resultModel->where('match_id', $matchId)->where('status', 'official')->first();
        if (! $match || ! $result) return ['success' => false, 'message' => 'Chỉ match official mới được tính ranking.'];

        $authority = $this->authorityModel->where('code', 'national-pickleball')->where('status', 'active')->first();
        $policy = $authority ? $this->policyModel->where('authority_id', $authority->id)->where('status', 'active')->orderBy('id', 'DESC')->first() : null;
        if (! $authority || ! $policy) return ['success' => false, 'message' => 'Chưa có ranking authority/policy active.'];

        $rules = is_string($policy->rules) ? (json_decode($policy->rules, true) ?: []) : (array) $policy->rules;
        $participants = $this->participantModel->where('match_id', $matchId)->findAll();
        $created = 0;
        foreach ($participants as $participant) {
            $key = sprintf('ranking:%s:%s:%s', $policy->id, $matchId, $participant->player_id);
            if ($this->ledgerModel->where('idempotency_key', $key)->first()) continue;
            $isWinner = (int) $participant->side === (int) $result->winner_side;
            $isDraw = empty($result->winner_side);
            $points = $isDraw ? (float) ($rules['draw'] ?? 5) : ($isWinner ? (float) ($rules['win'] ?? 10) : (float) ($rules['loss'] ?? 1));
            $reason = $isDraw ? 'official_match_draw' : ($isWinner ? 'official_match_win' : 'official_match_loss');
            $ok = $this->ledgerModel->insert([
                'authority_id' => $authority->id,
                'policy_id' => $policy->id,
                'tenant_id' => $match->tenant_id ?: $tenantId,
                'player_id' => $participant->player_id,
                'match_id' => $matchId,
                'points' => $points,
                'reason' => $reason,
                'idempotency_key' => $key,
                'metadata' => json_encode(['source_type' => $match->source_type, 'result_version' => $result->version_no]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            if ($ok) $created++;
        }

        return ['success' => true, 'created' => $created, 'authority_id' => (int) $authority->id, 'policy_id' => (int) $policy->id];
    }

    public function leaderboard(string $authorityCode = 'national-pickleball', ?int $tenantId = null, int $limit = 100): array
    {
        $authority = $this->authorityModel->where('code', $authorityCode)->first();
        if (! $authority) return [];
        $policy = $this->policyModel->where('authority_id', $authority->id)->where('status', 'active')->orderBy('id', 'DESC')->first();
        if (! $policy) return [];
        $builder = $this->ledgerModel
            ->select('ranking_point_ledgers.player_id, SUM(ranking_point_ledgers.points) AS points, COUNT(DISTINCT ranking_point_ledgers.match_id) AS match_count, players.full_name, players.player_code')
            ->join('players', 'players.id = ranking_point_ledgers.player_id', 'left')
            ->where('ranking_point_ledgers.authority_id', $authority->id)
            ->where('ranking_point_ledgers.policy_id', $policy->id)
            ->where('players.deleted_at', null)
            ->groupBy('ranking_point_ledgers.player_id')
            ->orderBy('points', 'DESC')
            ->orderBy('match_count', 'DESC')
            ->limit(max(1, min(500, $limit)));
        if ($tenantId !== null) $builder->where('ranking_point_ledgers.tenant_id', $tenantId);
        return $builder->get()->getResult();
    }

    public function createSnapshot(string $date, string $authorityCode = 'national-pickleball', ?int $tenantId = null): array
    {
        $authority = $this->authorityModel->where('code', $authorityCode)->first();
        if (! $authority) return ['success' => false, 'message' => 'Không tìm thấy ranking authority.'];
        $policy = $this->policyModel->where('authority_id', $authority->id)->where('status', 'active')->orderBy('id', 'DESC')->first();
        if (! $policy) return ['success' => false, 'message' => 'Không tìm thấy ranking policy.'];
        $rows = $this->leaderboard($authorityCode, $tenantId, 500);
        foreach ($rows as $rank => $row) {
            $existing = $this->snapshotModel->where('authority_id', $authority->id)->where('policy_id', $policy->id)
                ->where('tenant_id', $tenantId)->where('player_id', $row->player_id)->where('snapshot_date', $date)->first();
            $payload = ['rank_position' => $rank + 1, 'points' => $row->points, 'match_count' => $row->match_count, 'created_at' => date('Y-m-d H:i:s')];
            if ($existing) $this->snapshotModel->update($existing->id, $payload);
            else $this->snapshotModel->insert(array_merge(['authority_id' => $authority->id, 'policy_id' => $policy->id, 'tenant_id' => $tenantId, 'player_id' => $row->player_id, 'snapshot_date' => $date], $payload));
        }
        return ['success' => true, 'snapshot_date' => $date, 'count' => count($rows)];
    }
}
