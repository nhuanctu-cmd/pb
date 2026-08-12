<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\UnifiedMatchService;

class UnifiedMatchApi extends BaseController
{
    private UnifiedMatchService $service;

    public function __construct()
    {
        $this->service = new UnifiedMatchService();
    }

    public function show(int $matchId)
    {
        $data = $this->service->get($matchId, $this->tenantId());
        return $data
            ? service('apiResponseService')->success($data)
            : service('apiResponseService')->notFound('Trận đấu không tồn tại.');
    }

    public function create()
    {
        $result = $this->service->create($this->payload(), $this->tenantId(), $this->userId());
        return ! empty($result['success'])
            ? service('apiResponseService')->created($result)
            : service('apiResponseService')->error($result['message'] ?? 'Không thể tạo trận đấu.');
    }

    public function submit(int $matchId)
    {
        $result = $this->service->submitResult($matchId, $this->payload(), $this->tenantId(), $this->userId());
        return ! empty($result['success'])
            ? service('apiResponseService')->updated($result)
            : service('apiResponseService')->error($result['message'] ?? 'Không thể lưu kết quả.');
    }

    public function confirm(int $matchId)
    {
        $result = $this->service->confirmResult($matchId, $this->tenantId(), $this->userId());
        return ! empty($result['success'])
            ? service('apiResponseService')->updated($result)
            : service('apiResponseService')->error($result['message'] ?? 'Không thể xác nhận kết quả.');
    }

    public function official(int $matchId)
    {
        $result = $this->service->publishOfficial($matchId, $this->tenantId(), $this->userId());
        return ! empty($result['success'])
            ? service('apiResponseService')->updated($result)
            : service('apiResponseService')->error($result['message'] ?? 'Không thể công bố kết quả.');
    }

    private function payload(): array
    {
        return $this->request->getJSON(true) ?: $this->request->getPost();
    }

    private function tenantId(): ?int
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? current_tenant_id());
        return $tenantId > 0 ? $tenantId : null;
    }

    private function userId(): ?int
    {
        $userId = (int) ($this->request->api_user_id ?? 0);
        return $userId > 0 ? $userId : null;
    }
}
