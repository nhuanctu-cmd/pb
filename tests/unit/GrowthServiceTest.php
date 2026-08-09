<?php

namespace Tests\Unit;

use App\Services\GrowthService;
use CodeIgniter\Test\CIUnitTestCase;

class GrowthServiceTest extends CIUnitTestCase
{
    public function testPercentDiscountHonoursCapAndOrderAmount(): void
    {
        $this->assertSame(100.0, GrowthService::calculateDiscount(1000, 'percent', 20, 100));
        $this->assertSame(200.0, GrowthService::calculateDiscount(1000, 'percent', 20, null));
        $this->assertSame(0.0, GrowthService::calculateDiscount(0, 'fixed', 500, null));
    }

    public function testFixedDiscountNeverExceedsOrderAmount(): void
    {
        $this->assertSame(500.0, GrowthService::calculateDiscount(1000, 'fixed', 500, null));
        $this->assertSame(100.0, GrowthService::calculateDiscount(100, 'fixed', 500, null));
    }
}
