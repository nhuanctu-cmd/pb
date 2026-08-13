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
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(200, max(1, (int) ($this->request->getGet('limit') ?? 40)));
        $offset = ($page - 1) * $limit;
        $sortBy = (string) ($this->request->getGet('sort_by') ?? 'sort_order');
        $allowedSort = ['code', 'name_vi', 'name_en', 'status', 'city', 'district', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'sort_order';
        }
        $sortDir = strtoupper((string) ($this->request->getGet('sort_dir') ?? 'ASC'));
        $sortDir = $sortDir === 'DESC' ? 'DESC' : 'ASC';
        $format = strtolower((string) ($this->request->getGet('format') ?? 'json'));

        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $search = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
        $city = trim((string) ($this->request->getGet('city') ?? ''));
        $district = trim((string) ($this->request->getGet('district') ?? ''));

        $builder = $this->facilityModel->db->table('facilities f')
            ->where('f.tenant_id', $tenantId)
            ->where('f.deleted_at', null);

        if ($search !== '') {
            $builder->groupStart()
                ->like('f.code', '%' . $this->facilityModel->db->escapeLikeString($search) . '%', 'both', null, true)
                ->orLike('f.name_vi', '%' . $this->facilityModel->db->escapeLikeString($search) . '%', 'both', null, true)
                ->orLike('f.name_en', '%' . $this->facilityModel->db->escapeLikeString($search) . '%', 'both', null, true)
                ->groupEnd();
        }
        if ($status !== '') {
            $builder->where('f.status', $status);
        }
        if ($city !== '') {
            $builder->where('f.city', $city);
        }
        if ($district !== '') {
            $builder->where('f.district', $district);
        }

        $total = $this->countBuilder($builder);
        $rows = $builder
            ->orderBy('f.' . $sortBy, $sortDir)
            ->limit($limit, $offset)
            ->get()
            ->getResult();

        if ($format === 'csv') {
            return $this->exportCsv($rows, [
                'Facility ID',
                'Code',
                'Tên VI',
                'Tên EN',
                'Địa chỉ',
                'Thành phố',
                'Quận/Huyện',
                'Phone',
                'Email',
                'Website',
                'Tình trạng',
                'Trạng thái',
            ], function ($row) {
                return [
                    $row->id,
                    $row->code,
                    $row->name_vi,
                    $row->name_en,
                    $row->address,
                    $row->city,
                    $row->district,
                    $row->phone,
                    $row->email,
                    $row->website,
                    $row->timezone,
                    $row->status,
                ];
            }, 'facilities');
        }

        service('apiResponseService')->setMeta([
            'tenant_id' => $tenantId,
            'filters' => [
                'status' => $status,
                'q' => $search,
                'city' => $city,
                'district' => $district,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
        service('apiResponseService')->setPagination($total, $limit, $page);
        return service('apiResponseService')->success($rows, 'Danh sách cụm sân.');
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

    public function create()
    {
        $tenantId = $this->resolveTenantIdWithPost();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $data = $this->facilityPayload();
        $data['tenant_id'] = $tenantId;
        if (empty($data['code']) || empty($data['name_vi'])) {
            return service('apiResponseService')->validationError(['payload' => 'code và name_vi là bắt buộc']);
        }

        $facilityId = $this->facilityService->createFacility($data);
        return $facilityId
            ? service('apiResponseService')->created($this->facilityModel->findForTenant($facilityId, $tenantId), 'Đã tạo cụm sân.')
            : service('apiResponseService')->error('Không thể tạo cụm sân.');
    }

    public function update(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        if (! $this->facilityModel->findForTenant($id, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        $payload = $this->facilityPayload();
        if (empty(array_filter($payload, fn($value) => $value !== null && $value !== ''))) {
            return service('apiResponseService')->validationError(['payload' => 'Không có dữ liệu cập nhật']);
        }

        $ok = $this->facilityService->updateFacility($id, $payload);
        return $ok
            ? service('apiResponseService')->updated($this->facilityModel->findForTenant($id, $tenantId), 'Đã cập nhật cụm sân.')
            : service('apiResponseService')->error('Không thể cập nhật cụm sân.');
    }

    public function delete(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $facility = $this->facilityModel->findForTenant($id, $tenantId);
        if (! $facility) {
            return service('apiResponseService')->notFound();
        }

        if (! $this->facilityService->deleteFacility($id)) {
            return service('apiResponseService')->error('Không thể xoá cụm sân.');
        }
        return service('apiResponseService')->deleted('Đã xoá cụm sân.');
    }

    public function assignClub(int $facilityId)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        if (! $this->facilityModel->findForTenant($facilityId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        $clubId = (int) ($this->request->getPost('club_id') ?? 0);
        if ($clubId <= 0) {
            return service('apiResponseService')->validationError(['club_id' => 'club_id là bắt buộc']);
        }

        $result = $this->facilityService->assignClubToFacility(
            $facilityId,
            $clubId,
            $tenantId,
            (int) ($this->request->api_user_id ?? user_id() ?? 0),
            (bool) ($this->request->getPost('is_primary') ?? false),
            trim((string) ($this->request->getPost('notes') ?? '')),
            $this->trimOrNull($this->request->getPost('start_date')),
            $this->trimOrNull($this->request->getPost('end_date')),
            $this->parseDecimalOrNull($this->request->getPost('revenue_share')),
            (int) ($this->request->getPost('booking_priority') ?? 0),
            $this->request->getPost('allowed_courts'),
            $this->request->getPost('allowed_hours')
        );
        return $result['success']
            ? service('apiResponseService')->updated(null, $result['message'])
            : service('apiResponseService')->error($result['message']);
    }

    public function removeClubAssignment(int $facilityId, int $assignmentId)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        if (! $this->facilityModel->findForTenant($facilityId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }

        $ok = $this->facilityService->removeClubFromFacility($assignmentId, $tenantId, (int) ($this->request->api_user_id ?? user_id() ?? 0));
        return $ok ? service('apiResponseService')->deleted('Đã ngưng gán CLB khỏi cụm sân.') : service('apiResponseService')->error('Không thể ngưng gán CLB.');
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

    public function clubs(int $facilityId)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId || ! $this->facilityModel->findForTenant($facilityId, $tenantId)) {
            return service('apiResponseService')->notFound();
        }
        return service('apiResponseService')->success($this->facilityService->getFacilityClubs($facilityId, $tenantId, true));
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
        if (! $tenantId) {
            return null;
        }
        $tenantId = (int) $tenantId;
        $tokenTenant = (int) ($this->request->api_tenant_id ?? 0);
        if ($tokenTenant > 0 && $tenantId !== $tokenTenant) {
            return null;
        }
        return $tenantId;
    }

    private function countBuilder($builder): int
    {
        $clone = clone $builder;
        return (int) $clone->countAllResults();
    }

    private function exportCsv(array $rows, array $headers, callable $mapRow, string $filename): object
    {
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($fh, (array) $mapRow($row), ';');
        }
        rewind($fh);
        $csv = stream_get_contents($fh) ?: '';
        fclose($fh);

        return service('response')
            ->setStatusCode(200)
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename=' . $filename . '-' . date('YmdHis') . '.csv')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function resolveTenantIdWithPost(): ?int
    {
        $tenantId = $this->request->getPost('tenant_id')
            ?? $this->request->getGet('tenant_id')
            ?? $this->request->api_tenant_id
            ?? current_tenant_id();
        if (! $tenantId) {
            return null;
        }

        $tenantId = (int) $tenantId;
        $tokenTenant = (int) ($this->request->api_tenant_id ?? 0);
        if ($tokenTenant > 0 && $tenantId !== $tokenTenant) {
            return null;
        }
        return $tenantId;
    }

    private function facilityPayload(): array
    {
        $raw = $this->request->getJSON(true);
        if (! is_array($raw)) {
            $raw = [];
        }

        return [
            'code'           => trim((string) ($this->request->getPost('code') ?: ($raw['code'] ?? ''))),
            'name_vi'        => trim((string) ($this->request->getPost('name_vi') ?: ($raw['name_vi'] ?? ''))),
            'name_en'        => trim((string) ($this->request->getPost('name_en') ?: ($raw['name_en'] ?? ''))),
            'address'        => trim((string) ($this->request->getPost('address') ?: ($raw['address'] ?? ''))),
            'city'           => trim((string) ($this->request->getPost('city') ?: ($raw['city'] ?? ''))),
            'district'       => trim((string) ($this->request->getPost('district') ?: ($raw['district'] ?? ''))),
            'phone'          => trim((string) ($this->request->getPost('phone') ?: ($raw['phone'] ?? ''))),
            'email'          => trim((string) ($this->request->getPost('email') ?: ($raw['email'] ?? ''))),
            'status'         => trim((string) ($this->request->getPost('status') ?: ($raw['status'] ?? 'active'))),
            'is_active'      => ((string) ($this->request->getPost('status') ?: ($raw['status'] ?? 'active')) === 'active') ? 1 : 0,
            'description_vi'  => trim((string) (
                $this->request->getPost('description_vi')
                ?: ($raw['description_vi'] ?? ($this->request->getPost('description') ?: ($raw['description'] ?? '')))
            )),
            'description_en'  => trim((string) ($this->request->getPost('description_en') ?: ($raw['description_en'] ?? ''))),
        ];
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

    private function trimOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function parseDecimalOrNull($value): ?float
    {
        $raw = $this->trimOrNull($value);
        if ($raw === null) {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }
        $float = (float) $raw;
        return $float < 0 ? 0 : $float;
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
