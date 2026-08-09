<?php

namespace App\Services;

use App\Models\PaymentModel;
use App\Models\InvoiceModel;
use App\Models\RefundModel;
use App\Models\PaymentQrConfigModel;
use App\Entities\Payment;
use App\Entities\Refund;
use CodeIgniter\Database\Exceptions\DatabaseException;

class PaymentService
{
    protected PaymentModel $paymentModel;
    protected InvoiceModel $invoiceModel;
    protected RefundModel $refundModel;
    protected PaymentQrConfigModel $qrConfigModel;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->invoiceModel = new InvoiceModel();
        $this->refundModel = new RefundModel();
        $this->qrConfigModel = new PaymentQrConfigModel();
    }

    /**
     * Pay cash
     */
    public function payCash(int $invoiceId, float $amount, array $data = []): array
    {
        return $this->processPayment($invoiceId, $amount, 'cash', $data);
    }

    /**
     * Pay by wallet
     */
    public function payByWallet(int $invoiceId, float $amount, int $playerId, array $data = []): array
    {
        // TODO: Integrate with WalletService
        // For now, just process as regular payment
        return $this->processPayment($invoiceId, $amount, 'wallet', array_merge($data, ['player_id' => $playerId]));
    }

    /**
     * Create bank QR payment
     */
    public function createBankQr(int $invoiceId): array
    {
        $invoice = $this->invoiceModel->find($invoiceId);
        if (!$invoice) {
            throw new \InvalidArgumentException('Invoice not found');
        }

        $qrConfig = $this->qrConfigModel->getActiveByTenant($invoice['tenant_id']);
        if (!$qrConfig) {
            throw new \InvalidArgumentException('Bank QR config not found');
        }

        // Generate payment code
        $paymentCode = $this->generatePaymentCode($invoice['tenant_id']);

        // Create pending payment
        $paymentData = [
            'tenant_id' => $invoice['tenant_id'],
            'invoice_id' => $invoiceId,
            'payment_code' => $paymentCode,
            'method' => 'bank_qr',
            'amount' => $invoice['total_amount'] - $invoice['paid_amount'],
            'status' => 'pending',
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $paymentId = $this->paymentModel->insert($paymentData);

        // Generate QR content (Vietnam QR format - VietQR)
        $qrContent = $this->generateVietQR($qrConfig, $invoice, $paymentCode);

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'payment_code' => $paymentCode,
            'qr_content' => $qrContent,
            'amount' => $paymentData['amount'],
            'bank_info' => [
                'bank_name' => $qrConfig['bank_name'],
                'account_number' => $qrConfig['bank_account'],
                'account_name' => $qrConfig['account_name'],
            ],
        ];
    }

    /**
     * Confirm bank payment
     */
    public function confirmBankPayment(int $paymentId, string $transactionRef, ?string $idempotencyKey = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $payment = $this->paymentModel->find($paymentId);
            if (!$payment) {
                throw new \InvalidArgumentException('Payment not found');
            }

            if ($payment['status'] !== 'pending') {
                throw new \InvalidArgumentException('Payment already processed');
            }

            // Check idempotency
            if ($idempotencyKey) {
                $existing = $this->paymentModel->findByIdempotencyKey($idempotencyKey);
                if ($existing && $existing['id'] != $paymentId) {
                    throw new \InvalidArgumentException('Duplicate payment confirmation');
                }
            }

            // Update payment
            $this->paymentModel->update($paymentId, [
                'status' => 'success',
                'transaction_ref' => $transactionRef,
                'idempotency_key' => $idempotencyKey,
                'paid_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Update invoice
            $invoice = $this->invoiceModel->find($payment['invoice_id']);
            $newPaidAmount = $invoice['paid_amount'] + $payment['amount'];
            $newStatus = $this->calculateInvoiceStatus($invoice['total_amount'], $newPaidAmount);

            $this->invoiceModel->update($payment['invoice_id'], [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            return [
                'success' => true,
                'payment_code' => $payment['payment_code'],
                'new_status' => $newStatus,
            ];
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Process refund
     */
    public function refund(int $invoiceId, float $amount, ?string $reason = null, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $invoice = $this->invoiceModel->find($invoiceId);
            if (!$invoice) {
                throw new \InvalidArgumentException('Invoice not found');
            }

            if ($invoice['status'] === 'cancelled') {
                throw new \InvalidArgumentException('Invoice is cancelled');
            }

            // Get the latest successful payment
            $payments = $this->paymentModel
                ->where('invoice_id', $invoiceId)
                ->where('status', 'success')
                ->orderBy('created_at', 'DESC')
                ->findAll();

            if (empty($payments)) {
                throw new \InvalidArgumentException('No successful payment to refund');
            }

            $payment = $payments[0];

            // Check refund amount
            if ($amount > $payment['amount']) {
                throw new \InvalidArgumentException('Refund amount exceeds payment amount');
            }

            // Create refund record
            $refund = new Refund();
            $refund->tenant_id = $invoice['tenant_id'];
            $refund->payment_id = $payment['id'];
            $refund->invoice_id = $invoiceId;
            $refund->amount = $amount;
            $refund->reason = $reason;
            $refund->status = 'completed';
            $refund->processed_by = $userId;
            $refund->created_at = date('Y-m-d H:i:s');
            $refund->updated_at = date('Y-m-d H:i:s');

            $refundId = $this->refundModel->insert($refund);

            // Update invoice
            $newPaidAmount = $invoice['paid_amount'] - $amount;
            $newStatus = $this->calculateInvoiceStatus($invoice['total_amount'], $newPaidAmount);

            $this->invoiceModel->update($invoiceId, [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus === 'unpaid' ? 'refunded' : $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            return [
                'success' => true,
                'refund_id' => $refundId,
                'new_paid_amount' => $newPaidAmount,
                'new_status' => $newStatus,
            ];
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Prevent duplicate payment
     */
    public function preventDuplicatePayment(string $idempotencyKey): bool
    {
        $existing = $this->paymentModel->findByIdempotencyKey($idempotencyKey);
        return $existing !== null;
    }

    /**
     * Process payment (internal)
     */
    protected function processPayment(int $invoiceId, float $amount, string $method, array $data = []): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $invoice = $this->invoiceModel->find($invoiceId);
            if (!$invoice) {
                throw new \InvalidArgumentException('Invoice not found');
            }

            if ($invoice['status'] === 'cancelled') {
                throw new \InvalidArgumentException('Invoice is cancelled');
            }

            // Check idempotency
            if (!empty($data['idempotency_key'])) {
                $existingPayment = $this->paymentModel->findByIdempotencyKey($data['idempotency_key']);
                if ($existingPayment) {
                    return [
                        'success' => true,
                        'payment_id' => $existingPayment['id'],
                        'payment_code' => $existingPayment['payment_code'],
                        'duplicate' => true,
                    ];
                }
            }

            // Generate payment code
            $paymentCode = $this->generatePaymentCode($invoice['tenant_id']);

            // Create payment
            $paymentData = [
                'tenant_id' => $invoice['tenant_id'],
                'invoice_id' => $invoiceId,
                'payment_code' => $paymentCode,
                'method' => $method,
                'amount' => $amount,
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'status' => 'success',
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'paid_at' => date('Y-m-d H:i:s'),
                'created_by' => $data['created_by'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $paymentId = $this->paymentModel->insert($paymentData);

            // Update invoice
            $newPaidAmount = $invoice['paid_amount'] + $amount;
            $newStatus = $this->calculateInvoiceStatus($invoice['total_amount'], $newPaidAmount);

            $this->invoiceModel->update($invoiceId, [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'payment_code' => $paymentCode,
                'new_paid_amount' => $newPaidAmount,
                'new_status' => $newStatus,
            ];
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Generate unique payment code
     */
    protected function generatePaymentCode(int $tenantId): string
    {
        $date = date('Ymd');
        $prefix = "PAY{$tenantId}{$date}";

        $lastPayment = $this->paymentModel
            ->where('payment_code LIKE', $prefix . '%')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int)substr($lastPayment['payment_code'], -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate invoice status
     */
    protected function calculateInvoiceStatus(float $totalAmount, float $paidAmount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        } elseif ($paidAmount >= $totalAmount) {
            return 'paid';
        } else {
            return 'partial';
        }
    }

    /**
     * Generate VietQR content
     */
    protected function generateVietQR($qrConfig, $invoice, string $paymentCode): string
    {
        // Simple QR format (in real implementation, use proper VietQR library)
        $data = [
            'bank' => $qrConfig['bank_name'],
            'account' => $qrConfig['bank_account'],
            'name' => $qrConfig['account_name'],
            'amount' => $invoice['total_amount'] - $invoice['paid_amount'],
            'content' => "Payment for invoice {$invoice['invoice_code']}",
        ];

        return json_encode($data);
    }

    /**
     * Get QR config for tenant
     */
    public function getQrConfig(int $tenantId): ?array
    {
        $config = $this->qrConfigModel->getActiveByTenant($tenantId);
        return $config ? $config->toArray() : null;
    }
}
