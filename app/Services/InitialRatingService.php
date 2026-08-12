<?php

namespace App\Services;

use Config\Database;

class InitialRatingService
{
    protected $db;

    public function __construct() { $this->db = Database::connect(); }

    public function calculate(array $input = []): array
    {
        $configuration = $input['configuration'] ?? [];
        $sources = $input['sources'] ?? [];
        $priority = $configuration['source_priority'] ?? ['platform_established', 'external_verified', 'platform_provisional', 'coach_verified', 'club_verified', 'self_declared'];
        $selected = null;
        foreach ($priority as $type) {
            foreach ($sources as $source) {
                if (($source['type'] ?? null) === $type && isset($source['rating']) && (float) $source['rating'] > 0) {
                    $selected = $source;
                    break 2;
                }
            }
        }
        $seed = $selected ? (float) $selected['rating'] : (float) ($configuration['initial_rating'] ?? 3.000);
        $matchRating = isset($input['first_match_rating']) ? (float) $input['first_match_rating'] : null;
        if ($matchRating !== null && $matchRating > 0 && $selected) $seed = ($seed * .60) + ($matchRating * .40);
        elseif ($matchRating !== null && $matchRating > 0 && ! $selected) $seed = $matchRating;
        $reliability = $selected ? (float) ($selected['confidence'] ?? 20) : 0.0;
        return ['initial_rating' => round(max(2.000, min(5.999, $seed)), 3), 'initial_reliability' => round(max(0, min(69, $reliability)), 2), 'reason' => $selected ? 'INITIAL_SEED_' . strtoupper((string) $selected['type']) : 'INITIAL_SEED_NR', 'sources_used' => $selected ? [$selected] : []];
    }

    public function forPlayer(int $tenantId, int $playerId, int $disciplineId, array $configuration = []): array
    {
        $sources = [];
        if ($this->db->tableExists('player_skill_claims')) {
            $claims = $this->db->table('player_skill_claims')->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('discipline_id', $disciplineId)->where('verification_status', 'verified')->orderBy('claimed_at', 'DESC')->get()->getResult();
            foreach ($claims as $claim) {
                $type = match ($claim->source_type) { 'external_provider' => 'external_verified', 'coach' => 'coach_verified', 'club' => 'club_verified', 'tournament_organizer' => 'club_verified', default => 'self_declared' };
                if ($claim->claimed_rating !== null) $sources[] = ['type' => $type, 'rating' => (float) $claim->claimed_rating, 'confidence' => $this->claimConfidence($type)];
            }
        }
        if ($this->db->tableExists('player_external_ratings')) {
            $external = $this->db->table('player_external_ratings')->where('player_id', $playerId)->where('rating IS NOT NULL')->orderBy('reliability', 'DESC')->get()->getRow();
            if ($external) $sources[] = ['type' => 'external_verified', 'rating' => (float) $external->rating, 'confidence' => min(69, (float) $external->reliability)];
        }
        return $this->calculate(['sources' => $sources, 'configuration' => $configuration]);
    }

    private function claimConfidence(string $type): int
    {
        return match ($type) { 'external_verified' => 60, 'coach_verified' => 45, 'club_verified' => 30, default => 10 };
    }
}
