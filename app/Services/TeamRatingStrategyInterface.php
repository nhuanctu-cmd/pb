<?php

namespace App\Services;

interface TeamRatingStrategyInterface
{
    public function calculate(array $ratings): float;
}
