<?php

namespace App\Services;

class RatingCalculator
{
    public function __construct(
        protected ExpectedPerformanceCalculator $expected,
        protected MatchPerformanceService $performance,
        protected TeamRatingStrategyInterface $teamStrategy
    ) {}

    public function calculate(array $context): array
    {
        $policy = $context['configuration'] ?? [];
        $sideA = array_map(static fn ($row): float => (float) ($row['rating'] ?? $policy['initial_rating'] ?? 3.000), $context['side_a'] ?? []);
        $sideB = array_map(static fn ($row): float => (float) ($row['rating'] ?? $policy['initial_rating'] ?? 3.000), $context['side_b'] ?? []);
        $teamA = $this->teamStrategy->calculate($sideA);
        $teamB = $this->teamStrategy->calculate($sideB);
        $expectedA = $this->expected->calculate($teamA, $teamB, $policy);
        $actual = $this->performance->normalize($context['games'] ?? [], $context['winner_side'] ?? null);
        $matchWeight = max(0, min(1, (float) ($context['match_weight'] ?? 1.0)));
        $scoreFactor = 1 + (float) ($policy['score_margin_impact'] ?? .15) * abs($actual['side_a'] - .5) * 2;
        $baseDelta = (float) ($policy['base_delta'] ?? .160);
        $maxDelta = (float) ($policy['max_delta'] ?? .350);
        $averageReliability = array_merge($context['side_a'] ?? [], $context['side_b'] ?? []);
        $averageReliability = $averageReliability ? array_sum(array_map(static fn ($row): float => (float) ($row['reliability'] ?? 0), $averageReliability)) / count($averageReliability) : 0;
        $volatility = 1 + max(0, min(1, (100 - $averageReliability) / 100)) * ((float) ($policy['provisional_volatility'] ?? .35));
        $rawDeltaA = ($actual['side_a'] - $expectedA) * $baseDelta * $matchWeight * $scoreFactor * $volatility;
        $deltaA = max(-$maxDelta, min($maxDelta, $rawDeltaA));
        return [
            'team_a_rating' => round($teamA, 3), 'team_b_rating' => round($teamB, 3), 'expected_a' => round($expectedA, 6),
            'actual_a' => $actual['side_a'], 'actual_b' => $actual['side_b'], 'games_count' => $actual['games_count'],
            'delta_a' => round($deltaA, 3), 'delta_b' => round(-$deltaA, 3), 'score_margin' => $actual['score_margin'],
            'match_weight' => $matchWeight, 'volatility' => round($volatility, 4),
        ];
    }
}
