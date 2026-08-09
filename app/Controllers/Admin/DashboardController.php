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

        $tenantId = current_tenant_id();

        $this->viewData['pageTitle'] = 'Dashboard';
        $this->viewData['totalTenants'] = $tenantModel->where('deleted_at', null)->countAllResults();
        $this->viewData['totalBranches'] = $branchModel->where('deleted_at', null)->countAllResults();
        $this->viewData['totalUsers'] = $userModel->where('deleted_at', null)->countAllResults();
        $this->viewData['recentActivities'] = $auditLogModel->orderBy('created_at', 'DESC')->limit(10)->findAll();

        return $this->render('admin/dashboard', $this->viewData);
    }
}
