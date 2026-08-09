<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Services\CourtService;
use App\Models\BranchModel;
use App\Models\CourtModel;

class CourtApi extends ResourceController
{
    protected CourtService $courtService;
    protected BranchModel $branchModel;
    protected CourtModel $courtModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->courtService = new CourtService();
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
    }

    public function index()
    {
        $tenantId = $this->request->api_tenant_id ?? session('tenant_id');
        if (!$tenantId) {
            return $this->failUnauthorized(lang('App.unauthorized'));
        }

        $branchId = $this->request->getGet('branch_id');
        $courtTypeId = $this->request->getGet('court_type_id');
        $status = $this->request->getGet('status');

        if ($branchId) {
            if (!$this->branchModel->findForTenant((int) $branchId, (int) $tenantId)) {
                return $this->failNotFound('Branch not found');
            }
            $filters = [];
            if ($courtTypeId) $filters['court_type_id'] = $courtTypeId;
            if ($status) $filters['status'] = $status;

            $courts = $this->courtService->getCourtGridByBranch((int) $branchId, $filters);
            return $this->respond([
                'success' => true,
                'data'    => $courts,
            ]);
        }

        // Return all courts for tenant
        $courtModel = new \App\Models\CourtModel();
        $courts = $courtModel->getByTenant($tenantId);

        return $this->respond([
            'success' => true,
            'data'    => $courts,
        ]);
    }

    public function show($id = null)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? session('tenant_id'));
        if (!$tenantId) {
            return $this->failUnauthorized(lang('App.unauthorized'));
        }
        $court = $this->courtModel->findForTenant((int) $id, $tenantId);
        if (!$court) {
            return $this->failNotFound('Court not found');
        }

        $images = $this->courtService->getCourtImages((int) $id);
        $maintenance = $this->courtService->getActiveMaintenanceByCourt((int) $id);

        return $this->respond([
            'success' => true,
            'data'    => [
                'court'       => $court,
                'images'      => $images,
                'maintenance' => $maintenance,
            ],
        ]);
    }

    public function getByBranch($branchId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? session('tenant_id'));
        if (!$tenantId || !$this->branchModel->findForTenant((int) $branchId, $tenantId)) {
            return $tenantId ? $this->failNotFound('Branch not found') : $this->failUnauthorized(lang('App.unauthorized'));
        }
        $filters = [];
        $courtTypeId = $this->request->getGet('court_type_id');
        $status = $this->request->getGet('status');

        if ($courtTypeId) $filters['court_type_id'] = $courtTypeId;
        if ($status) $filters['status'] = $status;

        $courts = $this->courtService->getCourtGridByBranch((int) $branchId, $filters);

        return $this->respond([
            'success' => true,
            'data'    => $courts,
        ]);
    }

    public function available()
    {
        $branchId = $this->request->getGet('branch_id');
        $date = $this->request->getGet('date');
        $startTime = $this->request->getGet('start_time');
        $endTime = $this->request->getGet('end_time');

        if (!$branchId) {
            return $this->failValidationError('branch_id là bắt buộc');
        }

        $tenantId = (int) ($this->request->api_tenant_id ?? session('tenant_id'));
        if (!$tenantId || !$this->branchModel->findForTenant((int) $branchId, $tenantId)) {
            return $tenantId ? $this->failNotFound('Branch not found') : $this->failUnauthorized(lang('App.unauthorized'));
        }

        $courts = $this->courtService->getAvailableCourts(
            (int) $branchId,
            $date,
            $startTime,
            $endTime
        );

        return $this->respond([
            'success' => true,
            'data'    => $courts,
            'count'   => count($courts),
        ]);
    }

    public function courtTypes()
    {
        $tenantId = $this->request->api_tenant_id ?? session('tenant_id');
        if (!$tenantId) {
            return $this->failUnauthorized(lang('App.unauthorized'));
        }

        $types = $this->courtService->getActiveCourtTypes($tenantId);

        return $this->respond([
            'success' => true,
            'data'    => $types,
        ]);
    }
}
