<?php

namespace Tests\Unit;

use App\Services\InvoiceService;
use App\Services\PaymentService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class PaymentServiceTest extends CIUnitTestCase
{
    private InvoiceService $invoiceService;
    private PaymentService $paymentService;
    private array $invoiceIds = [];
    private string $invoiceCodePrefix = 'UT-PAY-';
    private float $initialWalletBalance = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = new InvoiceService();
        $this->paymentService = new PaymentService();
        $this->initialWalletBalance = (float) (\Config\Database::connect()->table('player_wallets')
            ->where('tenant_id', 1)->where('player_id', 1)->get()->getRow('balance') ?? 0);
    }

    protected function tearDown(): void
    {
        $db = \Config\Database::connect();
        if ($this->invoiceIds !== []) {
            $db->table('refunds')->whereIn('invoice_id', $this->invoiceIds)->delete();
            $db->table('payments')->whereIn('invoice_id', $this->invoiceIds)->delete();
            $db->table('invoices')->whereIn('id', $this->invoiceIds)->delete();
        }
        // Also clean rows created before an exception occurred between the
        // insert and the test fixture receiving the generated ID.
        $orphanIds = $db->table('invoices')->select('id')
            ->like('invoice_code', $this->invoiceCodePrefix, 'after')
            ->get()->getResultArray();
        $orphanIds = array_column($orphanIds, 'id');
        if ($orphanIds !== []) {
            $db->table('refunds')->whereIn('invoice_id', $orphanIds)->delete();
            $db->table('payments')->whereIn('invoice_id', $orphanIds)->delete();
            $db->table('invoices')->whereIn('id', $orphanIds)->delete();
        }
        $db->table('player_wallets')->where('tenant_id', 1)->where('player_id', 1)
            ->update(['balance' => $this->initialWalletBalance]);
        parent::tearDown();
    }

    public function testRetryWithSameIdempotencyKeyDoesNotDoubleCharge(): void
    {
        $invoice = $this->createInvoice(100);

        $first = $this->paymentService->payCash($invoice->id, 40, [
            'idempotency_key' => 'payment-test-' . $invoice->id,
        ], 1);
        $retry = $this->paymentService->payCash($invoice->id, 40, [
            'idempotency_key' => 'payment-test-' . $invoice->id,
        ], 1);

        $this->assertTrue($first['success']);
        $this->assertTrue($retry['duplicate']);
        $saved = model(\App\Models\InvoiceModel::class)->find($invoice->id);
        $this->assertSame(40.0, (float) $saved->paid_amount);
    }

    public function testOverpaymentIsRejected(): void
    {
        $invoice = $this->createInvoice(50);

        $this->expectException(InvalidArgumentException::class);
        $this->paymentService->payCash($invoice->id, 50.01, [
            'idempotency_key' => 'payment-over-' . $invoice->id,
        ], 1);
    }

    public function testRefundCannotExceedNetPaidBalance(): void
    {
        $invoice = $this->createInvoice(100);
        $this->paymentService->payCash($invoice->id, 100, [
            'idempotency_key' => 'payment-refund-' . $invoice->id,
        ], 1);

        $partial = $this->paymentService->refund($invoice->id, 40, 'test', 2, 1);
        $this->assertSame(60.0, (float) $partial['new_paid_amount']);

        $final = $this->paymentService->refund($invoice->id, 60, 'test', 2, 1);
        $this->assertSame(0.0, (float) $final['new_paid_amount']);
        $this->assertSame('refunded', $final['new_status']);
    }

    public function testWalletPaymentDebitsWalletAndInvoiceTogether(): void
    {
        $invoice = $this->createInvoice(1000, 1);
        $result = $this->paymentService->payByWallet($invoice->id, 1000, 1, [
            'idempotency_key' => 'payment-wallet-' . $invoice->id,
        ], 1);

        $this->assertTrue($result['success']);
        $wallet = \Config\Database::connect()->table('player_wallets')
            ->where('tenant_id', 1)->where('player_id', 1)->get()->getRow();
        $this->assertSame(round($this->initialWalletBalance - 1000, 2), round((float) $wallet->balance, 2));
    }

    private function createInvoice(float $amount, ?int $playerId = null)
    {
        $invoice = $this->invoiceService->createInvoice(
            1,
            2,
            $this->invoiceCodePrefix . bin2hex(random_bytes(5)),
            $amount,
            ['created_by' => 2, 'player_id' => $playerId, 'customer_type' => $playerId ? 'player' : 'guest']
        );
        $this->invoiceIds[] = (int) $invoice->id;

        return $invoice;
    }
}
