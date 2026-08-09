<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\PlayerWalletModel;
use App\Models\PlayerStatisticModel;

class DashboardController extends BaseController
{
    protected BookingModel $bookingModel;
    protected PlayerWalletModel $walletModel;
    protected PlayerStatisticModel $statModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->walletModel = new PlayerWalletModel();
        $this->statModel = new PlayerStatisticModel();
    }

    public function index()
    {
        $playerId = session()->get('player_id');
        $tenantId = session()->get('tenant_id');

        $upcomingBookings = $this->bookingModel
            ->where('player_id', $playerId)
            ->where('tenant_id', $tenantId)
            ->where('start_time >=', date('Y-m-d H:i:s'))
            ->where('status', 'confirmed')
            ->orderBy('start_time', 'ASC')
            ->limit(3)
            ->findAll();

        $wallet = $this->walletModel->where('player_id', $playerId)->where('tenant_id', $tenantId)->first();
        $stats = $this->statModel->where('player_id', $playerId)->where('tenant_id', $tenantId)->first();

        $data = [
            'showBack' => false,
            'playerName' => session()->get('player_name'),
            'upcomingBookings' => $upcomingBookings,
            'wallet' => $wallet,
            'stats' => $stats,
        ];

        return view('player/dashboard', $data);
    }
}
