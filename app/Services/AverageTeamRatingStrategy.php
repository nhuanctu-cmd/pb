<?php

namespace App\Services;

class AverageTeamRatingStrategy implements TeamRatingStrategyInterface
{
    public function calculate(array $ratings): float
    {
        $ratings = array_values(array_filter(array_map('floatval', $ratings), static fn (float $value): bool => $value > 0));
        return $ratings ? array_sum($ratings) / count($ratings) : 3.000;
    }
}
