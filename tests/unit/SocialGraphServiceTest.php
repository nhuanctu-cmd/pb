<?php

namespace Tests\Unit;

use App\Services\SocialGraphService;
use CodeIgniter\Test\CIUnitTestCase;

class SocialGraphServiceTest extends CIUnitTestCase
{
    public function testFavoriteKeyIsStableAndNormalized(): void
    {
        $this->assertSame('club:12', SocialGraphService::favoriteKey(' Club ', 12));
        $this->assertSame('court:0', SocialGraphService::favoriteKey('COURT', -3));
    }
}
