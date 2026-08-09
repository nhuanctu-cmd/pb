<?php

namespace Tests\Unit;

use App\Services\OpenPlayRotationService;
use CodeIgniter\Test\CIUnitTestCase;

class OpenPlayRotationServiceTest extends CIUnitTestCase
{
    public function testFourPlayersProduceThreeDeterministicRounds(): void
    {
        $rounds = OpenPlayRotationService::buildPairings([4, 1, 3, 2]);
        $this->assertCount(3, $rounds);
        $this->assertSame([[1, 4], [2, 3]], $rounds[0]);
    }

    public function testLessThanFourPlayersHasNoRotation(): void
    {
        $this->assertSame([], OpenPlayRotationService::buildPairings([1, 2, 3]));
    }
}
