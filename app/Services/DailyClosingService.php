<?php

namespace App\Services;

class DailyClosingService
{
    private \App\Models\DailyClosingModel $model;
    private bool $hasManualAdjustment = false;
    private bool $hasAdjustmentReason = false;
    private bool $hasDiscrepancyReason = false;
    private bool $hasIsLocked = false;
    private bool $hasSignatureName = false;
    private bool $hasSignatureBy = false;
    private bool $hasSignatureAt = false;
    private bool $hasLockedBy = false;

    public function __construct()
    {
        $this->model = new \App\Models\DailyClosingModel();

        $db = \Config\Database::connect();
        $this->hasManualAdjustment = $db->tableExists('daily_closings')
            && $db->fieldExists('manual_adjustment', 'daily_closings');
        $this->hasAdjustmentReason = $db->tableExists('daily_closings')
            && $db->fieldExists('adjustment_reason', 'daily_closings');
        $this->hasDiscrepancyReason = $db->tableExists('daily_closings')
            && $db->fieldExists('discrepancy_reason', 'daily_closings');
        $this->hasIsLocked = $db->tableExists('daily_closings') && $db->fieldExists('is_locked', 'daily_closings');
        $this->hasSignatureName = $db->tableExists('daily_closings') && $db->fieldExists('digital_signature_name', 'daily_closings');
        $this->hasSignatureBy = $db->tableExists('daily_closings') && $db->fieldExists('digital_signature_by', 'daily_closings');
        $this->hasLockedBy = $db->tableExists('daily_closings') && $db->fieldExists('locked_by', 'daily_closings');
        $this->hasSignatureAt = $db->tableExists('daily_closings') && $db->fieldExists('digital_signature_at', 'daily_closings');
    }

    public function snapshot(int $tenantId, ?int $branchId, string $date): array
    {
        $db = \Config\Database::connect();
        $snapshot = [
            'payments' => ['cash' => 0, 'bank_qr' => 0, 'wallet' => 0, 'other' => 0],
            'invoices' => ['billed' => 0, 'collected' => 0, 'refunds' => 0],
            'pos_orders' => ['count' => 0, 'billed' => 0, 'collected' => 0],
            'bookings' => ['total' => 0, 'no_show' => 0, 'cancelled' => 0],
            'opening_cash' => 0,
            'source_date' => $date,
        ];

        $applyScope = function ($builder, string $alias, int $tenantId, ?int $branchId, string $tableName) use ($db) {
            $builder->where($alias . '.tenant_id', $tenantId);
            if ($branchId && $db->fieldExists('branch_id', $tableName)) {
                $builder->where($alias . '.branch_id', $branchId);
            }

            return $builder;
        };

        if ($db->tableExists('payments')) {
            $paymentQuery = $db->table('payments p')
                ->select('p.method, COALESCE(SUM(p.amount), 0) AS total')
                ->join('invoices i', 'i.id = p.invoice_id', 'left')
                ->where('p.status', 'success')
                ->where('DATE(p.paid_at)', $date, false);

            if ($branchId && $db->fieldExists('branch_id', 'invoices')) {
                $paymentQuery->where('i.branch_id', $branchId);
            }

            $paymentQuery = $applyScope($paymentQuery, 'p', $tenantId, $branchId, 'payments')->groupBy('p.method');
            foreach ($paymentQuery->get()->getResultArray() as $row) {
                $method = (string) $row['method'];
                $key = in_array($method, ['cash', 'bank_qr', 'wallet'], true) ? $method : 'other';
                $snapshot['payments'][$key] += (float) $row['total'];
            }
        }

        if ($db->tableExists('invoices')) {
            $invoiceQuery = $db->table('invoices i')
                ->select('COALESCE(SUM(i.total_amount), 0) AS billed, COALESCE(SUM(i.paid_amount), 0) AS collected')
                ->where('DATE(i.created_at)', $date, false)
                ->whereNotIn('i.status', ['cancelled', 'refunded']);
            $snapshot['invoices'] = array_merge(
                $snapshot['invoices'],
                (array) $applyScope($invoiceQuery, 'i', $tenantId, $branchId, 'invoices')->get()->getRowArray()
            );

            $refundQuery = $db->table('invoices i')
                ->select('COALESCE(SUM(i.total_amount), 0) AS refunds')
                ->where('DATE(i.updated_at)', $date, false)
                ->where('i.status', 'refunded');
            $snapshot['invoices']['refunds'] = (float) (($applyScope($refundQuery, 'i', $tenantId, $branchId, 'invoices')
                ->get()
                ->getRowArray()['refunds'] ?? 0));

            $snapshot['invoices']['billed'] = (float) ($snapshot['invoices']['billed'] ?? 0);
            $snapshot['invoices']['collected'] = (float) ($snapshot['invoices']['collected'] ?? 0);
            $snapshot['invoices']['refunds'] = (float) ($snapshot['invoices']['refunds'] ?? 0);
        }

        if ($db->tableExists('pos_orders')) {
            $posQuery = $db->table('pos_orders p')
                ->select('COUNT(*) AS total_count, COALESCE(SUM(p.total_amount), 0) AS billed, COALESCE(SUM(p.paid_amount), 0) AS collected')
                ->where('DATE(p.created_at)', $date, false)
                ->where('p.status !=', 'cancelled');
            $posRows = (array) $applyScope($posQuery, 'p', $tenantId, $branchId, 'pos_orders')->get()->getRowArray();
            $snapshot['pos_orders'] = [
                'count' => (int) ($posRows['total_count'] ?? 0),
                'billed' => (float) ($posRows['billed'] ?? 0),
                'collected' => (float) ($posRows['collected'] ?? 0),
            ];
        }

        if ($db->tableExists('bookings')) {
            $bookingQuery = $db->table('bookings b')
                ->select("COUNT(*) AS total, COALESCE(SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END), 0) AS no_show, COALESCE(SUM(CASE WHEN status IN ('cancelled', 'refunded', 'expired') THEN 1 ELSE 0 END), 0) AS cancelled")
                ->where('b.booking_date', $date)
                ->where('b.deleted_at', null);
            $snapshot['bookings'] = (array) $applyScope($bookingQuery, 'b', $tenantId, $branchId, 'bookings')->get()->getRowArray();
        }

        $snapshot['payment_total'] = array_sum($snapshot['payments']);
        $snapshot['billed_total'] = (float) ($snapshot['invoices']['billed'] ?? 0) + (float) ($snapshot['pos_orders']['billed'] ?? 0);
        $snapshot['collected_total'] = (float) ($snapshot['invoices']['collected'] ?? 0) + (float) ($snapshot['pos_orders']['collected'] ?? 0);
        $snapshot['refund_total'] = (float) ($snapshot['invoices']['refunds'] ?? 0);
        $snapshot['bookings']['total'] = (int) ($snapshot['bookings']['total'] ?? 0);
        $snapshot['bookings']['no_show'] = (int) ($snapshot['bookings']['no_show'] ?? 0);
        $snapshot['bookings']['cancelled'] = (int) ($snapshot['bookings']['cancelled'] ?? 0);

        return $snapshot;
    }

    public function getOrCreate(int $tenantId, ?int $branchId, string $date): object
    {
        $closing = $this->model->findForScope($tenantId, $branchId, $date);
        if ($closing) {
            return $closing;
        }

        $id = $this->model->insert([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId ?: null,
            'closing_date' => $date,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->model->find((int) $id);
    }

    public function close(
        int $tenantId,
        ?int $branchId,
        string $date,
        ?int $userId,
        float $declaredCash = 0,
        float $manualAdjustment = 0,
        ?string $signatureName = null,
        ?string $adjustmentReason = null,
        ?string $notes = null
    ): array {
        $closing = $this->getOrCreate($tenantId, $branchId, $date);
        if ($closing->is_locked ?? 0) {
            return ['success' => false, 'message' => 'Ca đã khóa dữ liệu, không thể đóng/ghi đè.'];
        }
        if (($closing->status ?? '') === 'closed') {
            return ['success' => false, 'message' => 'Ca nay da duoc dong roi.'];
        }

        $snapshot = $this->snapshot($tenantId, $branchId, $date);
        $expectedCash = (float) ($snapshot['payments']['cash'] ?? 0);
        $discrepancy = (float) $declaredCash + $manualAdjustment - $expectedCash;
        $signatureName = trim((string) ($signatureName ?? ''));
        $normalNotes = trim((string) ($notes ?? ''));

        $payload = $this->buildClosingPayload($snapshot, $discrepancy, $signatureName, $manualAdjustment, $adjustmentReason, $notes, $userId);
        if ($this->hasIsLocked) {
            $payload['is_locked'] = 1;
            $payload['locked_at'] = date('Y-m-d H:i:s');
            $payload['locked_by'] = $userId;
        }
        if ($this->hasSignatureName) {
            $payload['digital_signature_name'] = $signatureName !== '' ? $signatureName : null;
        }
        if ($this->hasSignatureBy) {
            $payload['digital_signature_by'] = $userId;
        }
        if ($this->hasSignatureAt) {
            $payload['digital_signature_at'] = date('Y-m-d H:i:s');
        }

        if ($this->hasManualAdjustment) {
            $payload['manual_adjustment'] = $manualAdjustment;
        }
        if ($this->hasAdjustmentReason) {
            $payload['adjustment_reason'] = $adjustmentReason;
        }
        if ($notes !== null) {
            $payload['notes'] = $normalNotes !== '' ? $normalNotes : null;
        }

        if ($this->hasDiscrepancyReason && $discrepancy !== 0) {
            $payload['discrepancy_reason'] = $adjustmentReason
                ? 'Khác biệt do chỉnh tay: ' . $adjustmentReason
                : 'Khác biệt giữa số tiền quầy và hệ thống';
        }

        $ok = (bool) $this->model->update($closing->id, $payload);
        return [
            'success' => $ok,
            'message' => $ok ? 'Đã đóng ca.' : 'Không thể đóng ca.',
            'snapshot' => $snapshot,
            'discrepancy' => $discrepancy,
        ];
    }

    public function reopen(int $tenantId, ?int $branchId, string $date, ?int $userId): array
    {
        $closing = $this->model->findForScope($tenantId, $branchId, $date);
        if (! $closing) {
            return ['success' => false, 'message' => 'Không tìm thấy ca cần mở lại.'];
        }
        if ($closing->is_locked ?? 0) {
            if (! is_superadmin() && $userId === null) {
                return ['success' => false, 'message' => 'Ca đã khóa dữ liệu.'];
            }
        }

        $ok = (bool) $this->model->update($closing->id, [
            'status' => 'reopened',
            'reopened_by' => $userId,
            'reopened_at' => date('Y-m-d H:i:s'),
            'closed_by' => null,
            'closed_at' => null,
            'is_locked' => 0,
            'discrepancy_amount' => null,
        ]);
        return ['success' => $ok, 'message' => $ok ? 'Đã mở lại ca.' : 'Không thể mở lại ca.'];
    }

    public function toPdf(int $tenantId, ?int $branchId, string $date): string
    {
        return $this->printReport($tenantId, $branchId, $date);
    }

    private function buildClosingPayload(
        array $snapshot,
        float $discrepancy,
        ?string $signatureName,
        float $manualAdjustment,
        ?string $adjustmentReason,
        ?string $notes,
        ?int $userId
    ): array {
        $payload = [
            'status' => 'closed',
            'cash_total' => (float) ($snapshot['payments']['cash'] ?? 0),
            'qr_total' => (float) ($snapshot['payments']['bank_qr'] ?? 0),
            'wallet_total' => (float) ($snapshot['payments']['wallet'] ?? 0),
            'other_total' => (float) ($snapshot['payments']['other'] ?? 0),
            'billed_total' => (float) ($snapshot['billed_total'] ?? 0),
            'collected_total' => (float) ($snapshot['collected_total'] ?? 0),
            'refund_total' => (float) ($snapshot['refund_total'] ?? 0),
            'discrepancy_amount' => $discrepancy,
            'discrepancy_reason' => $discrepancy !== 0 ? 'Khác biệt giữa số tiền quầy và hệ thống' : null,
            'closed_by' => $userId,
            'closed_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->hasManualAdjustment) {
            $payload['manual_adjustment'] = $manualAdjustment;
        }
        if ($this->hasAdjustmentReason) {
            $payload['adjustment_reason'] = $adjustmentReason;
        }
        if ($notes !== null) {
            $payload['notes'] = $notes !== '' ? $notes : null;
        }
        if ($this->hasDiscrepancyReason) {
            $payload['discrepancy_reason'] = $discrepancy !== 0
                ? 'Khác biệt do chỉnh tay: ' . (($adjustmentReason !== null && $adjustmentReason !== '') ? $adjustmentReason : 'chưa có lý do')
                : null;
        }

        if ($this->hasSignatureName) {
            $payload['digital_signature_name'] = $signatureName !== '' ? $signatureName : null;
        }
        if ($this->hasSignatureBy) {
            $payload['digital_signature_by'] = $userId;
        }
        if ($this->hasSignatureAt) {
            $payload['digital_signature_at'] = date('Y-m-d H:i:s');
        }

        return $payload;
    }

    public function printReport(int $tenantId, ?int $branchId, string $date): string
    {
        $snapshot = $this->snapshot($tenantId, $branchId, $date);
        $closing = $this->getOrCreate($tenantId, $branchId, $date);
        $branchText = $branchId > 0 ? ('Chi nhánh #' . $branchId) : 'Toàn tenant';
        $printedAt = date('Y-m-d H:i:s');
        $discrepancy = (float) ($closing->discrepancy_amount ?? 0);

        $lines = [
            '<!doctype html><html><head><meta charset="utf-8"><title>Biên bản chốt ca ' . htmlspecialchars((string) $date, ENT_QUOTES, 'UTF-8') . '</title>',
            '<style>body{font-family:Arial,sans-serif;padding:16px;color:#111} h1{font-size:20px;margin-bottom:4px} .k{color:#6b7280} table{width:100%;border-collapse:collapse;margin:12px 0} td,th{border:1px solid #e5e7eb;padding:8px;text-align:left} .sig{margin-top:40px;display:flex;gap:80px} .sig div{flex:1} </style>',
            '</head><body>',
            '<h1>Biên bản chốt ca thương mại</h1>',
            '<div class="k">Ngày: ' . htmlspecialchars((string) $date, ENT_QUOTES, 'UTF-8') . ' | Kênh: ' . htmlspecialchars((string) $branchText, ENT_QUOTES, 'UTF-8') . ' | In lúc: ' . htmlspecialchars((string) $printedAt, ENT_QUOTES, 'UTF-8') . '</div>',
            '<table><thead><tr><th>Mục</th><th>Giá trị</th></tr></thead><tbody>',
            '<tr><td>Trạng thái</td><td>' . htmlspecialchars((string) ($closing->status ?? '-'), ENT_QUOTES, 'UTF-8') . '</td></tr>',
            '<tr><td>Booking tổng</td><td>' . (int) ($snapshot['bookings']['total'] ?? 0) . '</td></tr>',
            '<tr><td>No-show</td><td>' . (int) ($snapshot['bookings']['no_show'] ?? 0) . '</td></tr>',
            '<tr><td>Hủy/hủy hoàn</td><td>' . (int) ($snapshot['bookings']['cancelled'] ?? 0) . '</td></tr>',
            '<tr><td>Cash hệ thống</td><td>' . number_format((float) ($snapshot['payments']['cash'] ?? 0), 0, ',', '.') . 'đ</td></tr>',
            '<tr><td>QR</td><td>' . number_format((float) ($snapshot['payments']['bank_qr'] ?? 0), 0, ',', '.') . 'đ</td></tr>',
            '<tr><td>Ví</td><td>' . number_format((float) (($snapshot['payments']['wallet'] ?? 0) + ($snapshot['payments']['other'] ?? 0)), 0, ',', '.') . 'đ</td></tr>',
            '<tr><td>Tổng hóa đơn</td><td>' . number_format((float) ($snapshot['billed_total'] ?? 0), 0, ',', '.') . 'đ</td></tr>',
            '<tr><td>Đã thu</td><td>' . number_format((float) ($snapshot['collected_total'] ?? 0), 0, ',', '.') . 'đ</td></tr>',
            '<tr><td>Hàng chênh</td><td>' . number_format($discrepancy, 0, ',', '.') . 'đ</td></tr>',
            '</tbody></table>',
            '<div class="k">Ghi chú: ' . htmlspecialchars((string) ($closing->notes ?? '-'), ENT_QUOTES, 'UTF-8') . '</div>',
            '<div class="sig"><div>Nhân viên lập ca: ' . htmlspecialchars((string) ($closing->digital_signature_name ?? '-'), ENT_QUOTES, 'UTF-8') . '<br/>Chữ ký số: ' . htmlspecialchars((string) (($closing->digital_signature_at ?? '-') ?: '-'), ENT_QUOTES, 'UTF-8') . '</div><div>Ngày xác nhận: ' . htmlspecialchars((string) ($closing->closed_at ?? '-'), ENT_QUOTES, 'UTF-8') . '<br/>Mã xác nhận: #' . (int) ($closing->id ?? 0) . '</div></div>',
            '<script>window.print();</script>',
            '</body></html>',
        ];

        return implode(PHP_EOL, $lines);
    }

    public function snapshotRowsForCsv(int $tenantId, ?int $branchId, string $date): array
    {
        $snapshot = $this->snapshot($tenantId, $branchId, $date);

        return [
            ['Ngay', $date],
            ['Phong phu', $tenantId],
            ['Nhom', $branchId ? ('Chi nhanh ' . $branchId) : 'Toan tenant'],
            ['Mat tien', (float) ($snapshot['payments']['cash'] ?? 0)],
            ['QR ngan hang', (float) ($snapshot['payments']['bank_qr'] ?? 0)],
            ['Vi / MoMo', (float) ($snapshot['payments']['wallet'] ?? 0)],
            ['Khac', (float) ($snapshot['payments']['other'] ?? 0)],
            ['Doanh thu hoa don', (float) ($snapshot['invoices']['billed'] ?? 0)],
            ['Da thu', (float) ($snapshot['invoices']['collected'] ?? 0)],
            ['Hoan tien', (float) ($snapshot['invoices']['refunds'] ?? 0)],
            ['POS hoa don', (float) ($snapshot['pos_orders']['billed'] ?? 0)],
            ['POS thu', (float) ($snapshot['pos_orders']['collected'] ?? 0)],
            ['Tong booking', (int) ($snapshot['bookings']['total'] ?? 0)],
            ['No show', (int) ($snapshot['bookings']['no_show'] ?? 0)],
            ['Da huy', (int) ($snapshot['bookings']['cancelled'] ?? 0)],
        ];
    }
}
