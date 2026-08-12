<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class TournamentMatchNetworkApi extends BaseController
{
    public function sync(int $tournamentMatchId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        if ($tenantId <= 0) return service('apiResponseService')->validationError(['tenant_id' => 'Tenant không hợp lệ.']);
        $result = service('tournamentMatchNetworkAdapter')->sync($tournamentMatchId, $tenantId, (int) ($this->request->api_user_id ?? 0));
        return ! empty($result['success'])
            ? service('apiResponseService')->success($result)
            : service('apiResponseService')->error($result['message'] ?? 'Không thể đồng bộ tournament match.');
    }

    public function official(int $tournamentMatchId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        if ($tenantId <= 0) return service('apiResponseService')->validationError(['tenant_id' => 'Tenant không hợp lệ.']);
        $result = service('tournamentMatchNetworkAdapter')->publishOfficial($tournamentMatchId, $tenantId, (int) ($this->request->api_user_id ?? 0));
        return ! empty($result['success'])
            ? service('apiResponseService')->updated($result)
            : service('apiResponseService')->error($result['message'] ?? 'Không thể publish kết quả tournament.');
    }
}
