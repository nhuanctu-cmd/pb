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
    protected WalletService $walletService;

    public function __construct()
    {
        $this->paymentModel = new PaymentModel();
        $this->invoiceModel = new InvoiceModel();
        $this->refundModel = new RefundModel();
        $this->qrConfigModel = new PaymentQrConfigModel();
        $this->walletService = new WalletService();
    }

    /**
     * Pay cash
     */
    public function payCash(int $invoiceId, float $amount, array $data = [], ?int $tenantId = null): array
    {
        return $this->pay($invoiceId, $amount, 'cash', $data, $tenantId);
    }

    public function pay(int $invoiceId, float $amount, string $method, array $data = [], ?int $tenantId = null): array
    {
        if (! in_array($method, ['cash', 'wallet', 'bank_qr', 'momo', 'stripe'], true)) {
            throw new \InvalidArgumentException('Unsupported payment method');
        }

        return $this->processPayment($invoiceId, $amount, $method, $data, $tenantId);
    }

    /**
     * Pay by wallet
     */
    public function payByWallet(int $invoiceId, float $amount, int $playerId, array $data = [], ?int $tenantId = null): array
    {
        return $this->processPayment($invoiceId, $amount, 'wallet', array_merge($data, ['player_id' => $playerId]), $tenantId);
    }

    /**
     * Create bank QR payment
     */
    public function createBankQr(int $invoiceId, ?int $tenantId = null, array $data = []): array
    {
        $invoice = $tenantId === null
            ? $this->invoiceModel->find($invoiceId)
            : $this->invoiceModel->findForTenant($invoiceId, $tenantId);
        if (!$invoice) {
            throw new \InvalidArgumentException('Invoice not found');
        }

        $remaining = round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2);
        if ($remaining <= 0 || in_array($invoice->status, ['paid', 'cancelled', 'refunded'], true)) {
            throw new \InvalidArgumentException('Invoice is not payable');
        }

        $qrConfig = $this->qrConfigModel->getActiveByTenant((int) $invoice->tenant_id);
        if (!$qrConfig) {
            throw new \InvalidArgumentException('Bank QR config not found');
        }

        // Generate payment code
        $paymentCode = $this->generatePaymentCode((int) $invoice->tenant_id);

        // Create pending payment
        $paymentData = [
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoiceId,
            'payment_code' => $paymentCode,
            'method' => 'bank_qr',
            'amount' => $remaining,
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
            'bank_name' => $qrConfig->bank_name,
            'account_number' => $qrConfig->bank_account,
            'account_name' => $qrConfig->account_name,
            ],
        ];
    }

    /**
     * Confirm bank payment
     */
    public function confirmBankPayment(int $paymentId, string $transactionRef, ?string $idempotencyKey = null, ?int $tenantId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $payment = $this->paymentModel->findForUpdate($paymentId, $tenantId);
            if (!$payment) {
                throw new \InvalidArgumentException('Payment not found');
            }

            if ($payment->status === 'success') {
                if ($idempotencyKey && $payment->idempotency_key === $idempotencyKey) {
                    $db->transComplete();
                    return [
                        'success' => true, 'duplicate' => true,
                        'payment_id' => $payment->id,
                        'payment_code' => $payment->payment_code,
                    ];
                }
                throw new \InvalidArgumentException('Payment already processed');
            }
            if ($payment->status !== 'pending') {
                throw new \InvalidArgumentException('Payment cannot be confirmed');
            }

            // Check idempotency
            if ($idempotencyKey) {
                $existing = $this->paymentModel->findByIdempotencyKey($idempotencyKey, $tenantId);
                if ($existing && $existing['id'] != $paymentId) {
                    throw new \InvalidArgumentException('Duplicate payment confirmation');
                }
            }

            if (trim($transactionRef) === '') {
                throw new \InvalidArgumentException('Transaction reference is required');
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
            $invoice = $this->invoiceModel->findForUpdate((int) $payment->invoice_id, $tenantId);
            if (! $invoice) {
                throw new \InvalidArgumentException('Invoice not found');
            }
            $newPaidAmount = round((float) $invoice->paid_amount + (float) $payment->amount, 2);
            if ($newPaidAmount > (float) $invoice->total_amount) {
                throw new \InvalidArgumentException('Payment exceeds invoice total');
            }
            $newStatus = $this->calculateInvoiceStatus($invoice->total_amount, $newPaidAmount);

            $this->invoiceModel->update($payment->invoice_id, [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Payment confirmation failed');
            }

            return [
                'success' => true,
                'payment_code' => $payment->payment_code,
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
    public function refund(int $invoiceId, float $amount, ?string $reason = null, ?int $userId = null, ?int $tenantId = null): array
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero');
        }
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $invoice = $this->invoiceModel->findForUpdate($invoiceId, $tenantId);
            if (!$invoice) {
                throw new \InvalidArgumentException('Invoice not found');
            }

            if ($invoice->status === 'cancelled') {
                throw new \InvalidArgumentException('Invoice is cancelled');
            }

            $refundableTotal = round((float) $invoice->paid_amount, 2);
            if ($amount > $refundableTotal) {
                throw new \InvalidArgumentException('Refund amount exceeds refundable balance');
            }

            // Allocate the refund across successful payments, preserving a
            // traceable payment -> refund relationship.
            $paymentQuery = $this->paymentModel
                ->where('invoice_id', $invoiceId)
                ->where('status', 'success');
            if ($tenantId !== null) {
                $paymentQuery->where('tenant_id', $tenantId);
            }
            $payments = $paymentQuery->orderBy('created_at', 'DESC')->findAll();

            if (empty($payments)) {
                throw new \InvalidArgumentException('No successful payment to refund');
            }

            $remainingRefund = $amount;
            $refundIds = [];
            foreach ($payments as $payment) {
                if ($remainingRefund <= 0) {
                    break;
                }
                $alreadyRefunded = $this->refundModel
                    ->selectSum('amount')
                    ->where('payment_id', $payment->id)
                    ->where('status', 'completed')
                    ->first();
                $available = max(0, round((float) $payment->amount - (float) ($alreadyRefunded->amount ?? 0), 2));
                $chunk = min($remainingRefund, $available);
                if ($chunk <= 0) {
                    continue;
                }
                $refund = new Refund();
                $refund->tenant_id = $invoice->tenant_id;
                $refund->payment_id = $payment->id;
                $refund->invoice_id = $invoiceId;
                $refund->amount = $chunk;
                $refund->reason = $reason;
                $refund->status = 'completed';
                $refund->processed_by = $userId;
                $refundIds[] = $this->refundModel->insert($refund);
                $remainingRefund = round($remainingRefund - $chunk, 2);
            }
            if ($remainingRefund > 0.01) {
                throw new \InvalidArgumentException('Refund amount exceeds refundable payments');
            }

            // Update invoice
            $newPaidAmount = round((float) $invoice->paid_amount - $amount, 2);
            $newStatus = $this->calculateInvoiceStatus($invoice->total_amount, $newPaidAmount);

            $this->invoiceModel->update($invoiceId, [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus === 'unpaid' ? 'refunded' : $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            return [
                'success' => true,
                'refund_ids' => $refundIds,
                'new_paid_amount' => $newPaidAmount,
                'new_status' => $newStatus === 'unpaid' ? 'refunded' : $newStatus,
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
    protected function processPayment(int $invoiceId, float $amount, string $method, array $data = [], ?int $tenantId = null): array
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero');
        }
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $invoice = $this->invoiceModel->findForUpdate($invoiceId, $tenantId);
            if (!$invoice) {
                throw new \InvalidArgumentException('Invoice not found');
            }

            if (in_array($invoice->status, ['cancelled', 'refunded', 'paid'], true)) {
                throw new \InvalidArgumentException('Invoice is not payable');
            }

            // Check idempotency
            if (!empty($data['idempotency_key'])) {
                $key = trim((string) $data['idempotency_key']);
                if (strlen($key) > 64) {
                    throw new \InvalidArgumentException('Idempotency key is too long');
                }
                $existingPayment = $this->paymentModel->findByIdempotencyKey($key, $tenantId);
                if ($existingPayment) {
                    if ((int) $existingPayment->invoice_id !== $invoiceId
                        || (float) $existingPayment->amount !== $amount
                        || $existingPayment->method !== $method) {
                        throw new \InvalidArgumentException('Idempotency key was reused with different payment data');
                    }
                    $db->transComplete();
                    return [
                        'success' => true,
                        'payment_id' => $existingPayment->id,
                        'payment_code' => $existingPayment->payment_code,
                        'duplicate' => true,
                    ];
                }
                $data['idempotency_key'] = $key;
            }

            $remaining = round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2);
            if ($amount > $remaining) {
                throw new \InvalidArgumentException('Payment exceeds invoice balance');
            }

            if ($method === 'wallet') {
                $playerId = (int) ($data['player_id'] ?? 0);
                if ($playerId <= 0 || ($invoice->player_id !== null && (int) $invoice->player_id !== $playerId)) {
                    throw new \InvalidArgumentException('Wallet payer does not match invoice player');
                }
                if (! $this->walletService->pay(
                    $playerId,
                    (int) $invoice->tenant_id,
                    $amount,
                    'Payment for invoice ' . $invoice->invoice_code,
                    'invoice',
                    $invoiceId,
                    $data['created_by'] ?? null
                )) {
                    throw new \InvalidArgumentException('Insufficient wallet balance');
                }
            }

            // Generate payment code
            $paymentCode = $this->generatePaymentCode((int) $invoice->tenant_id);

            // Create payment
            $paymentData = [
                'tenant_id' => $invoice->tenant_id,
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
            if (! $paymentId) {
                throw new \RuntimeException('Payment could not be recorded');
            }

            // Update invoice
            $newPaidAmount = round((float) $invoice->paid_amount + $amount, 2);
            $newStatus = $this->calculateInvoiceStatus($invoice->total_amount, $newPaidAmount);

            if (! $this->invoiceModel->update($invoiceId, [
                'paid_amount' => $newPaidAmount,
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ])) {
                throw new \RuntimeException('Invoice payment status could not be updated');
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Payment transaction failed');
            }

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
        // Random suffix avoids the read-then-increment race under concurrent
        // cashiers/webhooks; the database unique key remains the final guard.
        return 'PAY' . $tenantId . date('YmdHis')
            . strtoupper(bin2hex(random_bytes(4)));
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
            'bank' => $qrConfig->bank_name,
            'account' => $qrConfig->bank_account,
            'name' => $qrConfig->account_name,
            'amount' => $invoice->total_amount - $invoice->paid_amount,
            'content' => "Payment for invoice {$invoice->invoice_code}",
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
