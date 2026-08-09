<?php

namespace Tests\Unit;

use App\Services\CommunityService;
use CodeIgniter\Test\CIUnitTestCase;

class CommunityServiceTest extends CIUnitTestCase
{
    public function testNormalizeBodyCollapsesWhitespaceWithoutChangingContent(): void
    {
        $this->assertSame('Mẹo đánh bóng tốt', CommunityService::normalizeBody("  Mẹo   đánh\n bóng tốt  "));
    }
}
