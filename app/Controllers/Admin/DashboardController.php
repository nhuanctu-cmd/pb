<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TenantModel;
use App\Models\BranchModel;
use App\Models\UserModel;
use App\Models\AuditLogModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $tenantModel = new TenantModel();
        $branchModel = new BranchModel();
        $userModel = new UserModel();
        $auditLogModel = new AuditLogModel();
        $operationsDashboard = service('operationsDashboardService');

        $tenantId = current_tenant_id();
        $tenantId = $tenantId ? (int) $tenantId : 0;

        $this->viewData['pageTitle'] = 'Dashboard';
        // The admin UI is tenant-contextual. Never fall back to a global
        // aggregate when the current tenant is missing.
        $this->viewData['totalTenants'] = $tenantId > 0
            ? $tenantModel->where('id', $tenantId)->where('deleted_at', null)->countAllResults()
            : 0;
        $this->viewData['totalBranches'] = $tenantId > 0
            ? $branchModel->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults()
            : 0;
        $this->viewData['totalUsers'] = $tenantId > 0
            ? $userModel->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults()
            : 0;
        $this->viewData['recentActivities'] = $tenantId > 0
            ? $auditLogModel->where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->limit(10)->findAll()
            : [];
        $this->viewData['operations'] = $tenantId > 0 ? $operationsDashboard->get($tenantId, $this->request->getGet('date')) : [];

        return $this->render('admin/dashboard', $this->viewData);
    }
}
