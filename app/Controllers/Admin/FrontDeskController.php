<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\OperationsDashboardService;

class FrontDeskController extends BaseController
{
    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $branchId = (int) current_branch_id();
        $date = OperationsDashboardService::normalizeDate($this->request->getGet('date'));
        $operations = service('operationsDashboardService')->get($tenantId, $date, is_superadmin() ? null : ($branchId ?: null));
        $db = \Config\Database::connect();
        $query = $db->table('bookings b')->select('b.*, GROUP_CONCAT(CONCAT(c.code, " - ", c.name_vi) ORDER BY c.sort_order SEPARATOR ", ") AS court_names')
            ->join('booking_items bi', 'bi.booking_id = b.id AND bi.status = "active"', 'left')
            ->join('courts c', 'c.id = bi.court_id', 'left')->where('b.tenant_id', $tenantId)->where('b.booking_date', $date)->where('b.deleted_at', null)
            ->groupBy('b.id')->orderBy('b.start_time', 'ASC');
        if (! is_superadmin() && $branchId) $query->where('b.branch_id', $branchId);
        $this->viewData['pageTitle'] = 'Front Desk · Quầy vận hành';
        $this->viewData['date'] = $date;
        $this->viewData['operations'] = $operations;
        $this->viewData['bookings'] = $query->get()->getResult();
        return $this->render('admin/front_desk/index', $this->viewData);
    }
}
