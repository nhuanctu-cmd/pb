<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\PlayerService;
use App\Services\MembershipService;
use App\Services\WalletService;
use App\Services\PlayerRatingService;

class PlayersController extends BaseController
{
    protected PlayerService $playerService;
    protected MembershipService $membershipService;
    protected WalletService $walletService;
    protected PlayerRatingService $ratingService;

    public function __construct()
    {
        $this->playerService     = new PlayerService();
        $this->membershipService = new MembershipService();
        $this->walletService     = new WalletService();
        $this->ratingService     = new PlayerRatingService();
    }

    public function dashboard()
    {
        $tenantId = current_tenant_id();

        return $this->render('admin/players/dashboard', [
            'pageTitle' => 'Player Dashboard',
            'dashboard' => $this->playerService->getDashboard($tenantId),
        ]);
    }

    public function index()
    {
        $tenantId = current_tenant_id();
        $filters  = $this->request->getGet();

        $this->viewData['pageTitle'] = lang('App.players');
        $this->viewData['players']   = $this->playerService->getPlayers($tenantId, $filters);
        $this->viewData['filters']   = $filters;
        $this->viewData['pager']     = model(\App\Models\PlayerModel::class)->pager;
        $this->viewData['regions']   = model(\App\Models\PlayerModel::class)->getRegions($tenantId);
        $this->viewData['branches']  = model(\App\Models\BranchModel::class)->getByTenant($tenantId);

        return $this->render('admin/players/index', $this->viewData);
    }

    public function create()
    {
        $this->viewData['pageTitle'] = lang('App.create_player');
        $this->viewData['branches']  = model(\App\Models\BranchModel::class)->getByTenant(current_tenant_id());
        return $this->render('admin/players/form', $this->viewData);
    }

    public function store()
    {
        $tenantId = current_tenant_id();

        // SaaS: kiểm tra hạn mức ngườị chơi theo gói
        $limit = (new \App\Services\TenantPlanService())->checkLimit((int) $tenantId, 'players');
        if (! $limit['allowed']) {
            return redirect()->back()->withInput()
                ->with('error', lang('App.planLimitReached', [lang('App.plans_limit_players'), $limit['max']]));
        }

        $rules = [
            'full_name' => 'required|max_length[255]',
            'phone'     => 'permit_empty|max_length[20]',
            'email'     => 'permit_empty|valid_email|max_length[255]',
            'gender'    => 'permit_empty|in_list[male,female,other]',
            'level'     => 'permit_empty|in_list[beginner,intermediate,advanced,pro]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'tenant_id' => $tenantId,
            'full_name' => $this->request->getPost('full_name'),
            'phone'     => $this->request->getPost('phone'),
            'email'     => $this->request->getPost('email'),
            'gender'    => $this->request->getPost('gender') ?: 'other',
            'birthday'  => $this->request->getPost('birthday'),
            'region'    => $this->request->getPost('region'),
            'home_branch_id' => $this->request->getPost('home_branch_id') ?: null,
            'level'     => $this->request->getPost('level') ?: 'beginner',
            'rating_score' => 1000,
            'status'    => 'active',
            'created_by'=> user_id(),
        ];

        $playerId = $this->playerService->createPlayer($data);
        if (!$playerId) {
            return redirect()->back()->withInput()->with('error', lang('App.error'));
        }

        return redirect()->to('/admin/players')->with('success', lang('App.player_created'));
    }

    public function edit(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle'] = lang('App.edit_player');
        $this->viewData['player']    = $player;
        $this->viewData['memberships'] = $this->membershipService->getPlayerMemberships($id, (int) current_tenant_id());
        $this->viewData['transactions'] = $this->walletService->getPlayerTransactions($id, $player->tenant_id, 20);
        $this->viewData['branches']  = model(\App\Models\BranchModel::class)->getByTenant($player->tenant_id);

        return $this->render('admin/players/form', $this->viewData);
    }

    public function profile(int $id)
    {
        $player = $this->playerService->getProfile($id);
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        return $this->render('admin/players/profile', [
            'pageTitle' => 'Player Profile - ' . $player->full_name,
            'player'    => $player,
            'bookings'  => model(\App\Models\BookingModel::class)
                ->where('player_id', $id)
                ->where('deleted_at', null)
                ->orderBy('created_at', 'DESC')
                ->limit(10)
                ->findAll(),
        ]);
    }

    public function checkIn(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        if ($this->playerService->recordCheckIn($id, $player->tenant_id)) {
            return redirect()->to('/admin/players/profile/' . $id)->with('success', 'Check-in recorded successfully.');
        }

        return redirect()->back()->with('error', 'Unable to record check-in.');
    }

    public function update(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        $rules = [
            'full_name' => 'required|max_length[255]',
            'phone'     => 'permit_empty|max_length[20]',
            'email'     => 'permit_empty|valid_email|max_length[255]',
            'gender'    => 'permit_empty|in_list[male,female,other]',
            'level'     => 'permit_empty|in_list[beginner,intermediate,advanced,pro]',
            'status'    => 'permit_empty|in_list[active,inactive,banned]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'full_name'  => $this->request->getPost('full_name'),
            'phone'      => $this->request->getPost('phone'),
            'email'      => $this->request->getPost('email'),
            'gender'     => $this->request->getPost('gender'),
            'birthday'   => $this->request->getPost('birthday'),
            'region'     => $this->request->getPost('region'),
            'home_branch_id' => $this->request->getPost('home_branch_id') ?: null,
            'level'      => $this->request->getPost('level'),
            'status'     => $this->request->getPost('status'),
            'updated_by' => user_id(),
        ];

        if ($this->playerService->updatePlayer($id, $data)) {
            return redirect()->to('/admin/players')->with('success', lang('App.player_updated'));
        }

        return redirect()->back()->withInput()->with('error', lang('App.error'));
    }

    public function delete(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        $playerModel = model(\App\Models\PlayerModel::class);
        if ($playerModel->delete($id)) {
            return redirect()->to('/admin/players')->with('success', lang('App.player_deleted'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }

    public function wallet(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle']   = lang('App.wallet') . ' - ' . $player->full_name;
        $this->viewData['player']      = $player;
        $this->viewData['wallet']      = $this->walletService->getWallet($id, $player->tenant_id);
        $this->viewData['transactions'] = $this->walletService->getPlayerTransactions($id, $player->tenant_id);

        return $this->render('admin/players/wallet', $this->viewData);
    }

    public function topup(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        $amount = (float) $this->request->getPost('amount');
        $note   = $this->request->getPost('note');

        if ($amount <= 0) {
            return redirect()->back()->with('error', lang('App.invalid_amount'));
        }

        if ($this->walletService->topup($id, $player->tenant_id, $amount, $note, null, null, user_id())) {
            return redirect()->to('/admin/players/wallet/' . $id)->with('success', lang('App.topup_success'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }

    public function adjustWallet(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        $newBalance = (float) $this->request->getPost('balance');
        $note       = $this->request->getPost('note');

        if ($newBalance < 0) {
            return redirect()->back()->with('error', lang('App.invalid_amount'));
        }

        if ($this->walletService->adjust($id, $player->tenant_id, $newBalance, $note, user_id())) {
            return redirect()->to('/admin/players/wallet/' . $id)->with('success', lang('App.adjust_success'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }

    public function bookingHistory(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        $bookingModel = model(\App\Models\BookingModel::class);
        $bookings = $bookingModel->where('player_id', $id)
                                 ->where('deleted_at', null)
                                 ->orderBy('created_at', 'DESC')
                                 ->paginate(20);

        $this->viewData['pageTitle'] = lang('App.booking_history') . ' - ' . $player->full_name;
        $this->viewData['player']    = $player;
        $this->viewData['bookings']  = $bookings;
        $this->viewData['pager']     = $bookingModel->pager;

        return $this->render('admin/players/booking_history', $this->viewData);
    }

    public function ranking()
    {
        $tenantId = current_tenant_id();
        $scopeType = $this->request->getGet('scope_type') ?: 'global';
        $scopeId = $this->request->getGet('scope_id') ? (int) $this->request->getGet('scope_id') : null;
        $region = $this->request->getGet('region') ?: null;

        return $this->render('admin/players/ranking', [
            'pageTitle' => 'Player Ranking',
            'rankings'  => $this->ratingService->getRankings($tenantId, $scopeType, $scopeId, $region),
            'scopeType' => $scopeType,
            'scopeId'   => $scopeId,
            'region'    => $region,
            'regions'   => model(\App\Models\PlayerModel::class)->getRegions($tenantId),
            'branches'  => model(\App\Models\BranchModel::class)->getByTenant($tenantId),
        ]);
    }

    public function matchHistory(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        return $this->render('admin/players/match_history', [
            'pageTitle' => 'Match History - ' . $player->full_name,
            'player' => $player,
            'matches' => model(\App\Models\PlayerMatchHistoryModel::class)->getByPlayer($id, 100),
            'players' => model(\App\Models\PlayerModel::class)->where('tenant_id', $player->tenant_id)->where('id !=', $id)->findAll(),
            'branches' => model(\App\Models\BranchModel::class)->getByTenant($player->tenant_id),
        ]);
    }

    public function storeMatch(int $id)
    {
        $player = $this->playerService->getPlayerById($id, (int) current_tenant_id());
        if (!$player) {
            return redirect()->to('/admin/players')->with('error', lang('App.no_data'));
        }

        $rules = [
            'result' => 'required|in_list[win,loss,draw]',
            'match_date' => 'required|valid_date',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $matchId = $this->ratingService->recordMatch($player->tenant_id, $id, $this->request->getPost('opponent_player_id') ?: null, $this->request->getPost('result'), [
            'branch_id' => $this->request->getPost('branch_id') ?: null,
            'match_date' => $this->request->getPost('match_date'),
            'score' => $this->request->getPost('score'),
            'is_mvp' => (bool) $this->request->getPost('is_mvp'),
            'notes' => $this->request->getPost('notes'),
            'created_by' => user_id(),
        ]);

        if (! $matchId) {
            return redirect()->back()->withInput()->with('error', 'Không thể ghi match. Rating canonical yêu cầu đối thủ hợp lệ và kết quả phải qua Unified Match Graph.');
        }

        return redirect()->to('/admin/players/match-history/' . $id)->with('success', 'Match recorded successfully.');
    }
}
