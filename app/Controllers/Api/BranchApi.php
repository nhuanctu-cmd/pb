<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\FacilityModel;
use CodeIgniter\Database\BaseConnection;

class BranchApi extends BaseController
{
    protected BranchModel $branchModel;
    protected FacilityModel $facilityModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
        $this->facilityModel = new FacilityModel();
        $this->db = db_connect();
    }

    public function index()
    {
        $tenantId = $this->resolveTenantId();
        if ($tenantId <= 0) {
            return service('apiResponseService')->validationError(['tenant_id' => lang('Validation.required', ['field' => 'tenant_id'])]);
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 30)));
        $offset = ($page - 1) * $limit;
        $sortBy = (string) ($this->request->getGet('sort_by') ?? 'name');
        $allowedSort = ['code', 'name', 'city', 'district', 'status', 'created_at', 'updated_at'];
        if (! in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'name';
        }
        $sortDir = strtoupper((string) ($this->request->getGet('sort_dir') ?? 'ASC'));
        $sortDir = $sortDir === 'DESC' ? 'DESC' : 'ASC';
        $format = strtolower((string) ($this->request->getGet('format') ?? 'json'));
        $status = trim((string) ($this->request->getGet('status') ?? ''));
        $search = trim((string) ($this->request->getGet('q') ?? $this->request->getGet('search') ?? ''));
        $facilityId = trim((string) ($this->request->getGet('facility_id') ?? ''));

        $builder = $this->db->table('branches b')
            ->select('b.id, b.facility_id, b.code, b.name, b.email, b.phone, b.address, b.city, b.district, b.branch_type, b.status')
            ->where('b.tenant_id', $tenantId)
            ->where('b.deleted_at', null);
        if ($status !== '') {
            $builder->where('b.status', $status);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('b.code', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->orLike('b.name', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->orLike('b.address', '%' . $this->db->escapeLikeString($search) . '%', 'both', null, true)
                ->groupEnd();
        }
        if ($facilityId !== '' && ctype_digit($facilityId)) {
            $builder->where('b.facility_id', (int) $facilityId);
        }
        $total = (clone $builder)->countAllResults();
        $rows = $builder
            ->orderBy('b.is_main', 'DESC')
            ->orderBy('b.' . $sortBy, $sortDir)
            ->limit($limit, $offset)
            ->get()
            ->getResult();

        if ($format === 'csv') {
            return $this->exportCsv($rows, [
                'ID',
                'Facility ID',
                'Code',
                'Tên chi nhánh',
                'Loại',
                'Email',
                'SĐT',
                'Địa chỉ',
                'Thành phố',
                'Quận/Huyện',
                'Trạng thái',
            ], function ($row) {
                return [
                    $row->id,
                    $row->facility_id,
                    $row->code,
                    $row->name,
                    $row->branch_type,
                    $row->email,
                    $row->phone,
                    $row->address,
                    $row->city,
                    $row->district,
                    $row->status,
                ];
            }, 'branches');
        }

        service('apiResponseService')->setMeta([
            'tenant_id' => $tenantId,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'facility_id' => $facilityId,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
        service('apiResponseService')->setPagination($total, $limit, $page);
        return service('apiResponseService')->success($rows, 'Danh sách chi nhánh.');
    }

    public function show(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if ($tenantId <= 0) {
            return service('apiResponseService')->validationError(['tenant_id' => lang('Validation.required', ['field' => 'tenant_id'])]);
        }

        $branch = $this->branchModel->findForTenant($id, $tenantId);
        if (!$branch) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($branch);
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

        if (! $payload['code'] || ! $payload['name']) {
            return service('apiResponseService')->validationError([
                'code' => 'code là bắt buộc',
                'name' => 'name là bắt buộc',
            ]);
        }
        if (! $this->isBranchCodeUnique((string) $payload['code'], $tenantId)) {
            return service('apiResponseService')->validationError(['code' => 'Mã chi nhánh đã tồn tại.']);
        }
        if (! empty($payload['facility_id']) && ! $this->facilityModel->findForTenant((int) $payload['facility_id'], $tenantId)) {
            return service('apiResponseService')->validationError(['facility_id' => 'Cụm sân không tồn tại.']);
        }

        $branchId = $this->branchModel->insert($payload);
        if (! $branchId) {
            return service('apiResponseService')->validationError($this->branchModel->errors() ?: ['general' => 'Không thể tạo chi nhánh.']);
        }

        return service('apiResponseService')->created($this->branchModel->find((int) $branchId), 'Đã tạo chi nhánh.');
    }

    public function update(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        $branch = $this->branchModel->findForTenant($id, $tenantId);
        if (! $branch) {
            return service('apiResponseService')->notFound();
        }

        $payload = $this->payload();
        if (! $payload) {
            return service('apiResponseService')->validationError(['payload' => 'Không có dữ liệu cập nhật']);
        }
        if (isset($payload['code']) && ! $this->isBranchCodeUnique((string) $payload['code'], $tenantId, $id)) {
            return service('apiResponseService')->validationError(['code' => 'Mã chi nhánh đã tồn tại.']);
        }
        if (! empty($payload['facility_id']) && ! $this->facilityModel->findForTenant((int) $payload['facility_id'], $tenantId)) {
            return service('apiResponseService')->validationError(['facility_id' => 'Cụm sân không tồn tại.']);
        }
        $payload['updated_by'] = $this->request->api_user_id ?? user_id() ?? null;

        $ok = $this->branchModel->update($id, $payload);
        return $ok
            ? service('apiResponseService')->updated($this->branchModel->findForTenant($id, $tenantId), 'Đã cập nhật chi nhánh.')
            : service('apiResponseService')->validationError($this->branchModel->errors() ?: ['general' => 'Không thể cập nhật chi nhánh.']);
    }

    public function delete(int $id)
    {
        $tenantId = $this->resolveTenantId();
        if (! $tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        $branch = $this->branchModel->findForTenant($id, $tenantId);
        if (! $branch) {
            return service('apiResponseService')->notFound();
        }

        $ok = $this->branchModel->delete($id);
        return $ok ? service('apiResponseService')->deleted('Đã xoá chi nhánh.') : service('apiResponseService')->error('Không thể xoá chi nhánh.');
    }

    private function resolveTenantId(): ?int
    {
        $tenantId = $this->request->getGet('tenant_id') ?? $this->request->getPost('tenant_id') ?? $this->request->api_tenant_id ?? current_tenant_id();
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

    private function payload(): array
    {
        $raw = $this->request->getJSON(true);
        if (! is_array($raw)) {
            $raw = [];
        }
        $facilityId = (int) ($this->request->getPost('facility_id') ?: ($raw['facility_id'] ?? 0));
        $status = (string) ($this->request->getPost('status') ?: ($raw['status'] ?? 'active'));

        return array_filter([
            'facility_id' => $facilityId > 0 ? $facilityId : null,
            'code' => trim((string) ($this->request->getPost('code') ?: ($raw['code'] ?? ''))),
            'branch_type' => trim((string) ($this->request->getPost('branch_type') ?: ($raw['branch_type'] ?? ''))),
            'name' => trim((string) ($this->request->getPost('name') ?: ($raw['name'] ?? ''))),
            'email' => trim((string) ($this->request->getPost('email') ?: ($raw['email'] ?? ''))),
            'phone' => trim((string) ($this->request->getPost('phone') ?: ($raw['phone'] ?? ''))),
            'address' => trim((string) ($this->request->getPost('address') ?: ($raw['address'] ?? ''))),
            'status' => trim($status),
            'city' => trim((string) ($this->request->getPost('city') ?: ($raw['city'] ?? ''))),
            'district' => trim((string) ($this->request->getPost('district') ?: ($raw['district'] ?? ''))),
            'is_active' => ($status === 'active') ? 1 : 0,
        ], static fn($value) => $value !== null && $value !== '');
    }

    private function isBranchCodeUnique(string $code, int $tenantId, ?int $excludeId = null): bool
    {
        $builder = $this->branchModel
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('deleted_at', null);
        if ($excludeId !== null && $excludeId > 0) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() === 0;
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
