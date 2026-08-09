<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\PlayerModel;

class SocialController extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = service('socialGraphService');
    }

    public function index()
    {
        $tenantId = (int) session('tenant_id');
        $playerId = $this->currentPlayerId();
        return view('player/social/index', [
            'following' => $this->service->following($tenantId, $playerId),
            'favorites' => $this->service->favorites($tenantId, $playerId),
        ]);
    }

    public function follow(int $id)
    {
        return $this->message($this->service->follow($this->currentPlayerId(), $id, (int) session('tenant_id'), (int) session('user_id')));
    }

    public function unfollow(int $id)
    {
        return $this->message($this->service->unfollow($this->currentPlayerId(), $id, (int) session('tenant_id'), (int) session('user_id')));
    }

    public function favorite()
    {
        return $this->message($this->service->favorite($this->currentPlayerId(), (string) $this->request->getPost('entity_type'), (int) $this->request->getPost('entity_id'), (int) session('tenant_id'), (int) session('user_id')));
    }

    public function unfavorite()
    {
        return $this->message($this->service->unfavorite($this->currentPlayerId(), (string) $this->request->getPost('entity_type'), (int) $this->request->getPost('entity_id'), (int) session('tenant_id')));
    }

    private function message(array $result)
    {
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    private function currentPlayerId(): int
    {
        $player = model(PlayerModel::class)->findPlayerByUser((int) session('user_id'), (int) session('tenant_id'));
        return (int) ($player->id ?? 0);
    }
}
