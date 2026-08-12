<?php

namespace Tests\Unit;

use App\Services\AverageTeamRatingStrategy;
use App\Services\ExpectedPerformanceCalculator;
use App\Services\MatchPerformanceService;
use App\Services\RatingCalculator;
use CodeIgniter\Test\CIUnitTestCase;

class RatingEngineV1Test extends CIUnitTestCase
{
    private function calculator(): RatingCalculator
    {
        return new RatingCalculator(new ExpectedPerformanceCalculator(), new MatchPerformanceService(), new AverageTeamRatingStrategy());
    }

    public function testExpectedPerformanceFavoursHigherRatedSide(): void
    {
        $expected = (new ExpectedPerformanceCalculator())->calculate(4.0, 3.0, ['expected_rating_divisor' => 2.0]);
        $this->assertGreaterThan(0.5, $expected);
        $this->assertLessThan(1.0, $expected);
    }

    public function testUpsetProducesPositiveDeltaForUnderdog(): void
    {
        $result = $this->calculator()->calculate([
            'side_a' => [['rating' => 3.0, 'reliability' => 0]],
            'side_b' => [['rating' => 4.0, 'reliability' => 80]],
            'winner_side' => 1,
            'games' => [['side_a_score' => 11, 'side_b_score' => 8]],
            'configuration' => ['expected_rating_divisor' => 2.0, 'base_delta' => .160, 'max_delta' => .350],
        ]);
        $this->assertGreaterThan(0, $result['delta_a']);
        $this->assertSame(-$result['delta_a'], $result['delta_b']);
    }

    public function testScoreNormalizationRewardsClearerWin(): void
    {
        $service = new MatchPerformanceService();
        $close = $service->normalize([['side_a_score' => 11, 'side_b_score' => 10]], 1);
        $clear = $service->normalize([['side_a_score' => 11, 'side_b_score' => 2]], 1);
        $this->assertGreaterThan($close['side_a'], $clear['side_a']);
        $this->assertSame(1, $clear['games_count']);
    }
}
