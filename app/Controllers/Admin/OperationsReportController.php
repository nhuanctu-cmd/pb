<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;

class OperationsReportController extends BaseController
{
    private $service;
    private BranchModel $branchModel;

    public function __construct()
    {
        $this->service = service('operationsReportService');
        $this->branchModel = new BranchModel();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $report = $this->service->get($tenantId, $this->request->getGet('from'), $this->request->getGet('to'), $this->branchId());
        return $this->render('admin/operations_report/index', [
            'pageTitle' => lang('App.menu_operations_report'),
            'report' => $report,
            'branches' => $tenantId ? $this->branchModel->getByTenant($tenantId) : [],
            'branchId' => $this->branchId(),
        ]);
    }

    public function csv()
    {
        $tenantId = (int) current_tenant_id();
        $report = $this->service->get($tenantId, $this->request->getGet('from'), $this->request->getGet('to'), $this->branchId());
        $lines = ["Ngày,Chi nhánh,Số booking,Doanh thu,Đã thu"];
        foreach ($report['daily'] as $row) {
            $lines[] = implode(',', [
                $row['booking_date'],
                '"' . str_replace('"', '""', $report['branches'][(int) $row['branch_id']] ?? '') . '"',
                (int) $row['bookings'],
                (float) $row['revenue'],
                (float) $row['collected'],
            ]);
        }
        return $this->response->download('operations-report-' . $report['from'] . '-' . $report['to'] . '.csv', implode("\r\n", $lines));
    }

    private function branchId(): ?int
    {
        $value = (int) $this->request->getGet('branch_id');
        return $value > 0 ? $value : null;
    }
}
