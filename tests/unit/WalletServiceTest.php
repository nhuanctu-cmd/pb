<?php

namespace Tests\Unit;

use App\Services\WalletService;
use CodeIgniter\Test\CIUnitTestCase;

class WalletServiceTest extends CIUnitTestCase
{
    private WalletService $service;
    private int $playerId = 1;
    private int $tenantId = 1;
    private string $marker;
    private float $initialBalance = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WalletService();
        $this->marker = 'wallet-test-' . bin2hex(random_bytes(5));
        $wallet = $this->service->getWallet($this->playerId, $this->tenantId);
        $this->initialBalance = (float) $wallet->balance;
    }

    protected function tearDown(): void
    {
        $db = \Config\Database::connect();
        $db->table('wallet_transactions')->like('note', 'wallet-test-', 'after')->delete();
        $wallet = $db->table('player_wallets')
            ->where('player_id', $this->playerId)->where('tenant_id', $this->tenantId)->get()->getRow();
        if ($wallet) {
            $db->table('player_wallets')->where('id', $wallet->id)->update(['balance' => $this->initialBalance]);
        }
        parent::tearDown();
    }

    public function testWalletMutationsUseOneConsistentLedger(): void
    {
        $this->assertTrue($this->service->topup($this->playerId, $this->tenantId, 10, $this->marker));
        $this->assertTrue($this->service->pay($this->playerId, $this->tenantId, 4, $this->marker));

        $wallet = $this->service->getWallet($this->playerId, $this->tenantId);
        $this->assertSame(round($this->initialBalance + 6, 2), round((float) $wallet->balance, 2));
        $rows = \Config\Database::connect()->table('wallet_transactions')
            ->where('wallet_id', $wallet->id)->like('note', $this->marker)->countAllResults();
        $this->assertSame(2, $rows);
    }

    public function testWalletCannotBecomeNegative(): void
    {
        $this->assertFalse($this->service->pay(
            $this->playerId, $this->tenantId, $this->initialBalance + 1, $this->marker
        ));
        $wallet = $this->service->getWallet($this->playerId, $this->tenantId);
        $this->assertSame(round($this->initialBalance, 2), round((float) $wallet->balance, 2));
    }
}
