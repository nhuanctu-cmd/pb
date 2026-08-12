<?php

namespace App\Services;

use Config\Database;

class RatingReliabilityEngine
{
    protected $db;

    public function __construct() { $this->db = Database::connect(); }

    public function calculate(array $facts, array $configuration = [], ?string $asOf = null): array
    {
        $count = max(0, (int) ($facts['rated_match_count'] ?? 0));
        $verified = max(0, (int) ($facts['verified_match_count'] ?? 0));
        $opponents = max(0, (int) ($facts['opponent_count'] ?? 0));
        $competitions = max(0, (int) ($facts['competition_type_count'] ?? 0));
        $lastMatch = $facts['last_rated_match_at'] ?? $asOf;
        $ageDays = max(0, (int) floor((strtotime($asOf ?: date('Y-m-d H:i:s')) - strtotime($lastMatch ?: date('Y-m-d H:i:s'))) / 86400));
        $halfLife = max(1, (int) ($configuration['recency_half_life_days'] ?? 365));
        $components = [
            'volume' => min(100, ($count / max(1, (int) ($configuration['volume_target_matches'] ?? 20))) * 100),
            'verification' => $count > 0 ? min(100, ($verified / $count) * 100) : 0,
            'recency' => round(100 * exp(-log(2) * $ageDays / $halfLife), 2),
            'opponent_diversity' => min(100, ($opponents / max(1, (int) ($configuration['opponent_target'] ?? 8))) * 100),
            'competition_diversity' => min(100, ($competitions / max(1, (int) ($configuration['competition_target'] ?? 4))) * 100),
        ];
        $weights = $configuration['reliability_weights'] ?? ['volume' => .30, 'verification' => .25, 'recency' => .20, 'opponent_diversity' => .15, 'competition_diversity' => .10];
        $score = 0.0;
        foreach ($components as $key => $value) $score += $value * (float) ($weights[$key] ?? 0);
        return ['score' => round(max(0, min(100, $score)), 2), 'components' => array_map(static fn ($value): float => round((float) $value, 2), $components), 'age_days' => $ageDays];
    }

    public function calculateForProfile(int $tenantId, int $playerId, int $disciplineId, array $configuration = [], ?array $extra = null): array
    {
        $facts = ['rated_match_count' => 0, 'verified_match_count' => 0, 'opponent_count' => 0, 'competition_type_count' => 0, 'last_rated_match_at' => null];
        if ($this->db->tableExists('rating_transactions')) {
            $rows = $this->db->table('rating_transactions')->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('discipline_id', $disciplineId)->where('status', 'applied')->whereIn('transaction_type', ['impact', 'replacement'])->orderBy('created_at', 'ASC')->get()->getResult();
            $facts['rated_match_count'] = count($rows);
            $facts['verified_match_count'] = count($rows);
            $opponents = [];
            $types = [];
            foreach ($rows as $row) {
                $metadata = is_string($row->metadata ?? null) ? json_decode($row->metadata, true) : (array) ($row->metadata ?? []);
                foreach (($metadata['opponent_ids'] ?? []) as $id) $opponents[(int) $id] = true;
                if (! empty($metadata['match_type'])) $types[$metadata['match_type']] = true;
                $facts['last_rated_match_at'] = $row->created_at;
            }
            $facts['opponent_count'] = count($opponents);
            $facts['competition_type_count'] = count($types);
        }
        if ($extra) $facts = array_merge($facts, $extra);
        return $this->calculate($facts, $configuration);
    }

    /** Builds diversity facts from immutable transaction metadata before applying the next match. */
    public function factsForNextMatch(int $tenantId, int $playerId, int $disciplineId, array $opponentIds = [], array $partnerIds = [], ?string $matchType = null, ?string $lastMatchAt = null): array
    {
        $facts = ['rated_match_count' => 0, 'verified_match_count' => 0, 'opponent_count' => 0, 'competition_type_count' => 0, 'last_rated_match_at' => $lastMatchAt];
        $opponents = []; $types = [];
        if ($this->db->tableExists('rating_transactions')) foreach ($this->db->table('rating_transactions')->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('discipline_id', $disciplineId)->where('status', 'applied')->whereIn('transaction_type', ['impact', 'replacement'])->get()->getResult() as $row) {
            $facts['rated_match_count']++; $facts['verified_match_count']++;
            $metadata = is_string($row->metadata ?? null) ? (json_decode($row->metadata, true) ?: []) : (array) ($row->metadata ?? []);
            foreach (array_merge((array) ($metadata['opponent_ids'] ?? []), (array) ($metadata['partner_ids'] ?? [])) as $id) $opponents[(int) $id] = true;
            if (! empty($metadata['match_type'])) $types[$metadata['match_type']] = true;
            $facts['last_rated_match_at'] = $row->created_at;
        }
        foreach (array_merge($opponentIds, $partnerIds) as $id) if ((int) $id > 0 && (int) $id !== $playerId) $opponents[(int) $id] = true;
        if ($matchType) $types[$matchType] = true;
        $facts['opponent_count'] = count($opponents); $facts['competition_type_count'] = count($types); $facts['last_rated_match_at'] = $lastMatchAt ?: $facts['last_rated_match_at'];
        return $facts;
    }
}
