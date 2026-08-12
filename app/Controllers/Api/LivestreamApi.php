<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class LivestreamApi extends BaseController
{
    public function index()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        if (! $tenantId) return service('apiResponseService')->validationError(['tenant_id' => 'Tenant không hợp lệ.']);
        return service('apiResponseService')->success(service('livestreamService')->publicChannels($tenantId));
    }
}
