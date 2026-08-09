<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TenantModel;

class TenantApi extends BaseController
{
    protected TenantModel $tenantModel;

    public function __construct()
    {
        $this->tenantModel = new TenantModel();
    }

    public function index()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? current_tenant_id() ?? 0);
        if ($tenantId <= 0) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        $tenants = $this->tenantModel->where('id', $tenantId)->where('deleted_at', null)->findAll();

        return service('apiResponseService')->success($tenants);
    }

    public function show(int $id)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? current_tenant_id() ?? 0);
        if ($tenantId <= 0) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }
        $tenant = $this->tenantModel->where('id', $id)->where('id', $tenantId)->where('deleted_at', null)->first();
        if (!$tenant) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($tenant);
    }
}
