<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Services\PlayerService;
use App\Services\WalletService;

class WalletController extends BaseController
{
    protected PlayerService $playerService;
    protected WalletService $walletService;

    public function __construct()
    {
        $this->playerService = new PlayerService();
        $this->walletService = new WalletService();
    }

    public function index()
    {
        $userId   = user_id();
        $tenantId = current_tenant_id();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return redirect()->to('/player')->with('error', lang('App.no_data'));
        }

        $wallet       = $this->walletService->getWallet($player->id, $tenantId);
        $transactions = $this->walletService->getPlayerTransactions($player->id, $tenantId);

        return view('player/profile/wallet', [
            'player'       => $player,
            'wallet'       => $wallet,
            'transactions' => $transactions,
        ]);
    }

    public function topup()
    {
        $userId   = user_id();
        $tenantId = current_tenant_id();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return redirect()->to('/player')->with('error', lang('App.no_data'));
        }

        $amount = (float) $this->request->getPost('amount');
        if ($amount <= 0) {
            return redirect()->back()->with('error', lang('App.invalid_amount'));
        }

        // In a real system, this would integrate with a payment gateway
        // For now, we simulate a successful topup
        if ($this->walletService->topup($player->id, $tenantId, $amount, 'Nạp tiền từ cổng thanh toán')) {
            return redirect()->to('/player/wallet')->with('success', lang('App.topup_success'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }
}
