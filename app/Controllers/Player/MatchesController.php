<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\MatchRequestModel;
use App\Models\PlayerModel;
use App\Models\SocialMatchModel;
use App\Models\SocialMatchPlayerModel;

class MatchesController extends BaseController
{
    protected MatchRequestModel $matchRequestModel;
    protected SocialMatchModel $socialMatchModel;

    public function __construct()
    {
        $this->matchRequestModel = model(MatchRequestModel::class);
        $this->socialMatchModel = model(SocialMatchModel::class);
    }

    public function index()
    {
        $tenantId = (int) session('tenant_id');

        return view('player/matches/index', [
            'requests' => $this->matchRequestModel->getOpen($tenantId, ['status' => 'open']),
            'matches' => $this->socialMatchModel->getByTenant($tenantId, ['status' => 'confirmed']),
        ]);
    }

    public function create()
    {
        return view('player/matches/form', [
            'branches' => model(BranchModel::class)->getByTenant((int) session('tenant_id')),
        ]);
    }

    public function store()
    {
        $result = service('matchingService')->createMatchRequest([
            'tenant_id' => (int) session('tenant_id'),
            'player_id' => $this->currentPlayerId(),
            'branch_id' => (int) $this->request->getPost('branch_id'),
            'preferred_date' => $this->request->getPost('preferred_date'),
            'preferred_start_time' => $this->request->getPost('preferred_start_time'),
            'preferred_end_time' => $this->request->getPost('preferred_end_time'),
            'level_from' => (int) $this->request->getPost('level_from'),
            'level_to' => (int) $this->request->getPost('level_to'),
            'match_type' => $this->request->getPost('match_type'),
            'need_players' => (int) $this->request->getPost('need_players'),
        ]);

        return $result['success']
            ? redirect()->to('/player/matches')->with('success', $result['message'])
            : redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function show($id)
    {
        $tenantId = (int) session('tenant_id');
        $request = $tenantId ? $this->matchRequestModel->findForTenant((int) $id, $tenantId) : null;
        if (! $request) {
            return redirect()->to('/player/matches')->with('error', 'Không tìm thấy kèo.');
        }

        return view('player/matches/show', [
            'request' => $request,
            'suggestedPlayers' => service('matchingService')->findCompatiblePlayers((int) $id),
        ]);
    }

    public function join($id)
    {
        $result = service('matchingService')->confirmSocialMatch((int) $id, [$this->currentPlayerId()]);

        return redirect()->to('/player/matches')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function confirm($id)
    {
        $playerIds = array_filter(array_map('intval', (array) $this->request->getPost('player_ids')));
        $result = service('matchingService')->confirmSocialMatch((int) $id, $playerIds);

        return redirect()->to('/player/matches')->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function currentPlayerId(): int
    {
        $tenantId = (int) session('tenant_id');
        $userId = (int) session('user_id');
        $player = model(PlayerModel::class)->findPlayerByUser($userId, $tenantId);

        return (int) ($player->id ?? $userId);
    }
}
