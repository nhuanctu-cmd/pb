<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\ClubModel;
use App\Models\PlayerModel;
use App\Models\TeamMemberModel;
use App\Models\TeamModel;

class TeamsController extends BaseController
{
    protected TeamModel $teamModel;
    protected TeamMemberModel $teamMemberModel;

    public function __construct()
    {
        $this->teamModel = model(TeamModel::class);
        $this->teamMemberModel = model(TeamMemberModel::class);
    }

    public function index()
    {
        $tenantId = (int) session('tenant_id');
        $playerId = $this->currentPlayerId();

        return view('player/teams/index', [
            'teams' => $this->teamModel->getByTenant($tenantId, ['player_id' => $playerId]),
            'invites' => $this->teamMemberModel->select('team_members.*, teams.team_name')
                ->join('teams', 'teams.id = team_members.team_id')
                ->where('team_members.tenant_id', $tenantId)
                ->where('team_members.player_id', $playerId)
                ->where('team_members.status', 'invited')
                ->findAll(),
        ]);
    }

    public function create()
    {
        $tenantId = (int) session('tenant_id');
        return view('player/teams/form', [
            'clubs' => model(ClubModel::class)->getByTenant($tenantId, ['status' => 'active']),
        ]);
    }

    public function store()
    {
        $result = service('teamService')->createTeam([
            'tenant_id' => (int) session('tenant_id'),
            'club_id' => $this->request->getPost('club_id') ?: null,
            'team_name' => $this->request->getPost('team_name'),
            'captain_player_id' => $this->currentPlayerId(),
            'team_type' => $this->request->getPost('team_type') ?: 'group',
        ]);

        return $result['success']
            ? redirect()->to('/player/teams')->with('success', $result['message'])
            : redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function show($id)
    {
        $tenantId = (int) session('tenant_id');
        $team = $tenantId ? $this->teamModel->findForTenant((int) $id, $tenantId) : null;
        if (! $team) {
            return redirect()->to('/player/teams')->with('error', 'Không tìm thấy team.');
        }

        return view('player/teams/show', [
            'team' => $team,
            'members' => $this->teamMemberModel->getByTeam((int) $id, $tenantId),
            'players' => model(PlayerModel::class)->getByTenant($tenantId, ['status' => 'active']),
        ]);
    }

    public function invite($id)
    {
        $playerId = (int) $this->request->getPost('player_id');
        $result = service('teamService')->inviteMember((int) $id, $playerId, (int) session('tenant_id'));

        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function accept($id)
    {
        $result = service('teamService')->acceptInvite((int) $id, $this->currentPlayerId(), (int) session('tenant_id'));

        return redirect()->to('/player/teams')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function remove($id, $playerId)
    {
        $result = service('teamService')->removeMember((int) $id, (int) $playerId, (int) session('tenant_id'));

        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function currentPlayerId(): int
    {
        $tenantId = (int) session('tenant_id');
        $userId = (int) session('user_id');
        $player = model(PlayerModel::class)->findPlayerByUser($userId, $tenantId);

        return (int) ($player->id ?? $userId);
    }
}
