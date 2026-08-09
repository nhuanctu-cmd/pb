<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Services\BookingService;

class BookingApi extends BaseController
{
    protected BookingService $bookingService;
    protected BookingModel $bookingModel;

    public function __construct()
    {
        $this->bookingService = service('bookingService');
        $this->bookingModel   = model(BookingModel::class);
    }

    public function index()
    {
        $tenantId = $this->request->api_tenant_id ?? current_tenant_id();
        $branchId = $this->request->getGet('branch_id');

        if (!$tenantId) {
            return service('apiResponseService')->validationError(['tenant_id' => 'tenant_id là bắt buộc']);
        }

        if ($branchId) {
            $bookings = $this->bookingModel->getByBranch((int) $branchId, $this->request->getGet(), (int) $tenantId);
        } else {
            $bookings = $this->bookingModel
                ->where('tenant_id', (int) $tenantId)
                ->where('deleted_at', null)
                ->orderBy('booking_date', 'DESC')
                ->orderBy('start_time', 'DESC')
                ->findAll();
        }

        return service('apiResponseService')->success($bookings);
    }

    /**
     * Get available slots
     * GET /api/v1/booking/available-slots
     */
    public function availableSlots()
    {
        $courtId = $this->request->getGet('court_id');
        $date    = $this->request->getGet('date');

        if (!$courtId || !$date) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('App.missing_parameters'),
            ]);
        }

        $tenantId = $this->request->api_tenant_id ?? current_tenant_id();
        $slots = $this->bookingService->getAvailableSlots(
            (int) $courtId, $date, 60, $tenantId ? (int) $tenantId : null
        );

        return $this->response->setJSON([
            'success' => true,
            'data'    => $slots,
        ]);
    }

    /**
     * Create booking from public API
     * POST /api/v1/bookings
     */
    public function create()
    {
        $tenantId = $this->request->api_tenant_id ?? $this->request->getPost('tenant_id') ?? session()->get('tenant_id');
        $branchId = $this->request->getPost('branch_id');

        if (!$branchId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('App.missing_parameters'),
            ]);
        }

        $data = [
            'tenant_id'      => $tenantId,
            'branch_id'      => $branchId,
            'player_id'      => $this->request->getPost('player_id'),
            'customer_name'  => $this->request->getPost('customer_name'),
            'customer_phone' => $this->request->getPost('customer_phone'),
            'customer_email' => $this->request->getPost('customer_email'),
            'booking_date'   => $this->request->getPost('booking_date'),
            'start_time'     => $this->request->getPost('start_time'),
            'end_time'       => $this->request->getPost('end_time'),
            'source'         => $this->request->getPost('source') ?? 'public_web',
            'note'           => $this->request->getPost('note'),
            'items'          => [],
        ];

        $items = $this->request->getPost('items');
        if ($items) {
            $data['items'] = is_array($items) ? $items : json_decode($items, true);
        } else {
            // Single court item
            $data['items'][] = [
                'court_id'   => $this->request->getPost('court_id'),
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'price'      => $this->request->getPost('price') ?? 0,
            ];
        }

        $result = $this->bookingService->holdBooking($data);

        return $this->response->setJSON($result);
    }

    /**
     * Get booking detail
     * GET /api/v1/bookings/(:num)
     */
    public function detail($id)
    {
        $tenantId = $this->request->api_tenant_id ?? current_tenant_id();
        $booking = $tenantId
            ? $this->bookingModel->findForTenant((int) $id, (int) $tenantId)
            : null;
        if (!$booking) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('App.booking_not_found'),
            ]);
        }

        $items = model(BookingItemModel::class)->getByBooking($id);

        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'booking' => $booking->toRawArray(),
                'items'   => $items,
            ],
        ]);
    }

    /**
     * Cancel booking
     * POST /api/v1/bookings/(:num)/cancel
     */
    public function cancel($id)
    {
        $reason = $this->request->getPost('reason') ?? lang('App.cancelled_via_api');
        $userId = $this->request->getPost('user_id');

        $tenantId = $this->request->api_tenant_id ?? current_tenant_id();
        $result = $this->bookingService->cancelBooking(
            (int) $id, $reason, $userId ? (int) $userId : null,
            $tenantId ? (int) $tenantId : null
        );

        return $this->response->setJSON($result);
    }

    /**
     * Check-in via QR
     * POST /api/v1/booking/checkin
     */
    public function checkInQr()
    {
        $token  = $this->request->getPost('qr_token');
        $userId = $this->request->getPost('user_id');

        if (!$token) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('App.missing_qr_token'),
            ]);
        }

        $tenantId = $this->request->api_tenant_id ?? current_tenant_id();
        $result = $this->bookingService->checkInByQr(
            $token, $userId ? (int) $userId : null,
            $tenantId ? (int) $tenantId : null
        );

        return $this->response->setJSON($result);
    }
}
