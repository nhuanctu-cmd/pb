<?php

namespace Tests\Unit;

use App\Services\AverageTeamRatingStrategy;
use App\Services\RatingEligibilityService;
use App\Services\RatingIntegrityService;
use App\Services\RatingReliabilityEngine;
use CodeIgniter\Test\CIUnitTestCase;

class RatingEngineExtendedTest extends CIUnitTestCase
{
    public function testDoublesTeamRatingUsesAverage(): void
    {
        $strategy = new AverageTeamRatingStrategy();
        $this->assertSame(3.5, $strategy->calculate([3.0, 4.0]));
        $this->assertSame(3.0, $strategy->calculate([2.5, 3.5]));
    }

    public function testDisputedMatchCannotBeRated(): void
    {
        $result = (new RatingEligibilityService())->validate([
            'match' => (object) ['status' => 'disputed', 'verification_status' => 'pending'],
            'result' => (object) ['status' => 'disputed'],
            'participants' => [(object) ['side' => 1], (object) ['side' => 2]],
            'discipline' => 'singles',
            'games' => [['side_a_score' => 11, 'side_b_score' => 8]],
        ]);
        $this->assertFalse($result['eligible']);
        $this->assertContains('MATCH_NOT_OFFICIAL', $result['reasons']);
        $this->assertContains('RESULT_DISPUTED_OR_VOID', $result['reasons']);
    }

    public function testReliabilityIsBoundedAndDecaysWithRecency(): void
    {
        $engine = new RatingReliabilityEngine();
        $fresh = $engine->calculate(['rated_match_count' => 20, 'verified_match_count' => 20, 'opponent_count' => 8, 'competition_type_count' => 4, 'last_rated_match_at' => '2026-08-09 12:00:00'], [], '2026-08-09 12:00:00');
        $old = $engine->calculate(['rated_match_count' => 20, 'verified_match_count' => 20, 'opponent_count' => 8, 'competition_type_count' => 4, 'last_rated_match_at' => '2024-08-09 12:00:00'], [], '2026-08-09 12:00:00');
        $this->assertLessThanOrEqual(100, $fresh['score']);
        $this->assertGreaterThan($old['score'], $fresh['score']);
    }

    public function testIntegrityFlagsInvalidScoreAsHighRisk(): void
    {
        $result = (new RatingIntegrityService())->evaluate(
            (object) ['completed_at' => '2026-08-09 12:00:00'],
            [(object) ['player_id' => 1], (object) ['player_id' => 2]],
            ['games' => [['side_a_score' => 0, 'side_b_score' => 0]]]
        );

        $this->assertTrue($result['review_required']);
        $this->assertSame('INVALID_GAME_SCORE', $result['flags'][0]['code']);
        $this->assertGreaterThanOrEqual(80, $result['flags'][0]['risk_score']);
    }
}
