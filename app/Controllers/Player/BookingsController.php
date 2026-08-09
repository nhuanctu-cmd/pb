<?php

namespace App\Controllers\Player;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Models\BookingLogModel;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Services\BookingService;

class BookingsController extends BaseController
{
    protected BookingService $bookingService;
    protected BookingModel $bookingModel;
    protected BranchModel $branchModel;
    protected CourtModel $courtModel;

    public function __construct()
    {
        $this->bookingService = service('bookingService');
        $this->bookingModel   = model(BookingModel::class);
        $this->branchModel    = model(BranchModel::class);
        $this->courtModel     = model(CourtModel::class);
    }

    /**
     * Player booking list
     */
    public function index()
    {
        $playerId = session()->get('user_id');
        $status   = $this->request->getGet('status');

        $filters = [];
        if ($status) $filters['status'] = $status;

        $bookings = $this->bookingModel->getByPlayer(
            (int) $playerId, $filters, (int) session()->get('tenant_id')
        );

        return view('player/bookings/index', [
            'bookings' => $bookings,
            'status'   => $status,
        ]);
    }

    /**
     * Create booking - Step 1: Select date & court
     */
    public function create()
    {
        $tenantId = session()->get('tenant_id');
        $branches = $this->branchModel->getByTenant($tenantId);

        return view('player/bookings/create', [
            'branches' => $branches,
            'today'    => date('Y-m-d'),
            'step'     => 1,
        ]);
    }

    /**
     * Create booking - Step 2: Select time slot (AJAX)
     */
    public function getSlots()
    {
        $courtId = $this->request->getPost('court_id');
        $date    = $this->request->getPost('date');

        if (!$courtId || !$date) {
            return $this->response->setJSON(['success' => false, 'message' => lang('App.invalid_data')]);
        }

        $slots = $this->bookingService->getAvailableSlots(
            (int) $courtId, $date, 60, (int) session()->get('tenant_id')
        );

        return $this->response->setJSON(['success' => true, 'slots' => $slots]);
    }

    /**
     * Return court positions and availability for a Monday-Sunday window.
     */
    public function weekAvailability()
    {
        $branchId = (int) $this->request->getGet('branch_id');
        $weekStart = (string) ($this->request->getGet('week_start') ?: date('Y-m-d'));
        $tenantId = (int) session()->get('tenant_id');

        if (! $branchId || ! $this->branchModel->findForTenant($branchId, $tenantId)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => lang('App.invalid_data'),
            ]);
        }

        try {
            $data = $this->bookingService->getWeeklyAvailability($branchId, $weekStart, $tenantId);
        } catch (\InvalidArgumentException $exception) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => lang('App.invalid_data'),
            ]);
        }

        return $this->response->setJSON(['success' => true, 'data' => $data]);
    }

    /**
     * Store booking - Step 3: Confirm & submit
     */
    public function store()
    {
        $playerId = session()->get('user_id');
        $tenantId = session()->get('tenant_id');

        $data = [
            'tenant_id'      => $tenantId,
            'branch_id'      => $this->request->getPost('branch_id'),
            'player_id'      => $playerId,
            'customer_name'  => $this->request->getPost('customer_name'),
            'customer_phone' => $this->request->getPost('customer_phone'),
            'customer_email' => $this->request->getPost('customer_email'),
            'booking_date'   => $this->request->getPost('booking_date'),
            'start_time'     => $this->request->getPost('start_time'),
            'end_time'       => $this->request->getPost('end_time'),
            'source'         => 'player_portal',
            'note'           => $this->request->getPost('note'),
            'promotion_code' => trim((string) $this->request->getPost('promotion_code')) ?: null,
            'promotion_idempotency_key' => 'booking-' . bin2hex(random_bytes(8)),
            'created_by'     => $playerId,
            'items'          => [],
        ];

        $courtId = $this->request->getPost('court_id');
        $price   = $this->request->getPost('price') ?? 0;

        $data['items'][] = [
            'court_id'   => $courtId,
            'start_time' => $data['start_time'],
            'end_time'   => $data['end_time'],
            'price'      => $price,
        ];

        $result = $this->bookingService->holdBooking($data);

        if ($result['success']) {
            return redirect()->to('/player/bookings/detail/' . $result['booking']->id)
                           ->with('success', lang('App.booking_created_success'));
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Booking detail
     */
    public function detail($id)
    {
        $playerId = session()->get('user_id');
        $booking = $this->bookingModel->findForTenant((int) $id, (int) session()->get('tenant_id'));

        if (!$booking || $booking->player_id != $playerId) {
            return redirect()->to('/player/bookings')->with('error', lang('App.booking_not_found'));
        }

        $items = model(BookingItemModel::class)->getByBooking($id);
        $logs  = model(BookingLogModel::class)->getByBooking($id);

        return view('player/bookings/detail', [
            'booking' => $booking,
            'items'   => $items,
            'logs'    => $logs,
        ]);
    }

    /**
     * Cancel booking
     */
    public function cancel($id)
    {
        $playerId = session()->get('user_id');
        $booking = $this->bookingModel->findForTenant((int) $id, (int) session()->get('tenant_id'));

        if (!$booking || $booking->player_id != $playerId) {
            return redirect()->to('/player/bookings')->with('error', lang('App.booking_not_found'));
        }

        $reason = $this->request->getPost('reason') ?? lang('App.cancelled_by_player');
        $result = $this->bookingService->cancelBooking($id, $reason, $playerId, (int) session()->get('tenant_id'));

        if ($result['success']) {
            return redirect()->to('/player/bookings')->with('success', lang('App.booking_cancelled_success'));
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
