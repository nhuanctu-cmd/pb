<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Services\PlayerService;
use App\Services\MembershipService;

class ProfileController extends BaseController
{
    protected PlayerService $playerService;
    protected MembershipService $membershipService;

    public function __construct()
    {
        $this->playerService     = new PlayerService();
        $this->membershipService = new MembershipService();
    }

    public function index()
    {
        $userId   = user_id();
        $tenantId = current_tenant_id();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return redirect()->to('/player')->with('error', lang('App.no_data'));
        }

        $profile = $this->playerService->getProfile($player->id);

        return view('player/profile/index', [
            'player'     => $profile,
            'membership' => $profile->active_membership ?? null,
            'wallet'     => $profile->wallet ?? null,
            'stats'      => $profile->statistics ?? null,
        ]);
    }

    public function update()
    {
        $userId   = user_id();
        $tenantId = current_tenant_id();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return redirect()->to('/player')->with('error', lang('App.no_data'));
        }

        $rules = [
            'full_name' => 'required|max_length[255]',
            'phone'     => 'permit_empty|max_length[20]',
            'gender'    => 'permit_empty|in_list[male,female,other]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'full_name' => $this->request->getPost('full_name'),
            'phone'     => $this->request->getPost('phone'),
            'gender'    => $this->request->getPost('gender'),
            'birthday'  => $this->request->getPost('birthday'),
        ];

        if ($this->playerService->updatePlayer($player->id, $data)) {
            return redirect()->to('/player/profile')->with('success', lang('App.profile_updated'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }

    public function membership()
    {
        $userId   = user_id();
        $tenantId = current_tenant_id();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return redirect()->to('/player')->with('error', lang('App.no_data'));
        }

        $memberships = $this->membershipService->getPlayerMemberships($player->id);
        $packages    = $this->membershipService->getPackages($tenantId);
        $active      = $this->membershipService->getActiveMembership($player->id, $tenantId);

        return view('player/profile/membership', [
            'player'      => $player,
            'memberships' => $memberships,
            'packages'    => $packages,
            'active'      => $active,
        ]);
    }

    public function buyPackage()
    {
        $userId   = user_id();
        $tenantId = current_tenant_id();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return redirect()->to('/player')->with('error', lang('App.no_data'));
        }

        $packageId = (int) $this->request->getPost('package_id');
        if (!$packageId) {
            return redirect()->back()->with('error', lang('App.invalid_data'));
        }

        $membershipId = $this->membershipService->buyPackage($player->id, $packageId, $tenantId);
        if (!$membershipId) {
            return redirect()->back()->with('error', lang('App.error'));
        }

        return redirect()->to('/player/profile/membership')->with('success', lang('App.membership_created'));
    }

    public function cancelMembership(int $id)
    {
        $userId   = user_id();
        $tenantId = current_tenant_id();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return redirect()->to('/player')->with('error', lang('App.no_data'));
        }

        $membership = model(\App\Models\MembershipModel::class)->find($id);
        if ($membership && (int) $membership->player_id === (int) $player->id && $this->membershipService->cancel($id)) {
            return redirect()->to('/player/profile/membership')->with('success', lang('App.membership_cancelled'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }

    public function ranking()
    {
        $tenantId = current_tenant_id();

        return view('player/profile/ranking', [
            'pageTitle' => lang('App.ranking'),
            'rankings' => $this->playerService->getRanking($tenantId, 'rating_score', 100),
        ]);
    }
}
