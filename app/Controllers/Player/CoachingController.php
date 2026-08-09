<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\PlayerModel;

class CoachingController extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = service('coachingService');
    }

    public function index()
    {
        $tenantId = (int) session('tenant_id');
        $playerId = $this->currentPlayerId();
        $date = $this->request->getGet('date') ?: date('Y-m-d');
        $sessions = $tenantId ? $this->service->sessions($tenantId, ['session_date' => $date]) : [];
        $entries = $players = [];
        foreach ($sessions as $session) {
            $entries[(int) $session->id] = $this->service->entryForPlayer((int) $session->id, $playerId, $tenantId);
            $players[(int) $session->id] = $this->service->players((int) $session->id, $tenantId);
        }
        return view('player/coaching/index', compact('sessions', 'entries', 'players', 'date'));
    }

    public function join(int $id)
    {
        $result = $this->service->requestJoin($id, $this->currentPlayerId(), (int) session('tenant_id'), (int) session('user_id'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    public function leave(int $id)
    {
        $tenantId = (int) session('tenant_id');
        $playerId = $this->currentPlayerId();
        $entry = $this->service->entryForPlayer($id, $playerId, $tenantId);
        $result = $entry ? $this->service->leave((int) $entry->id, $playerId, $tenantId, (int) session('user_id')) : ['success' => false, 'message' => 'Bạn chưa đăng ký session.'];
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    public function pay(int $id)
    {
        $result = $this->service->payInvoice($id, $this->currentPlayerId(), (int) session('tenant_id'), (int) session('user_id'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    private function currentPlayerId(): int
    {
        $player = model(PlayerModel::class)->findPlayerByUser((int) session('user_id'), (int) session('tenant_id'));
        return (int) ($player->id ?? 0);
    }
}
