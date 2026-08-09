<?php

namespace App\Services;

use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Models\RefundModel;
use App\Entities\Invoice;
use CodeIgniter\Database\Exceptions\DatabaseException;

class InvoiceService
{
    protected InvoiceModel $invoiceModel;
    protected PaymentModel $paymentModel;
    protected RefundModel $refundModel;

    public function __construct()
    {
        $this->invoiceModel = new InvoiceModel();
        $this->paymentModel = new PaymentModel();
        $this->refundModel = new RefundModel();
    }

    /**
     * Create invoice
     */
    public function createInvoice(int $tenantId, ?int $branchId, string $invoiceCode, float $totalAmount, array $data = []): Invoice
    {
        $totalAmount = round($totalAmount, 2);
        $discountAmount = round((float) ($data['discount_amount'] ?? 0), 2);
        if ($tenantId <= 0 || $totalAmount < 0 || $discountAmount < 0 || $discountAmount > $totalAmount) {
            throw new \InvalidArgumentException('Invalid invoice amount or tenant');
        }
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $invoice = new Invoice();
            $invoice->tenant_id = $tenantId;
            $invoice->branch_id = $branchId;
            $invoice->invoice_code = $invoiceCode;
            $invoice->customer_type = $data['customer_type'] ?? 'guest';
            $invoice->player_id = $data['player_id'] ?? null;
            $invoice->ref_type = $data['ref_type'] ?? null;
            $invoice->ref_id = $data['ref_id'] ?? null;
            $invoice->subtotal = $totalAmount;
            $invoice->discount_amount = $discountAmount;
            $invoice->total_amount = round($totalAmount - $discountAmount, 2);
            $invoice->paid_amount = 0;
            $invoice->status = 'unpaid';
            $invoice->note = $data['note'] ?? null;
            $invoice->created_by = $data['created_by'] ?? null;
            $invoice->created_at = date('Y-m-d H:i:s');
            $invoice->updated_at = date('Y-m-d H:i:s');

            $invoiceId = $this->invoiceModel->insert($invoice);
            $invoice->id = $invoiceId;

            $db->transComplete();
            return $invoice;
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Add payment to invoice
     */
    public function addPayment(int $invoiceId, float $amount, string $method, array $data = [], ?int $tenantId = null): array
    {
        return (new PaymentService())->pay($invoiceId, $amount, $method, $data, $tenantId);
    }

    /**
     * Cancel invoice
     */
    public function cancelInvoice(int $invoiceId, ?string $reason = null, ?int $tenantId = null): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $invoice = $this->invoiceModel->findForUpdate($invoiceId, $tenantId);
            if (!$invoice) {
                throw new \InvalidArgumentException('Invoice not found');
            }

            if ($invoice->status === 'cancelled') {
                throw new \InvalidArgumentException('Invoice already cancelled');
            }

            // If invoice is paid, need to refund first
            if ($invoice->paid_amount > 0) {
                throw new \InvalidArgumentException('Cannot cancel paid invoice. Please refund first.');
            }

            $this->invoiceModel->update($invoiceId, [
                'status' => 'cancelled',
                'note' => $reason,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();
            return true;
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * Update invoice status
     */
    public function updateStatus(int $invoiceId, ?int $tenantId = null): string
    {
        $invoice = $tenantId === null
            ? $this->invoiceModel->find($invoiceId)
            : $this->invoiceModel->findForTenant($invoiceId, $tenantId);
        if (!$invoice) {
            throw new \InvalidArgumentException('Invoice not found');
        }

        $newStatus = $this->calculateInvoiceStatus($invoice->total_amount, $invoice->paid_amount);

        $this->invoiceModel->update($invoiceId, [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $newStatus;
    }

    /**
     * Mark invoice as paid (full payment)
     */
    public function markPaid(int $invoiceId, string $method = 'cash', array $data = []): array
    {
        $invoice = $this->invoiceModel->find($invoiceId);
        if (!$invoice) {
            throw new \InvalidArgumentException('Invoice not found');
        }

        if ($invoice->status === 'paid') {
            throw new \InvalidArgumentException('Invoice already paid');
        }

        if ($invoice->status === 'cancelled') {
            throw new \InvalidArgumentException('Invoice is cancelled');
        }

        $remaining = $invoice->total_amount - $invoice->paid_amount;
        if ($remaining <= 0) {
            throw new \InvalidArgumentException('Invoice already fully paid');
        }

        return $this->addPayment($invoiceId, $remaining, $method, $data);
    }

    /**
     * Calculate invoice status based on payment
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
            $lastNumber = (int) substr($lastPayment->payment_code, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get invoice with payments
     */
    public function getInvoiceWithPayments(int $invoiceId, ?int $tenantId = null): array
    {
        $invoice = $this->invoiceModel->getWithDetails($invoiceId, $tenantId);
        if (!$invoice) {
            throw new \InvalidArgumentException('Invoice not found');
        }

        $payments = $this->paymentModel->getByInvoice($invoiceId, $tenantId);
        $refunds = $this->refundModel->getByInvoice($invoiceId, $tenantId);

        return [
            'invoice' => $invoice,
            'payments' => $payments,
            'refunds' => $refunds,
        ];
    }

    /**
     * Get invoices by reference
     */
    public function getInvoicesByRef(string $refType, int $refId, ?int $tenantId = null): array
    {
        $builder = $this->invoiceModel
            ->where('ref_type', $refType)
            ->where('ref_id', $refId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }
}
