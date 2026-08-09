<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Models\BookingModel;
use App\Models\PlayerModel;
use App\Models\InvoiceModel;
use App\Models\PaymentModel;
use App\Models\PaymentQrConfigModel;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentController extends BaseController
{
    protected InvoiceService $invoiceService;
    protected PaymentService $paymentService;
    protected BookingModel $bookingModel;
    protected PlayerModel $playerModel;
    protected PaymentQrConfigModel $qrConfigModel;
    protected InvoiceModel $invoiceModel;
    protected PaymentModel $paymentModel;

    public function __construct()
    {
        $this->invoiceService = new InvoiceService();
        $this->paymentService = new PaymentService();
        $this->bookingModel = new BookingModel();
        $this->playerModel = new PlayerModel();
        $this->qrConfigModel = new PaymentQrConfigModel();
        $this->invoiceModel = new InvoiceModel();
        $this->paymentModel = new PaymentModel();
    }

    /**
     * Invoice list
     */
    public function index(): string
    {
        $tenantId = session()->get('tenant_id');
        $branchId = session()->get('branch_id');
        $status = $this->request->getGet('status');

        $invoices = $this->invoiceModel->getByTenant($tenantId, $branchId, $status);

        $data = [
            'invoices' => $invoices,
            'current_status' => $status,
        ];

        return view('admin/payments/invoices', $data);
    }

    /**
     * Invoice detail
     */
    public function detail(int $invoiceId): string
    {
        $details = $this->invoiceService->getInvoiceWithPayments(
            $invoiceId, (int) session()->get('tenant_id')
        );

        $data = [
            'invoice' => $details['invoice'],
            'payments' => $details['payments'],
            'refunds' => $details['refunds'],
        ];

        return view('admin/payments/detail', $data);
    }

    /**
     * Create invoice for booking
     */
    public function createBookingInvoice(int $bookingId): ResponseInterface
    {
        $tenantId = session()->get('tenant_id');
        $branchId = session()->get('branch_id');
        $userId = session()->get('user_id');

        $booking = $this->bookingModel->findForTenant($bookingId, (int) $tenantId);
        if (!$booking) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Booking not found']);
        }

        // Check if invoice already exists
        $existing = $this->invoiceService->getInvoicesByRef('booking', $bookingId, (int) $tenantId);
        if (!empty($existing)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invoice already exists']);
        }

        try {
            // Generate invoice code
            $invoiceCode = $this->generateInvoiceCode($tenantId, $branchId);

            // Create invoice
            $invoice = $this->invoiceService->createInvoice($tenantId, $branchId, $invoiceCode, $booking['total_amount'], [
                'customer_type' => $booking['customer_type'],
                'player_id' => $booking['player_id'],
                'ref_type' => 'booking',
                'ref_id' => $bookingId,
                'created_by' => $userId,
            ]);

            return $this->response->setJSON([
                'success' => true,
                'invoice_id' => $invoice->id,
                'invoice_code' => $invoice->invoice_code,
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Pay cash
     */
    public function payCash(int $invoiceId): ResponseInterface
    {
        $amount = (float)$this->request->getPost('amount');
        $userId = session()->get('user_id');

        try {
            $result = $this->paymentService->payCash($invoiceId, $amount, [
                'idempotency_key' => $this->request->getHeaderLine('Idempotency-Key')
                    ?: ('cash_' . $invoiceId . '_' . bin2hex(random_bytes(8))),
                'created_by' => $userId,
            ], (int) session()->get('tenant_id'));

            return $this->response->setJSON($result);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Create bank QR
     */
    public function createBankQr(int $invoiceId): ResponseInterface
    {
        try {
            $result = $this->paymentService->createBankQr(
                $invoiceId, (int) session()->get('tenant_id'), ['created_by' => session()->get('user_id')]
            );
            return $this->response->setJSON($result);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Confirm bank payment
     */
    public function confirmBankPayment(int $paymentId): ResponseInterface
    {
        $transactionRef = $this->request->getPost('transaction_ref');
        $idempotencyKey = $this->request->getPost('idempotency_key');

        try {
            $result = $this->paymentService->confirmBankPayment(
                $paymentId, (string) $transactionRef, $idempotencyKey,
                (int) session()->get('tenant_id')
            );
            return $this->response->setJSON($result);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Process refund
     */
    public function refund(int $invoiceId): ResponseInterface
    {
        $amount = (float)$this->request->getPost('amount');
        $reason = $this->request->getPost('reason');
        $userId = session()->get('user_id');

        try {
            $result = $this->paymentService->refund(
                $invoiceId, $amount, $reason, $userId, (int) session()->get('tenant_id')
            );
            return $this->response->setJSON($result);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel invoice
     */
    public function cancel(int $invoiceId): ResponseInterface
    {
        $reason = $this->request->getPost('reason');

        try {
            $success = $this->invoiceService->cancelInvoice(
                $invoiceId, $reason, (int) session()->get('tenant_id')
            );
            return $this->response->setJSON(['success' => $success]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(400)->setJSON(['error' => $e->getMessage()]);
        }
    }

    /**
     * QR Config management
     */
    public function qrConfig(): string
    {
        $tenantId = session()->get('tenant_id');
        $config = $this->qrConfigModel->getActiveByTenant($tenantId);

        $data = ['config' => $config];
        return view('admin/payments/qr_config', $data);
    }

    /**
     * Save QR config
     */
    public function saveQrConfig(): ResponseInterface
    {
        $tenantId = session()->get('tenant_id');
        $data = [
            'tenant_id' => $tenantId,
            'bank_name' => $this->request->getPost('bank_name'),
            'bank_account' => $this->request->getPost('bank_account'),
            'account_name' => $this->request->getPost('account_name'),
            'status' => 'active',
        ];

        $db = \Config\Database::connect();
        $db->transStart();
        $this->qrConfigModel->where('tenant_id', $tenantId)->set(['status' => 'inactive'])->update();
        if (! $this->qrConfigModel->insert($data)) {
            $db->transRollback();
            return $this->response->setStatusCode(422)->setJSON(['success' => false]);
        }
        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false]);
        }

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * Generate invoice code
     */
    protected function generateInvoiceCode(int $tenantId, ?int $branchId): string
    {
        $date = date('Ymd');
        $prefix = "INV{$tenantId}" . ($branchId ? "{$branchId}" : "00") . "{$date}";

        $lastInvoice = $this->invoiceModel
            ->where('invoice_code LIKE', $prefix . '%')
            ->orderBy('id', 'DESC')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int)substr($lastInvoice['invoice_code'], -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }
}
