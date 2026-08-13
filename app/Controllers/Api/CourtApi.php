<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\CourtService;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\CourtTypeModel;
use CodeIgniter\Database\BaseConnection;

class CourtApi extends BaseController
{
    protected CourtService $courtService;
    protected BranchModel $branchModel;
    protected CourtModel $courtModel;
    protected CourtTypeModel $courtTypeModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->courtService = new CourtService();
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
        $this->courtTypeModel = new CourtTypeModel();
        $this->db = db_connect();
    }

    public function index()
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 30)));
        $offset = ($page - 1) * $limit;
        $sortBy = (string) ($this->request->getGet('sort_by') ?? 'sort_order');
        $allowedSort = ['code', 'name_vi', 'name_en', 'status', 'sort_order', 'created_at', 'updated_at', 'branch_id'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'sort_order';
        }
        $sortDir = strtoupper((string) ($this->request->getGet('sort_dir') ?? 'ASC'));
        $sortDir = $sortDir === 'DESC' ? 'DESC' : 'ASC';
        $format = strtolower((string) ($this->request->getGet('format') ?? 'json'));

        $branchId = (int) ($this->request->getGet('branch_id') ?? 0);
        $facilityId = (int) ($this->request->getGet('facility_id') ?? 0);
        $courtTypeId = (int) ($this->request->getGet('court_type_id') ?? 0);
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $search = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));

        $builder = $this->db->table('courts c')
            ->select('c.id, c.tenant_id, c.facility_id, c.branch_id, c.code, c.name_vi, c.name_en, c.floor, c.area, c.is_indoor, c.has_light, c.has_fan, c.has_camera, c.status, c.sort_order, c.created_at, c.updated_at, b.name AS branch_name, ct.name_vi AS court_type_name_vi, cl.name_vi AS club_name_vi')
            ->join('branches b', 'b.id = c.branch_id', 'left')
            ->join('court_types ct', 'ct.id = c.court_type_id', 'left')
            ->join('clubs cl', 'cl.id = c.club_id AND cl.tenant_id = c.tenant_id', 'left')
            ->where('c.tenant_id', $tenantId)
            ->where('c.deleted_at', null);

        if ($branchId > 0) {
            if (!$this->branchModel->findForTenant($branchId, $tenantId)) {
                return service('apiResponseService')->notFound('Branch not found');
            }
            $builder->where('c.branch_id', $branchId);
        }
        if ($facilityId > 0) {
            $builder->where('b.facility_id', $facilityId);
        }
        if ($courtTypeId > 0) {
            $builder->where('c.court_type_id', $courtTypeId);
        }
        if ($status !== '') {
            $builder->where('c.status', $status);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('c.code', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->orLike('c.name_vi', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->orLike('c.name_en', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->groupEnd();
        }

        $total = $this->countBuilder($builder);
        $rows = $builder
            ->orderBy($sortBy === 'branch_id' ? 'c.branch_id' : 'c.' . $sortBy, $sortDir)
            ->limit($limit, $offset)
            ->get()
            ->getResult();

        if ($format === 'csv') {
            return $this->exportCsv($rows, [
                'ID',
                'Mã sân',
                'Tên VI',
                'Tên EN',
                'Chi nhánh',
                'Loại sân',
                'CLB vận hành',
                'Indoor',
                'Đèn',
                'Máy quạt',
                'Camera',
                'Tầng',
                'Diện tích',
                'Trạng thái',
                'Sort',
            ], function ($row) {
                return [
                    $row->id,
                    $row->code,
                    $row->name_vi,
                    $row->name_en,
                    $row->branch_name,
                    $row->court_type_name_vi,
                    $row->club_name_vi,
                    $row->is_indoor ? '1' : '0',
                    $row->has_light ? '1' : '0',
                    $row->has_fan ? '1' : '0',
                    $row->has_camera ? '1' : '0',
                    $row->floor,
                    $row->area,
                    $row->status,
                    $row->sort_order,
                ];
            }, 'courts');
        }

        service('apiResponseService')->setMeta([
            'tenant_id' => $tenantId,
            'filters' => [
                'branch_id' => $branchId,
                'facility_id' => $facilityId,
                'court_type_id' => $courtTypeId,
                'status' => $status,
                'q' => $search,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
        service('apiResponseService')->setPagination($total, $limit, $page);
        return service('apiResponseService')->success($rows, 'Danh sách sân.');
    }

    public function show($id = null)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        $court = $this->courtModel->findForTenant((int) $id, $tenantId);
        if (!$court) {
            return service('apiResponseService')->notFound('Court not found');
        }

        return service('apiResponseService')->success([
            'court'       => $court,
            'images'      => $this->courtService->getCourtImages((int) $id),
            'maintenance' => $this->courtService->getActiveMaintenanceByCourt((int) $id),
        ], 'Chi tiết sân.');
    }

    public function getByBranch($branchId)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId || !$this->branchModel->findForTenant((int) $branchId, $tenantId)) {
            return $tenantId ? service('apiResponseService')->notFound('Branch not found') : service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 30)));
        $offset = ($page - 1) * $limit;
        $sortBy = (string) ($this->request->getGet('sort_by') ?? 'sort_order');
        $allowedSort = ['code', 'name_vi', 'status', 'sort_order', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'sort_order';
        }
        $sortDir = strtoupper((string) ($this->request->getGet('sort_dir') ?? 'ASC'));
        $sortDir = $sortDir === 'DESC' ? 'DESC' : 'ASC';
        $format = strtolower((string) ($this->request->getGet('format') ?? 'json'));
        $courtTypeId = (int) ($this->request->getGet('court_type_id') ?? 0);
        $status = trim((string) ($this->request->getGet('status') ?? ''));

        $builder = $this->db->table('courts c')
            ->select('c.id, c.code, c.name_vi, c.name_en, c.floor, c.is_indoor, c.has_light, c.has_fan, c.has_camera, c.status, c.sort_order, c.created_at, c.updated_at')
            ->where('c.tenant_id', $tenantId)
            ->where('c.branch_id', (int) $branchId)
            ->where('c.deleted_at', null);

        if ($courtTypeId > 0) {
            $builder->where('c.court_type_id', $courtTypeId);
        }
        if ($status !== '') {
            $builder->where('c.status', $status);
        }

        $total = $this->countBuilder($builder);
        $rows = $builder
            ->orderBy('c.' . $sortBy, $sortDir)
            ->limit($limit, $offset)
            ->get()
            ->getResult();

        if ($format === 'csv') {
            return $this->exportCsv($rows, [
                'ID',
                'Mã sân',
                'Tên VI',
                'Tên EN',
                'Tầng',
                'Indoor',
                'Có đèn',
                'Có quạt',
                'Có camera',
                'Trạng thái',
                'Sort',
            ], function ($row) {
                return [
                    $row->id,
                    $row->code,
                    $row->name_vi,
                    $row->name_en,
                    $row->floor,
                    $row->is_indoor ? '1' : '0',
                    $row->has_light ? '1' : '0',
                    $row->has_fan ? '1' : '0',
                    $row->has_camera ? '1' : '0',
                    $row->status,
                    $row->sort_order,
                ];
            }, 'courts');
        }

        service('apiResponseService')->setPagination($total, $limit, $page);
        return service('apiResponseService')->success($rows, 'Danh sách sân của chi nhánh.');
    }

    public function availability($id = null)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        $court = $this->courtModel->findForTenant((int) $id, $tenantId);
        if (! $court) {
            return service('apiResponseService')->notFound('Court not found');
        }

        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $startTime = $this->request->getGet('start_time');
        $endTime = $this->request->getGet('end_time');

        $bookingItems = $this->db->table('booking_items bi')
            ->select('bi.booking_id, bi.start_time, bi.end_time, b.booking_date, b.status, b.customer_name')
            ->join('bookings b', 'b.id = bi.booking_id')
            ->where('bi.court_id', (int) $id)
            ->where('bi.deleted_at', null)
            ->where('b.tenant_id', $tenantId)
            ->where('b.booking_date', $date)
            ->whereNotIn('b.status', ['cancelled', 'refunded', 'expired'])
            ->where('bi.status', 'active')
            ->orderBy('bi.start_time', 'ASC')
            ->get()
            ->getResult();

        $isAvailable = true;
        if ($startTime && $endTime) {
            $conflict = $this->db->table('booking_items bi')
                ->join('bookings b', 'b.id = bi.booking_id')
                ->where('bi.court_id', (int) $id)
                ->where('b.tenant_id', $tenantId)
                ->where('b.booking_date', $date)
                ->whereNotIn('b.status', ['cancelled', 'refunded', 'expired'])
                ->where('bi.status', 'active')
                ->groupStart()
                    ->where('bi.start_time <', $endTime)
                    ->where('bi.end_time >', $startTime)
                ->groupEnd()
                ->countAllResults();
            $isAvailable = $conflict === 0;
        }

        return service('apiResponseService')->success([
            'court' => $court,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_available' => (bool) $isAvailable,
            'bookings' => $bookingItems,
        ], 'Kiểm tra tính khả dụng sân.');
    }

    public function available()
    {
        $branchId = (int) ($this->request->getGet('branch_id') ?? 0);
        $date = $this->request->getGet('date');
        $startTime = $this->request->getGet('start_time');
        $endTime = $this->request->getGet('end_time');

        if (! $branchId) {
            return service('apiResponseService')->validationError(['branch_id' => 'branch_id là bắt buộc']);
        }

        $tenantId = $this->resolveTenantId();
        if (!$tenantId || !$this->branchModel->findForTenant($branchId, $tenantId)) {
            return $tenantId ? service('apiResponseService')->notFound('Branch not found') : service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $courts = $this->courtService->getAvailableCourts(
            $branchId,
            $date,
            $startTime,
            $endTime
        );

        return service('apiResponseService')->success([
            'date' => $date,
            'branch_id' => $branchId,
            'items' => $courts,
            'count' => count($courts),
        ]);
    }

    public function courtTypes()
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $types = $this->courtService->getActiveCourtTypes($tenantId);
        return service('apiResponseService')->success($types, 'Danh sách loại sân.');
    }

    public function create()
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $payload = $this->payload();
        $payload['tenant_id'] = $tenantId;
        $payload['created_by'] = $this->request->api_user_id ?? user_id() ?? null;
        $payload['is_indoor'] = (int) ($payload['is_indoor'] ?? 0);

        if (! $this->branchModel->findForTenant((int) $payload['branch_id'], $tenantId)) {
            return service('apiResponseService')->validationError(['branch_id' => 'branch_id không hợp lệ']);
        }
        if (! $this->courtTypeModel->find((int) $payload['court_type_id'])) {
            return service('apiResponseService')->validationError(['court_type_id' => 'court_type_id không hợp lệ']);
        }
        if (! $this->isCourtCodeUnique((string) $payload['code'], (int) $payload['branch_id'], $tenantId)) {
            return service('apiResponseService')->validationError(['code' => 'Mã sân đã tồn tại tại chi nhánh này']);
        }

        $courtId = $this->courtService->createCourt($payload);
        if (! $courtId) {
            return service('apiResponseService')->validationError($this->courtModel->errors() ?: ['general' => 'Không thể tạo sân.']);
        }

        return service('apiResponseService')->created($this->courtModel->find((int) $courtId), 'Đã tạo sân.');
    }

    public function update($id = null)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $court = $this->courtModel->findForTenant((int) $id, $tenantId);
        if (! $court) {
            return service('apiResponseService')->notFound('Court not found');
        }

        $payload = $this->payload(true);
        if (! $payload) {
            return service('apiResponseService')->validationError(['payload' => 'Không có dữ liệu cập nhật']);
        }
        if (isset($payload['branch_id']) && ! $this->branchModel->findForTenant((int) $payload['branch_id'], $tenantId)) {
            return service('apiResponseService')->validationError(['branch_id' => 'branch_id không hợp lệ']);
        }
        if (isset($payload['court_type_id']) && ! $this->courtTypeModel->find((int) $payload['court_type_id'])) {
            return service('apiResponseService')->validationError(['court_type_id' => 'court_type_id không hợp lệ']);
        }
        if (isset($payload['code']) && ! $this->isCourtCodeUnique((string) $payload['code'], (int) $court->branch_id, $tenantId, $id)) {
            return service('apiResponseService')->validationError(['code' => 'Mã sân đã tồn tại tại chi nhánh này']);
        }

        $payload['updated_by'] = $this->request->api_user_id ?? user_id() ?? null;
        $ok = $this->courtService->updateCourt((int) $id, $payload);
        return $ok
            ? service('apiResponseService')->updated($this->courtModel->findForTenant((int) $id, $tenantId), 'Đã cập nhật sân.')
            : service('apiResponseService')->validationError(['general' => 'Không thể cập nhật sân.']);
    }

    public function delete($id = null)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        $court = $this->courtModel->findForTenant((int) $id, $tenantId);
        if (! $court) {
            return service('apiResponseService')->notFound('Court not found');
        }

        $ok = $this->courtService->deleteCourt((int) $id);
        return $ok
            ? service('apiResponseService')->deleted('Đã xoá sân.')
            : service('apiResponseService')->error('Không thể xoá sân vì còn booking hoạt động.');
    }

    public function courtStatuses()
    {
        $tenantId = $this->resolveTenantId();
        if (!$tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        return service('apiResponseService')->success($this->courtService->getActiveCourtStatuses($tenantId), 'Trạng thái sân.');
    }

    public function availableStatuses()
    {
        return $this->courtStatuses();
    }

    private function payload(bool $allowMissing = false): array
    {
        $raw = $this->request->getJSON(true);
        if (! is_array($raw)) {
            $raw = [];
        }

        $data = [
            'branch_id'     => (int) ($this->request->getPost('branch_id') ?: ($raw['branch_id'] ?? 0)),
            'court_type_id' => (int) ($this->request->getPost('court_type_id') ?: ($raw['court_type_id'] ?? 0)),
            'code'          => trim((string) ($this->request->getPost('code') ?: ($raw['code'] ?? ''))),
            'name_vi'       => trim((string) ($this->request->getPost('name_vi') ?: ($raw['name_vi'] ?? ''))),
            'name_en'       => trim((string) ($this->request->getPost('name_en') ?: ($raw['name_en'] ?? ''))),
            'floor'         => (int) ($this->request->getPost('floor') ?: ($raw['floor'] ?? 1)),
            'area'          => $this->request->getPost('area') ?: ($raw['area'] ?? null),
            'is_indoor'     => (int) ($this->request->getPost('is_indoor') ?: ($raw['is_indoor'] ?? 0)),
            'has_light'     => (int) ($this->request->getPost('has_light') ?: ($raw['has_light'] ?? 0)),
            'has_fan'       => (int) ($this->request->getPost('has_fan') ?: ($raw['has_fan'] ?? 0)),
            'has_camera'    => (int) ($this->request->getPost('has_camera') ?: ($raw['has_camera'] ?? 0)),
            'status'        => trim((string) ($this->request->getPost('status') ?: ($raw['status'] ?? 'available'))),
            'sort_order'    => (int) ($this->request->getPost('sort_order') ?: ($raw['sort_order'] ?? 0)),
            'club_id'       => $this->request->getPost('club_id') ? (int) $this->request->getPost('club_id') : ($raw['club_id'] ?? null),
        ];

        if (! $allowMissing) {
            return $data;
        }

        return array_filter($data, static fn($value) => $value !== null && $value !== '');
    }

    private function isCourtCodeUnique(string $code, int $branchId, int $tenantId, ?int $excludeId = null): bool
    {
        $builder = $this->courtModel
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('code', $code)
            ->where('deleted_at', null);
        if ($excludeId !== null && $excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() === 0;
    }

    private function getTenantId(): ?int
    {
        $tenantId = $this->request->getGet('tenant_id') ?? $this->request->api_tenant_id ?? session('tenant_id');
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

    private function resolveTenantId(): ?int
    {
        return $this->getTenantId();
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
}
