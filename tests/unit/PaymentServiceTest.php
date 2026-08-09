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

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = new InvoiceService();
        $this->paymentService = new PaymentService();
    }

    protected function tearDown(): void
    {
        $db = \Config\Database::connect();
        if ($this->invoiceIds !== []) {
            $db->table('refunds')->whereIn('invoice_id', $this->invoiceIds)->delete();
            $db->table('payments')->whereIn('invoice_id', $this->invoiceIds)->delete();
            $db->table('invoices')->whereIn('id', $this->invoiceIds)->delete();
        }
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

    private function createInvoice(float $amount)
    {
        $invoice = $this->invoiceService->createInvoice(
            1,
            2,
            'UT-PAY-' . bin2hex(random_bytes(5)),
            $amount,
            ['created_by' => 2]
        );
        $this->invoiceIds[] = (int) $invoice->id;

        return $invoice;
    }
}
