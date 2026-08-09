<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\PlayerModel;
use App\Models\TeamModel;

class CompetitionsController extends BaseController
{
    private $service;
    private BranchModel $branchModel;
    private TeamModel $teamModel;
    private PlayerModel $playerModel;

    public function __construct()
    {
        $this->service = service('competitionService');
        $this->branchModel = new BranchModel();
        $this->teamModel = new TeamModel();
        $this->playerModel = new PlayerModel();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $events = $tenantId ? $this->service->events($tenantId) : [];
        $selected = (int) ($this->request->getGet('event_id') ?: ($events[0]->id ?? 0));
        $event = null; $participants = $fixtures = $standings = $checkins = $ladderChallenges = [];
        foreach ($events as $candidate) if ((int) $candidate->id === $selected) $event = $candidate;
        if ($event) {
            $participants = $this->service->participants($selected, $tenantId);
            $fixtures = $this->service->fixtures($selected, $tenantId);
            $standings = $this->service->standings($selected, $tenantId);
            $checkins = $this->service->checkins($selected, $tenantId);
            $ladderChallenges = $this->service->ladderChallenges($selected, $tenantId);
        }
        return $this->render('admin/competitions/index', ['pageTitle' => 'Competition', 'events' => $events, 'event' => $event, 'participants' => $participants, 'fixtures' => $fixtures, 'standings' => $standings, 'checkins' => $checkins, 'ladderChallenges' => $ladderChallenges, 'branches' => $tenantId ? $this->branchModel->getByTenant($tenantId) : [], 'teams' => $tenantId ? $this->teamModel->getByTenant($tenantId, ['status' => 'active']) : [], 'players' => $tenantId ? $this->playerModel->getByTenant($tenantId, ['status' => 'active']) : []]);
    }

    public function storeEvent()
    {
        $result = $this->service->createEvent($this->request->getPost(), (int) current_tenant_id(), (int) user_id());
        return $this->message($result);
    }

    public function addParticipant(int $eventId)
    {
        $result = $this->service->addParticipant($eventId, $this->request->getPost(), (int) current_tenant_id());
        return $this->message($result, '/admin/competitions?event_id=' . $eventId);
    }

    public function updateEntryFee(int $eventId)
    {
        $result = $this->service->updateEntryFee($eventId, (float) $this->request->getPost('entry_fee'), (int) current_tenant_id(), (int) user_id());
        return $this->message($result, '/admin/competitions?event_id=' . $eventId);
    }

    public function generate(int $eventId)
    {
        return $this->message($this->service->generateRoundRobin($eventId, (int) current_tenant_id(), (int) user_id()), '/admin/competitions?event_id=' . $eventId);
    }

    public function result(int $fixtureId)
    {
        $result = $this->service->recordResult($fixtureId, (int) $this->request->getPost('score_a'), (int) $this->request->getPost('score_b'), (int) current_tenant_id(), (int) user_id());
        return $this->message($result, '/admin/competitions?event_id=' . (int) $this->request->getPost('event_id'));
    }

    public function checkin(int $participantId)
    {
        $result = $this->service->checkIn($participantId, (string) $this->request->getPost('status'), (int) current_tenant_id(), (int) user_id());
        return $this->message($result, '/admin/competitions?event_id=' . (int) $this->request->getPost('event_id'));
    }

    public function ladderChallenge(int $eventId)
    {
        $result = $this->service->createLadderChallenge($eventId, (int) $this->request->getPost('challenger_id'), (int) $this->request->getPost('opponent_id'), $this->request->getPost(), (int) current_tenant_id(), (int) user_id());
        return $this->message($result, '/admin/competitions?event_id=' . $eventId);
    }

    public function ladderResult(int $challengeId)
    {
        $result = $this->service->recordLadderResult($challengeId, (int) $this->request->getPost('score_a'), (int) $this->request->getPost('score_b'), (int) current_tenant_id(), (int) user_id());
        return $this->message($result, '/admin/competitions?event_id=' . (int) $this->request->getPost('event_id'));
    }

    public function ladderRespond(int $challengeId)
    {
        $result = $this->service->respondLadderChallenge($challengeId, (string) $this->request->getPost('decision') === 'accept', (int) current_tenant_id(), (int) user_id());
        return $this->message($result);
    }

    private function message(array $result, ?string $url = null)
    {
        return redirect()->to($url ?: '/admin/competitions')->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }
}
