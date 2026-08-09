<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PlayerModel;

class CompetitionApi extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = service('competitionService');
    }

    public function index()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        if ($tenantId <= 0) return service('apiResponseService')->validationError(['tenant_id' => 'Tenant không hợp lệ.']);
        return service('apiResponseService')->success($this->service->events($tenantId));
    }

    public function detail(int $eventId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        if ($tenantId <= 0) return service('apiResponseService')->validationError(['tenant_id' => 'Tenant không hợp lệ.']);
        $event = null;
        foreach ($this->service->events($tenantId) as $candidate) if ((int) $candidate->id === $eventId) $event = $candidate;
        if (!$event) return service('apiResponseService')->notFound('Competition không tồn tại.');
        return service('apiResponseService')->success([
            'event' => $event,
            'participants' => $this->service->participants($eventId, $tenantId),
            'standings' => $this->service->standings($eventId, $tenantId),
            'fixtures' => $this->service->fixtures($eventId, $tenantId),
            'ladder_challenges' => $this->service->ladderChallenges($eventId, $tenantId),
        ]);
    }

    public function ladderRespond(int $challengeId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        $player = (new PlayerModel())->findPlayerByUser((int) ($this->request->api_user_id ?? 0), $tenantId);
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $rawDecision = $input['accept'] ?? ($input['decision'] ?? false);
        $decision = is_bool($rawDecision)
            ? $rawDecision
            : in_array(strtolower(trim((string) $rawDecision)), ['1', 'true', 'yes', 'accept', 'accepted'], true);
        if (!$player) return service('apiResponseService')->forbidden('Tài khoản chưa có hồ sơ player.');
        $result = $this->service->respondLadderChallenge($challengeId, $decision, $tenantId, (int) ($this->request->api_user_id ?? 0), (int) $player->id);
        return !empty($result['success']) ? service('apiResponseService')->success($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể phản hồi challenge.');
    }

    public function payEntry(int $participantId)
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        $player = (new PlayerModel())->findPlayerByUser((int) ($this->request->api_user_id ?? 0), $tenantId);
        if (!$player) return service('apiResponseService')->forbidden('Tài khoản chưa có hồ sơ player.');
        $result = $this->service->payEntryFee($participantId, (int) $player->id, $tenantId, (int) ($this->request->api_user_id ?? 0));
        return !empty($result['success']) ? service('apiResponseService')->success($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể thanh toán.');
    }
}
