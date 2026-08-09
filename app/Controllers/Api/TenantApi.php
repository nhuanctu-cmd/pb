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
        $tenants = $this->tenantModel->where('deleted_at', null)
            ->orderBy('name', 'ASC')
            ->findAll();

        return service('apiResponseService')->success($tenants);
    }

    public function show(int $id)
    {
        $tenant = $this->tenantModel->find($id);
        if (!$tenant) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($tenant);
    }
}
