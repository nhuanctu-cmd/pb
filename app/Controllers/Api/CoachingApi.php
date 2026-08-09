<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PlayerModel;

class CoachingApi extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = service('coachingService');
    }

    public function index()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        if ($tenantId <= 0) return service('apiResponseService')->validationError(['tenant_id' => 'Tenant không hợp lệ.']);

        $date = (string) ($this->request->getGet('date') ?: date('Y-m-d'));
        $sessions = array_values(array_filter($this->service->sessions($tenantId, ['session_date' => $date]), static fn (object $session): bool => in_array($session->status, ['open', 'full'], true)));
        $playerId = $this->currentPlayerId($tenantId);
        $data = [];
        foreach ($sessions as $session) {
            $entry = $playerId ? $this->service->entryForPlayer((int) $session->id, $playerId, $tenantId) : null;
            $data[] = ['session' => $session, 'entry' => $entry];
        }
        return service('apiResponseService')->success($data);
    }

    public function join(int $sessionId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        $playerId = $this->currentPlayerId($tenantId);
        if (!$tenantId || !$playerId) return service('apiResponseService')->forbidden('Tài khoản chưa có hồ sơ player.');
        $result = $this->service->requestJoin($sessionId, $playerId, $tenantId, (int) ($this->request->api_user_id ?? 0));
        return !empty($result['success']) ? service('apiResponseService')->success($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể đăng ký.');
    }

    public function pay(int $entryId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        $playerId = $this->currentPlayerId($tenantId);
        if (!$tenantId || !$playerId) return service('apiResponseService')->forbidden('Tài khoản chưa có hồ sơ player.');
        $result = $this->service->payInvoice($entryId, $playerId, $tenantId, (int) ($this->request->api_user_id ?? 0));
        return !empty($result['success']) ? service('apiResponseService')->success($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể thanh toán.');
    }

    private function currentPlayerId(int $tenantId): int
    {
        $player = (new PlayerModel())->findPlayerByUser((int) ($this->request->api_user_id ?? 0), $tenantId);
        return (int) ($player->id ?? 0);
    }
}
