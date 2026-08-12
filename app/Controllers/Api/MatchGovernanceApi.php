<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class MatchGovernanceApi extends BaseController
{
    public function dispute(int $matchId)
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $result = service('matchGovernanceService')->open(
            $matchId,
            (int) ($this->request->api_tenant_id ?? 0),
            (int) ($this->request->api_user_id ?? 0),
            $data
        );
        return ! empty($result['success']) ? service('apiResponseService')->created($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể mở dispute.');
    }

    public function resolve(int $disputeId)
    {
        $data = $this->request->getJSON(true) ?: $this->request->getPost();
        $result = service('matchGovernanceService')->resolve(
            $disputeId,
            (int) ($this->request->api_tenant_id ?? 0),
            (int) ($this->request->api_user_id ?? 0),
            (string) ($data['status'] ?? ''),
            trim((string) ($data['resolution'] ?? ''))
        );
        return ! empty($result['success']) ? service('apiResponseService')->updated($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể xử lý dispute.');
    }
}
