<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class AiSchedulingApi extends BaseController
{
    private $service;
    public function __construct() { $this->service = service('aiSchedulingService'); }
    public function index()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        if (!$tenantId) return service('apiResponseService')->validationError(['tenant_id' => 'Tenant không hợp lệ.']);
        return service('apiResponseService')->success($this->service->requests($tenantId));
    }
    public function store()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0); $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $result = $this->service->create($input, $tenantId, (int) ($this->request->api_user_id ?? 0));
        return !empty($result['success']) ? service('apiResponseService')->created($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể tạo yêu cầu.');
    }
    public function run(int $id)
    {
        $result = $this->service->run($id, (int) ($this->request->api_tenant_id ?? 0), (int) ($this->request->api_user_id ?? 0));
        return !empty($result['success']) ? service('apiResponseService')->success($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể chạy scheduling.');
    }
}
