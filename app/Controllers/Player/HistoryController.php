<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\BookingModel;

class HistoryController extends BaseController
{
    public function index()
    {
        $userId = user_id();
        $bookingModel = new BookingModel();

        return $this->render('player/bookings/index', [
            'pageTitle' => lang('App.my_bookings'),
            'bookings'  => $userId ? $bookingModel->getByPlayer($userId, ['status' => 'completed']) : [],
        ]);
    }
}
