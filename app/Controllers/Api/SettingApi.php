<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class SettingApi extends BaseController
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    public function index()
    {
        $tenantId = $this->request->getGet('tenant_id') ?? $this->request->api_tenant_id ?? current_tenant_id();
        $branchId = $this->request->getGet('branch_id');

        $builder = $this->settingModel->where('deleted_at', null)->where('is_active', 1);
        if ($tenantId) {
            $builder->where('tenant_id', (int) $tenantId);
        }
        if ($branchId) {
            $builder->where('branch_id', (int) $branchId);
        }

        return service('apiResponseService')->success($builder->orderBy('group', 'ASC')->orderBy('key', 'ASC')->findAll());
    }
}
