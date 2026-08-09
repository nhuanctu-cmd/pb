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
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return $this->tenantRequired();
        }

        $facility = $this->facilityModel->findForTenant($id, $tenantId);
        if (!$facility) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($facility);
    }

    public function dashboard(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId || !$this->facilityModel->findForTenant($id, $tenantId)) {
            return $this->tenantOrNotFound($tenantId);
        }

        $dashboard = $this->facilityService->getFacilityDashboard($id);
        if (empty($dashboard)) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($dashboard);
    }

    public function branches(int $facilityId)
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return $this->tenantRequired();
        }
        if (!$this->facilityModel->findForTenant($facilityId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($this->branchModel->getByFacilityForTenant($facilityId, $tenantId));
    }

    public function branchDetail(int $branchId)
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return $this->tenantRequired();
        }
        if (!$this->branchModel->findForTenant($branchId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        $branch = $this->facilityService->getBranchById($branchId);
        if (!$branch) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($branch);
    }

    public function branchOpeningHours(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        return service('apiResponseService')->success($this->facilityService->getBranchOpeningHours($branchId));
    }

    public function branchHolidays(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
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
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        return service('apiResponseService')->success($this->facilityService->getCourtsByBranch($branchId, $this->request->getGet()));
    }

    public function courtDetail(int $courtId)
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return $this->tenantRequired();
        }
        if (!$this->courtModel->findForTenant($courtId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

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
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        return service('apiResponseService')->success($this->facilityService->getRealtimeCourtStatus($branchId));
    }

    public function activeSessions(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        return service('apiResponseService')->success($this->facilityService->getActiveSessions($branchId));
    }

    public function courtTimeline(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        return service('apiResponseService')->success($this->facilityService->getCourtTimeline($branchId, $date));
    }

    public function devices(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        return service('apiResponseService')->success($this->facilityService->getDevicesByBranch($branchId, $this->request->getGet()));
    }

    public function deviceDetail(int $deviceId)
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return $this->tenantRequired();
        }
        $device = model('App\Models\CourtDeviceModel')->findForTenant($deviceId, $tenantId);
        if (!$device) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($device);
    }

    public function deviceLogs(int $deviceId)
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId || !model('App\Models\CourtDeviceModel')->findForTenant($deviceId, $tenantId)) {
            return $this->tenantOrNotFound($tenantId);
        }
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        return service('apiResponseService')->success($this->facilityService->getDeviceLogs($deviceId, $limit));
    }

    public function toggleDevice(int $deviceId)
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId || !model('App\Models\CourtDeviceModel')->findForTenant($deviceId, $tenantId)) {
            return $this->tenantOrNotFound($tenantId);
        }
        return $this->facilityService->toggleDevice($deviceId)
            ? service('apiResponseService')->updated(null)
            : service('apiResponseService')->notFound();
    }

    public function report(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        $range = $this->validatedDateRange();
        if (!$range) {
            return service('apiResponseService')->validationError(['date' => 'Khoảng ngày không hợp lệ']);
        }
        [$from, $to] = $range;
        return service('apiResponseService')->success($this->facilityService->getBranchReport($branchId, $from, $to));
    }

    public function peakHours(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        $range = $this->validatedDateRange();
        if (!$range) {
            return service('apiResponseService')->validationError(['date' => 'Khoảng ngày không hợp lệ']);
        }
        [$from, $to] = $range;
        return service('apiResponseService')->success($this->facilityService->getPeakHours($branchId, $from, $to));
    }

    public function courtRanking(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        $range = $this->validatedDateRange();
        if (!$range) {
            return service('apiResponseService')->validationError(['date' => 'Khoảng ngày không hợp lệ']);
        }
        [$from, $to] = $range;
        return service('apiResponseService')->success($this->facilityService->getCourtRanking($branchId, $from, $to));
    }

    public function revenueByCourt(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        $range = $this->validatedDateRange();
        if (!$range) {
            return service('apiResponseService')->validationError(['date' => 'Khoảng ngày không hợp lệ']);
        }
        [$from, $to] = $range;
        return service('apiResponseService')->success($this->facilityService->getRevenueByCourt($branchId, $from, $to));
    }

    public function utilization(int $branchId)
    {
        if (!$this->authorizeBranch($branchId)) {
            return service('apiResponseService')->notFound();
        }
        $date = $this->validDate($this->request->getGet('date') ?? date('Y-m-d'));
        if (!$date) {
            return service('apiResponseService')->validationError(['date' => 'Ngày không hợp lệ']);
        }
        return service('apiResponseService')->success($this->facilityService->getUtilizationStats($branchId, $date));
    }

    private function resolveTenantId(): ?int
    {
        $tenantId = $this->request->getGet('tenant_id') ?? $this->request->api_tenant_id ?? current_tenant_id();
        return $tenantId ? (int) $tenantId : null;
    }

    private function authorizeBranch(int $branchId): bool
    {
        $tenantId = $this->resolveTenantId();
        return $tenantId !== null && $this->branchModel->findForTenant($branchId, $tenantId) !== null;
    }

    private function tenantRequired()
    {
        return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
    }

    private function tenantOrNotFound(?int $tenantId)
    {
        return $tenantId ? service('apiResponseService')->notFound() : $this->tenantRequired();
    }

    private function validatedDateRange(): ?array
    {
        $from = $this->validDate($this->request->getGet('from') ?? date('Y-m-01'));
        $to = $this->validDate($this->request->getGet('to') ?? date('Y-m-d'));
        if (!$from || !$to || $from > $to) {
            return null;
        }

        return [$from, $to];
    }

    private function validDate(string $date): ?string
    {
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
    }
}
