<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\DailyClosingService;

class DailyClosingController extends BaseController
{
    private DailyClosingService $service;

    public function __construct()
    {
        $this->service = new DailyClosingService();
    }

    public function index()
    {
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getGet('date'));
        $branchId = (int) current_branch_id();
        $branchId = is_superadmin() ? null : ($branchId ?: null);
        $this->viewData['pageTitle'] = 'Daily Closing · Chốt ca';
        $this->viewData['date'] = $date;
        $this->viewData['snapshot'] = $this->service->snapshot((int) current_tenant_id(), $branchId, $date);
        $this->viewData['closing'] = $this->service->getOrCreate((int) current_tenant_id(), $branchId, $date);
        return $this->render('admin/daily_closing/index', $this->viewData);
    }

    public function close()
    {
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getPost('closing_date'));
        $branchId = (int) current_branch_id();
        $branchId = is_superadmin() ? null : ($branchId ?: null);
        $ok = $this->service->close((int) current_tenant_id(), $branchId, $date, user_id(), (float) $this->request->getPost('declared_cash'), $this->request->getPost('notes'));
        return redirect()->to('/admin/daily-closing?date=' . $date)->with($ok ? 'success' : 'error', $ok ? 'Đã chốt ca và lưu số liệu.' : 'Không thể chốt ca.');
    }

    public function reopen()
    {
        $date = \App\Services\OperationsDashboardService::normalizeDate($this->request->getPost('closing_date'));
        $branchId = (int) current_branch_id();
        $branchId = is_superadmin() ? null : ($branchId ?: null);
        $ok = $this->service->reopen((int) current_tenant_id(), $branchId, $date, user_id());
        return redirect()->to('/admin/daily-closing?date=' . $date)->with($ok ? 'success' : 'error', $ok ? 'Đã mở lại ca để điều chỉnh.' : 'Không thể mở lại ca.');
    }
}
