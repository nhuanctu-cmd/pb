<?php

namespace App\Services;

class MatchPerformanceService
{
    /** Returns normalized side A/B performance in [0,1]. */
    public function normalize(array $games, ?int $winnerSide = null): array
    {
        $performances = [];
        foreach ($games as $game) {
            $a = (int) ($game['side_a_score'] ?? $game['team_a_score'] ?? $game['a'] ?? 0);
            $b = (int) ($game['side_b_score'] ?? $game['team_b_score'] ?? $game['b'] ?? 0);
            if ($a < 0 || $b < 0 || ($a === 0 && $b === 0)) continue;
            $performances[] = 0.5 + (($a - $b) / max(1, $a + $b)) * 0.5;
        }
        if (! $performances) {
            $sideA = $winnerSide === 1 ? 1.0 : ($winnerSide === 2 ? 0.0 : 0.5);
            return ['side_a' => $sideA, 'side_b' => 1 - $sideA, 'games_count' => 0, 'score_margin' => 0.0];
        }
        $sideA = max(0.0, min(1.0, array_sum($performances) / count($performances)));
        return ['side_a' => round($sideA, 6), 'side_b' => round(1 - $sideA, 6), 'games_count' => count($performances), 'score_margin' => round(abs(($sideA - 0.5) * 2), 6)];
    }
}
