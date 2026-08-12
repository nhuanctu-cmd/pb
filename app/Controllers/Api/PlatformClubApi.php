<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class PlatformClubApi extends BaseController
{
    public function index()
    {
        return service('apiResponseService')->success(service('platformClubService')->list($this->request->getGet()));
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $result = service('platformClubService')->create($data, (int) ($this->request->api_user_id ?? 0));
        return ! empty($result['success']) ? service('apiResponseService')->created($result) : service('apiResponseService')->error($result['message'] ?? 'Không tạo được club.');
    }

    public function link(int $platformClubId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $clubId = (int) ($data['club_id'] ?? 0);
        $result = service('platformClubService')->linkTenantClub($platformClubId, $tenantId, $clubId, (int) ($this->request->api_user_id ?? 0));
        return ! empty($result['success']) ? service('apiResponseService')->updated($result) : service('apiResponseService')->error($result['message'] ?? 'Không liên kết được club.');
    }
}
