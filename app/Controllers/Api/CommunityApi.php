<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PlayerModel;

class CommunityApi extends BaseController
{
    private $service;
    public function __construct() { $this->service = service('communityService'); }

    public function index()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        if (!$tenantId) return service('apiResponseService')->validationError(['tenant_id' => 'Tenant không hợp lệ.']);
        return service('apiResponseService')->success($this->service->feed($tenantId, (int) ($this->request->getGet('limit') ?: 30)));
    }

    public function store()
    {
        [$tenantId, $playerId] = $this->context(); $input = $this->request->getJSON(true) ?: $this->request->getPost();
        if (!$playerId) return service('apiResponseService')->forbidden('Tài khoản chưa có hồ sơ player.');
        $result = $this->service->createPost($playerId, $input, $tenantId, (int) ($this->request->api_user_id ?? 0));
        return !empty($result['success']) ? service('apiResponseService')->created($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể đăng bài.');
    }

    public function comment(int $postId)
    {
        [$tenantId, $playerId] = $this->context(); $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $result = $this->service->comment($postId, $playerId, (string) ($input['body'] ?? ''), $tenantId, (int) ($this->request->api_user_id ?? 0));
        return !empty($result['success']) ? service('apiResponseService')->created($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể bình luận.');
    }

    public function react(int $postId)
    {
        [$tenantId, $playerId] = $this->context(); $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $result = $this->service->react($postId, $playerId, (string) ($input['reaction'] ?? 'like'), $tenantId, (int) ($this->request->api_user_id ?? 0));
        return !empty($result['success']) ? service('apiResponseService')->success($result) : service('apiResponseService')->error($result['message'] ?? 'Không thể reaction.');
    }

    private function context(): array { $tenantId = (int) ($this->request->api_tenant_id ?? 0); $player = (new PlayerModel())->findPlayerByUser((int) ($this->request->api_user_id ?? 0), $tenantId); return [$tenantId, (int) ($player->id ?? 0)]; }
}
