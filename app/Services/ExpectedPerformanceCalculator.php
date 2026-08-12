<?php

namespace App\Services;

class ExpectedPerformanceCalculator
{
    public function calculate(float $teamRating, float $opponentRating, array $configuration = []): float
    {
        $divisor = max(0.1, (float) ($configuration['expected_rating_divisor'] ?? 1.0));
        return round(1 / (1 + pow(10, ($opponentRating - $teamRating) / $divisor)), 6);
    }
}
