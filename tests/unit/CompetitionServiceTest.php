<?php

namespace Tests\Unit;

use App\Services\CompetitionService;
use CodeIgniter\Test\CIUnitTestCase;

class CompetitionServiceTest extends CIUnitTestCase
{
    public function testRoundRobinCoversEveryPairForEvenParticipantCount(): void
    {
        $rounds = CompetitionService::buildRoundRobinPairs([1, 2, 3, 4]);
        $pairs = [];
        foreach ($rounds as $round) foreach ($round as [$a, $b]) $pairs[] = min($a, $b) . ':' . max($a, $b);
        sort($pairs);
        $this->assertCount(3, $rounds);
        $this->assertCount(6, array_unique($pairs));
        $this->assertSame(['1:2', '1:3', '1:4', '2:3', '2:4', '3:4'], array_values(array_unique($pairs)));
    }

    public function testStandingSortUsesPointsThenPointDifferenceThenWins(): void
    {
        $rows = [
            (object) ['points' => 3, 'points_for' => 11, 'points_against' => 5, 'wins' => 1],
            (object) ['points' => 6, 'points_for' => 8, 'points_against' => 7, 'wins' => 2],
            (object) ['points' => 3, 'points_for' => 9, 'points_against' => 2, 'wins' => 1],
        ];
        $sorted = CompetitionService::sortStandingRows($rows);
        $this->assertSame(6, $sorted[0]->points);
        $this->assertSame(3, $sorted[1]->points);
        $this->assertSame(7, $sorted[1]->points_for - $sorted[1]->points_against);
    }

    public function testCompetitionDateRangeRejectsMalformedAndReversedDates(): void
    {
        $this->assertTrue(CompetitionService::isValidDateRange('2026-08-10', '2026-08-10'));
        $this->assertFalse(CompetitionService::isValidDateRange('2026-08-11', '2026-08-10'));
        $this->assertFalse(CompetitionService::isValidDateRange('10-08-2026', '2026-08-10'));
    }

    public function testLadderChallengeOnlyAllowsHigherRankWithinConfiguredGap(): void
    {
        $this->assertTrue(CompetitionService::canChallengeRank(4, 2));
        $this->assertFalse(CompetitionService::canChallengeRank(4, 1));
        $this->assertFalse(CompetitionService::canChallengeRank(1, 2));
    }

    public function testEntryFeeIsBoundedAndNonNegative(): void
    {
        $this->assertTrue(CompetitionService::isValidEntryFee(0));
        $this->assertTrue(CompetitionService::isValidEntryFee(250000));
        $this->assertFalse(CompetitionService::isValidEntryFee(-1));
        $this->assertFalse(CompetitionService::isValidEntryFee(1000000001));
    }
}
