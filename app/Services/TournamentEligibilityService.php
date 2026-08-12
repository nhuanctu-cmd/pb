<?php

namespace App\Services;

use Config\Database;

class TournamentEligibilityService
{
    protected $db;

    public function __construct() { $this->db = Database::connect(); }

    public function evaluate(int $tenantId, array $playerIds, string $discipline, array $rules = []): array
    {
        $rules = array_merge(['policy' => 'STRICT', 'min_rating' => null, 'max_rating' => null, 'team_average_max' => null, 'individual_cap' => null, 'play_up_allowed' => false, 'play_down_allowed' => false, 'grace' => 0], $rules);
        $profiles = [];
        foreach ($playerIds as $playerId) {
            $rating = service('ratingEngine')->getPublicRating($tenantId, (int) $playerId, $discipline);
            $profiles[] = ['player_id' => (int) $playerId, 'rating' => $rating['rating'] ?? null, 'reliability' => $rating['reliability'] ?? 0, 'status' => $rating['status'] ?? 'nr', 'skill_band' => $rating['skill_band'] ?? 'NR'];
        }
        $reasons = [];
        foreach ($profiles as $profile) {
            if ($profile['rating'] === null) $reasons[] = ['code' => 'NR_NOT_RATED', 'player_id' => $profile['player_id']];
            if ($rules['max_rating'] !== null && $profile['rating'] !== null && (float) $profile['rating'] > (float) $rules['max_rating'] + (float) $rules['grace'] && ! $rules['play_up_allowed']) $reasons[] = ['code' => 'RATING_ABOVE_CATEGORY_MAX', 'player_id' => $profile['player_id']];
            if ($rules['min_rating'] !== null && $profile['rating'] !== null && (float) $profile['rating'] < (float) $rules['min_rating'] - (float) $rules['grace'] && ! $rules['play_down_allowed']) $reasons[] = ['code' => 'RATING_BELOW_CATEGORY_MIN', 'player_id' => $profile['player_id']];
        }
        $ratings = array_values(array_filter(array_map(static fn ($p) => $p['rating'], $profiles), static fn ($v) => $v !== null));
        if ($rules['policy'] === 'AVERAGE_WITH_CAP' && $ratings) {
            if ($rules['team_average_max'] !== null && array_sum($ratings) / count($ratings) > (float) $rules['team_average_max']) $reasons[] = ['code' => 'TEAM_AVERAGE_ABOVE_MAX'];
            if ($rules['individual_cap'] !== null && max($ratings) > (float) $rules['individual_cap']) $reasons[] = ['code' => 'INDIVIDUAL_CAP_EXCEEDED'];
        }
        if ($rules['policy'] === 'MANUAL_REVIEW') $reasons[] = ['code' => 'MANUAL_REVIEW_REQUIRED'];
        $conflicts = array_filter($profiles, static fn ($p) => $p['rating'] !== null && $p['status'] === 'established');
        if ($conflicts && isset($rules['declared_skill_rating'])) foreach ($conflicts as $profile) if ((float) $rules['declared_skill_rating'] < (float) $profile['rating'] - .25) $reasons[] = ['code' => 'POSSIBLE_UNDERRATED_PLAYER', 'player_id' => $profile['player_id']];
        $failed = array_filter($reasons, static fn ($reason) => ! in_array($reason['code'], ['MANUAL_REVIEW_REQUIRED', 'POSSIBLE_UNDERRATED_PLAYER'], true));
        return ['status' => $failed ? 'failed' : ($reasons ? 'flagged' : 'passed'), 'eligible' => ! $failed && $reasons === [], 'reasons' => array_values($reasons), 'players' => $profiles, 'policy' => $rules['policy']];
    }
}
