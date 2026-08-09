<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\PlayerModel;

class CompetitionsController extends BaseController
{
    private $service;

    public function __construct()
    {
        $this->service = service('competitionService');
    }

    public function index()
    {
        $tenantId = (int) session('tenant_id');
        $events = array_values(array_filter($tenantId ? $this->service->events($tenantId) : [], static fn (object $event): bool => in_array($event->status, ['open', 'running', 'completed'], true)));
        $selected = (int) ($this->request->getGet('event_id') ?: ($events[0]->id ?? 0));
        $event = null;
        foreach ($events as $candidate) if ((int) $candidate->id === $selected) $event = $candidate;
        $playerId = $this->currentPlayerId();
        return view('player/competitions/index', ['events' => $events, 'event' => $event, 'standings' => $event ? $this->service->standings($selected, $tenantId) : [], 'fixtures' => $event ? $this->service->fixtures($selected, $tenantId) : [], 'ladderChallenges' => $event ? $this->service->ladderChallenges($selected, $tenantId) : [], 'myParticipant' => $event ? $this->service->participantForPlayer($selected, $playerId, $tenantId) : null, 'playerId' => $playerId]);
    }

    public function ladderRespond(int $challengeId)
    {
        $result = $this->service->respondLadderChallenge($challengeId, (string) $this->request->getPost('decision') === 'accept', (int) session('tenant_id'), (int) session('user_id'), $this->currentPlayerId());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    public function payEntry(int $participantId)
    {
        $result = $this->service->payEntryFee($participantId, $this->currentPlayerId(), (int) session('tenant_id'), (int) session('user_id'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    private function currentPlayerId(): int
    {
        $player = model(PlayerModel::class)->findPlayerByUser((int) session('user_id'), (int) session('tenant_id'));
        return (int) ($player->id ?? 0);
    }
}
