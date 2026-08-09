<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\PlayerModel;

class OpenPlayController extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = service('openPlayService');
    }

    public function index()
    {
        $tenantId = (int) session('tenant_id');
        $playerId = $this->currentPlayerId();
        $sessions = $tenantId ? $this->service->list($tenantId, ['session_date' => $this->request->getGet('date') ?: date('Y-m-d')]) : [];
        $participants = $entries = [];
        foreach ($sessions as $session) {
            $participants[(int) $session->id] = $this->service->players((int) $session->id, $tenantId);
            $entries[(int) $session->id] = $this->service->entryForPlayer((int) $session->id, $playerId, $tenantId);
        }
        return view('player/open_play/index', compact('sessions', 'participants', 'entries'));
    }

    public function create()
    {
        return view('player/open_play/form', ['branches' => model(BranchModel::class)->getByTenant((int) session('tenant_id'))]);
    }

    public function store()
    {
        $data = $this->request->getPost();
        $data['host_player_id'] = $this->currentPlayerId();
        $result = $this->service->create($data, (int) session('tenant_id'), (int) session('user_id'));
        return $result['success'] ? redirect()->to('/player/open-play')->with('success', 'Đã tạo Open Play.') : redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function join(int $id)
    {
        $result = $this->service->requestJoin($id, $this->currentPlayerId(), (int) session('tenant_id'), (int) session('user_id'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    public function leave(int $id)
    {
        $entry = $this->service->entryForPlayer($id, $this->currentPlayerId(), (int) session('tenant_id'));
        $result = $entry ? $this->service->leave((int) $entry->id, (int) session('tenant_id'), (int) session('user_id')) : ['success' => false, 'message' => 'Bạn chưa tham gia session.'];
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    private function currentPlayerId(): int
    {
        $player = model(PlayerModel::class)->findPlayerByUser((int) session('user_id'), (int) session('tenant_id'));
        return (int) ($player->id ?? 0);
    }
}
