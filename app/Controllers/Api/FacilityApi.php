<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\FacilityModel;
use App\Services\FacilityService;

class FacilityApi extends BaseController
{
    protected FacilityService $facilityService;
    protected FacilityModel $facilityModel;
    protected BranchModel $branchModel;
    protected CourtModel $courtModel;

    public function __construct()
    {
        $this->facilityService = new FacilityService();
        $this->facilityModel = new FacilityModel();
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
    }

    public function index()
    {
        $tenantId = (int) ($this->request->getGet('tenant_id') ?? $this->request->api_tenant_id ?? current_tenant_id() ?? 0);
        if ($tenantId <= 0) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        return service('apiResponseService')->success($this->facilityService->getAllFacilities($tenantId, $this->request->getGet()));
    }

    public function show(int $id)
    {
        $facility = $this->facilityService->getFacilityById($id);
        if (!$facility) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($facility);
    }

    public function dashboard(int $id)
    {
        $dashboard = $this->facilityService->getFacilityDashboard($id);
        if (empty($dashboard)) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($dashboard);
    }

    public function branches(int $facilityId)
    {
        $facility = $this->facilityModel->find($facilityId);
        if (!$facility) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($this->facilityService->getBranchesByFacility($facilityId));
    }

    public function branchDetail(int $branchId)
    {
        $branch = $this->facilityService->getBranchById($branchId);
        if (!$branch) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($branch);
    }

    public function branchOpeningHours(int $branchId)
    {
        return service('apiResponseService')->success($this->facilityService->getBranchOpeningHours($branchId));
    }

    public function branchHolidays(int $branchId)
    {
        $year = $this->request->getGet('year');
        return service('apiResponseService')->success($this->facilityService->getBranchHolidays($branchId, $year ? (int) $year : null));
    }

    public function courtTypes()
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        return service('apiResponseService')->success($this->facilityService->getActiveCourtTypes($tenantId));
    }

    public function courtStatuses()
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        return service('apiResponseService')->success($this->facilityService->getActiveCourtStatuses($tenantId));
    }

    public function courts(int $branchId)
    {
        return service('apiResponseService')->success($this->facilityService->getCourtsByBranch($branchId, $this->request->getGet()));
    }

    public function courtDetail(int $courtId)
    {
        $court = $this->facilityService->getCourtById($courtId);
        if (!$court) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success([
            'court'       => $court,
            'images'      => $this->facilityService->getCourtImages($courtId),
            'maintenance' => $this->facilityService->getMaintenanceByCourt($courtId),
            'devices'     => $this->facilityService->getDevicesByCourt($courtId),
        ]);
    }

    public function realtimeStatus(int $branchId)
    {
        return service('apiResponseService')->success($this->facilityService->getRealtimeCourtStatus($branchId));
    }

    public function activeSessions(int $branchId)
    {
        return service('apiResponseService')->success($this->facilityService->getActiveSessions($branchId));
    }

    public function courtTimeline(int $branchId)
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        return service('apiResponseService')->success($this->facilityService->getCourtTimeline($branchId, $date));
    }

    public function devices(int $branchId)
    {
        return service('apiResponseService')->success($this->facilityService->getDevicesByBranch($branchId, $this->request->getGet()));
    }

    public function deviceDetail(int $deviceId)
    {
        $device = model('App\Models\CourtDeviceModel')->find($deviceId);
        if (!$device) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($device);
    }

    public function deviceLogs(int $deviceId)
    {
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        return service('apiResponseService')->success($this->facilityService->getDeviceLogs($deviceId, $limit));
    }

    public function toggleDevice(int $deviceId)
    {
        return $this->facilityService->toggleDevice($deviceId)
            ? service('apiResponseService')->updated(null)
            : service('apiResponseService')->notFound();
    }

    public function report(int $branchId)
    {
        $from = $this->request->getGet('from') ?? date('Y-m-01');
        $to = $this->request->getGet('to') ?? date('Y-m-d');
        return service('apiResponseService')->success($this->facilityService->getBranchReport($branchId, $from, $to));
    }

    public function peakHours(int $branchId)
    {
        $from = $this->request->getGet('from') ?? date('Y-m-01');
        $to = $this->request->getGet('to') ?? date('Y-m-d');
        return service('apiResponseService')->success($this->facilityService->getPeakHours($branchId, $from, $to));
    }

    public function courtRanking(int $branchId)
    {
        $from = $this->request->getGet('from') ?? date('Y-m-01');
        $to = $this->request->getGet('to') ?? date('Y-m-d');
        return service('apiResponseService')->success($this->facilityService->getCourtRanking($branchId, $from, $to));
    }

    public function revenueByCourt(int $branchId)
    {
        $from = $this->request->getGet('from') ?? date('Y-m-01');
        $to = $this->request->getGet('to') ?? date('Y-m-d');
        return service('apiResponseService')->success($this->facilityService->getRevenueByCourt($branchId, $from, $to));
    }

    public function utilization(int $branchId)
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        return service('apiResponseService')->success($this->facilityService->getUtilizationStats($branchId, $date));
    }

    private function resolveTenantId(): ?int
    {
        $tenantId = $this->request->getGet('tenant_id') ?? $this->request->api_tenant_id ?? current_tenant_id();
        return $tenantId ? (int) $tenantId : null;
    }
}
