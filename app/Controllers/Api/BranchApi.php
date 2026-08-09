<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BranchModel;

class BranchApi extends BaseController
{
    protected BranchModel $branchModel;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
    }

    public function index()
    {
        $tenantId = (int) ($this->request->getGet('tenant_id') ?? $this->request->api_tenant_id ?? current_tenant_id() ?? 0);
        if ($tenantId <= 0) {
            return service('apiResponseService')->validationError(['tenant_id' => lang('Validation.required', ['field' => 'tenant_id'])]);
        }

        return service('apiResponseService')->success($this->branchModel->getByTenant($tenantId));
    }

    public function show(int $id)
    {
        $branch = $this->branchModel->find($id);
        if (!$branch) {
            return service('apiResponseService')->notFound();
        }

        return service('apiResponseService')->success($branch);
    }
}
