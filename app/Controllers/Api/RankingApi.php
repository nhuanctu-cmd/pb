<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class RankingApi extends BaseController
{
    public function leaderboard()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        $authority = (string) ($this->request->getGet('authority') ?: 'national-pickleball');
        $limit = (int) ($this->request->getGet('limit') ?: 50);
        $rows = service('rankingNetworkService')->leaderboard($authority, $tenantId > 0 ? $tenantId : null, $limit);
        return service('apiResponseService')->success([
            'authority' => $authority,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'items' => $rows,
        ]);
    }
}
