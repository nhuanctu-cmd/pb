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
        $userId = session()->get('user_id') ?: session()->get('userId');

        // bookings.player_id hiện liên kết tới users.id; các module CRM/wallet
        // dùng players.id. Giữ hai khóa riêng để không lấy nhầm dữ liệu.
        $upcomingBookings = $this->bookingModel
            ->where('player_id', $userId)
            ->where('tenant_id', $tenantId)
            ->groupStart()
                ->where('booking_date >', date('Y-m-d'))
                ->orGroupStart()
                    ->where('booking_date', date('Y-m-d'))
                    ->where('start_time >=', date('H:i:s'))
                ->groupEnd()
            ->groupEnd()
            ->whereIn('status', ['pending', 'reserved', 'paid', 'checked_in', 'in_progress'])
            ->orderBy('start_time', 'ASC')
            ->limit(3)
            ->findAll();

        $wallet = $this->walletModel->where('player_id', $playerId)->where('tenant_id', $tenantId)->first();
        $stats = $this->statModel->where('player_id', $playerId)->where('tenant_id', $tenantId)->first();

        $data = [
            'showBack' => false,
            'playerName' => session()->get('player_name') ?: session()->get('fullName'),
            'upcomingBookings' => array_map(static fn ($booking) => $booking instanceof \CodeIgniter\Entity\Entity ? $booking->toArray() : (array) $booking, $upcomingBookings),
            'wallet' => $wallet instanceof \CodeIgniter\Entity\Entity ? $wallet->toArray() : ($wallet ?: []),
            'stats' => $stats instanceof \CodeIgniter\Entity\Entity ? $stats->toArray() : ($stats ?: []),
        ];

        return view('player/dashboard', $data);
    }
}
