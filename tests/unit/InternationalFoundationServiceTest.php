<?php

namespace Tests\Unit;

use App\Services\InternationalFoundationService;
use CodeIgniter\Test\CIUnitTestCase;

class InternationalFoundationServiceTest extends CIUnitTestCase
{
    public function testMoneyFormattingUsesCurrencyAndLocale(): void
    {
        $service = new InternationalFoundationService();
        $this->assertSame('1.500.000 VND', $service->formatMoney(1500000, 'VND', 'vi-VN'));
        $this->assertSame('1,250.50 USD', $service->formatMoney(1250.5, 'USD', 'en-US'));
    }

    public function testInvalidMembershipInputFailsBeforeDatabaseAccess(): void
    {
        $result = (new InternationalFoundationService())->upsertMembership(0, 0, '');
        $this->assertFalse($result['success']);
    }
}
