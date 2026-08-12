<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\DailyClosingService;
use App\Models\BranchModel;

class DailyClosingController extends BaseController
{
    private DailyClosingService $service;
    private BranchModel $branchModel;

    public function __construct()
    {
        $this->service = new DailyClosingService();
        $this->branchModel = model(BranchModel::class);
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getGet('date'));
        $branchId = (int) current_branch_id();
        $scopeBranch = is_superadmin() ? (int) $this->request->getGet('branch_id') : $branchId;
        $scopeBranch = $scopeBranch > 0 ? $scopeBranch : null;

        $snapshot = $tenantId > 0 ? $this->service->snapshot($tenantId, $scopeBranch, $date) : [];
        $closing = $tenantId > 0 ? $this->service->getOrCreate($tenantId, $scopeBranch, $date) : null;

        return $this->render('admin/daily_closing/index', [
            'pageTitle' => 'Daily Closing',
            'date' => $date,
            'snapshot' => $snapshot,
            'closing' => $closing,
            'branches' => $tenantId > 0 ? $this->branchModel->where('tenant_id', $tenantId)->where('deleted_at', null)->findAll() : [],
            'scopeBranchId' => $scopeBranch,
        ]);
    }

    public function close()
    {
        $tenantId = (int) current_tenant_id();
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getPost('closing_date'));
        $branchId = is_superadmin() ? (int) $this->request->getPost('branch_id') : (int) current_branch_id();
        $branchId = $branchId > 0 ? $branchId : null;

        $declaredCash = (float) $this->request->getPost('declared_cash');
        $manualAdjustment = (float) $this->request->getPost('manual_adjustment');
        $signatureName = trim((string) $this->request->getPost('signature_name'));
        $adjustmentReason = trim((string) $this->request->getPost('adjustment_reason'));
        $notes = trim((string) $this->request->getPost('notes'));

        $result = $this->service->close(
            $tenantId,
            $branchId,
            $date,
            user_id(),
            $declaredCash,
            $manualAdjustment,
            $signatureName !== '' ? $signatureName : null,
            $adjustmentReason !== '' ? $adjustmentReason : null,
            $notes !== '' ? $notes : null
        );

        if (function_exists('log_audit')) {
            log_audit([
                'action' => 'daily_closing_close',
                'table' => 'daily_closings',
                'tenant_id' => $tenantId,
                'metadata' => [
                    'branch_id' => $branchId,
                    'closing_date' => $date,
                    'discrepancy' => $result['discrepancy'] ?? null,
                ],
                'data' => $result,
            ]);
        }

        return redirect()->to('/admin/daily-closing' . $this->buildDateQuery($branchId, $date))
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function reopen()
    {
        $tenantId = (int) current_tenant_id();
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getPost('closing_date'));
        $branchId = is_superadmin() ? (int) $this->request->getPost('branch_id') : (int) current_branch_id();
        $branchId = $branchId > 0 ? $branchId : null;

        $result = $this->service->reopen($tenantId, $branchId, $date, user_id());
        if (function_exists('log_audit')) {
            log_audit([
                'action' => 'daily_closing_reopen',
                'table' => 'daily_closings',
                'tenant_id' => $tenantId,
                'metadata' => [
                    'branch_id' => $branchId,
                    'closing_date' => $date,
                ],
                'data' => $result,
            ]);
        }
        return redirect()->to('/admin/daily-closing' . $this->buildDateQuery($branchId, $date))
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function csv()
    {
        $tenantId = (int) current_tenant_id();
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getGet('date'));
        $branchId = is_superadmin() ? (int) $this->request->getGet('branch_id') : (int) current_branch_id();
        $branchId = $branchId > 0 ? $branchId : null;

        $rows = $this->service->snapshotRowsForCsv($tenantId, $branchId, $date);
        $lines = [];
        foreach ($rows as $row) {
            $line = array_map(fn ($value) => '"' . str_replace('"', '""', (string) $value) . '"', $row);
            $lines[] = implode(',', $line);
        }

        return $this->response->download('daily-closing-' . $date . '.csv', implode("\r\n", $lines));
    }

    public function print()
    {
        $tenantId = (int) current_tenant_id();
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getGet('date'));
        $branchId = is_superadmin() ? (int) $this->request->getGet('branch_id') : (int) current_branch_id();
        $branchId = $branchId > 0 ? $branchId : null;

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setHeader('Content-Disposition', 'inline; filename="daily-closing-' . $date . '.html"')
            ->setBody($this->service->printReport($tenantId, $branchId, $date));
    }

    public function pdf()
    {
        $tenantId = (int) current_tenant_id();
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getGet('date'));
        $branchId = is_superadmin() ? (int) $this->request->getGet('branch_id') : (int) current_branch_id();
        $branchId = $branchId > 0 ? $branchId : null;

        $html = $this->service->toPdf($tenantId, $branchId, $date);
        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=utf-8')
            ->setHeader('Content-Disposition', 'inline; filename="daily-closing-' . $date . '.html"')
            ->setBody($html . '<script>window.print();</script>');
    }

    private function buildDateQuery(?int $branchId, string $date): string
    {
        $params = ['date' => $date];
        if ($branchId > 0) {
            $params['branch_id'] = $branchId;
        }
        return '?' . http_build_query($params);
    }

}
