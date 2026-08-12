<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\BookingModel;
use App\Models\BookingItemModel;
use App\Models\BookingLogModel;
use App\Models\BookingQrCodeModel;
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
     * List bookings
     */
    public function index()
    {
        $tenantId = session()->get('tenant_id');
        $branchId = $this->request->getGet('branch_id');
        $status   = $this->request->getGet('status');
        $date     = $this->request->getGet('date');
        $search   = $this->request->getGet('search');

        $filters = [];
        if ($status) $filters['status'] = $status;
        if ($date) $filters['booking_date'] = $date;
        if ($search) $filters['search'] = $search;

        if ($branchId) {
            $ownedBranch = $this->branchModel->where('id', (int) $branchId)
                ->where('tenant_id', (int) $tenantId)->first();
            $bookings = $ownedBranch
                ? $this->bookingModel->getByBranch((int) $branchId, $filters, (int) $tenantId)
                : [];
        } else {
            $bookings = [];
            $branches = $this->branchModel->getByTenant($tenantId);
            foreach ($branches as $branch) {
                $branchBookings = $this->bookingModel->getByBranch($branch->id, $filters, (int) $tenantId);
                $bookings = array_merge($bookings, $branchBookings);
            }
            usort($bookings, function ($a, $b) {
                return strtotime($b->booking_date . ' ' . $b->start_time) - strtotime($a->booking_date . ' ' . $a->start_time);
            });
        }

        $branches = $this->branchModel->getByTenant($tenantId);

        return $this->render('admin/bookings/index', [
            'pageTitle' => lang('App.bookings'),
            'bookings' => $bookings,
            'branches' => $branches,
            'branchId' => $branchId,
            'status'   => $status,
            'date'     => $date,
            'search'   => $search,
        ]);
    }

    /**
     * Calendar view
     */
    public function calendar()
    {
        $tenantId = session()->get('tenant_id');
        $branchId = $this->request->getGet('branch_id');

        $branches = $this->branchModel->getByTenant($tenantId);

        if (!$branchId && !empty($branches)) {
            $branchId = $branches[0]->id;
        }

        $dateFrom = $this->request->getGet('date_from') ?? date('Y-m-d');
        $dateTo   = $this->request->getGet('date_to') ?? date('Y-m-d', strtotime('+6 days'));

        $events = [];
        if ($branchId) {
            $events = $this->bookingModel->getCalendarEvents($branchId, $dateFrom, $dateTo, (int) $tenantId);
        }

        return $this->render('admin/bookings/calendar', [
            'pageTitle' => lang('App.calendar'),
            'branches' => $branches,
            'branchId' => $branchId,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'events'   => $events,
        ]);
    }

    /**
     * Create booking form
     */
    public function create()
    {
        $tenantId = session()->get('tenant_id');
        $prefill = [
            'customer_name'  => (string) $this->request->getGet('customer_name'),
            'customer_phone' => (string) $this->request->getGet('customer_phone'),
            'customer_email' => (string) $this->request->getGet('customer_email'),
        ];
        $branches = $this->branchModel->getByTenant($tenantId);
        $branchId = $this->request->getGet('branch_id');

        $courts = [];
        if ($branchId) {
            $courts = $this->courtModel->getByBranch($branchId, ['status' => 'available']);
        }

        return $this->render('admin/bookings/form', [
            'pageTitle' => lang('App.create_booking'),
            'prefill'   => $prefill,
            'branches'  => $branches,
            'branchId'  => $branchId,
            'courts'    => $courts,
            'mode'      => 'create',
        ]);
    }

    /**
     * Store booking
     */
    public function store()
    {
        $tenantId = session()->get('tenant_id');
        $userId   = session()->get('user_id');

        $data = [
            'tenant_id'      => $tenantId,
            'branch_id'      => $this->request->getPost('branch_id'),
            'customer_name'  => $this->request->getPost('customer_name'),
            'customer_phone' => $this->request->getPost('customer_phone'),
            'customer_email' => $this->request->getPost('customer_email'),
            'booking_date'   => $this->request->getPost('booking_date'),
            'start_time'     => $this->request->getPost('start_time'),
            'end_time'       => $this->request->getPost('end_time'),
            'source'         => 'admin',
            'note'           => $this->request->getPost('note'),
            'status'         => 'reserved',
            'created_by'     => $userId,
            'items'          => [],
        ];

        $courtIds = $this->request->getPost('court_ids');
        $prices   = $this->request->getPost('prices');

        if (!empty($courtIds)) {
            foreach ($courtIds as $i => $courtId) {
                $data['items'][] = [
                    'court_id'   => $courtId,
                    'start_time' => $data['start_time'],
                    'end_time'   => $data['end_time'],
                    'price'      => $prices[$i] ?? 0,
                ];
            }
        }

        $result = $this->bookingService->createBooking($data);

        if ($result['success']) {
            return redirect()->to('/admin/bookings')->with('success', lang('App.booking_created_success'));
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Show booking detail
     */
    public function show($id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $booking = $this->bookingModel->findForTenant((int) $id, $tenantId);
        if (!$booking) {
            return redirect()->to('/admin/bookings')->with('error', lang('App.booking_not_found'));
        }

        $items = model(BookingItemModel::class)->getByBooking($id);
        $logs  = model(BookingLogModel::class)->getByBooking($id);
        $qrCode = model(BookingQrCodeModel::class)
            ->where('booking_id', $id)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        return $this->render('admin/bookings/show', [
            'pageTitle' => lang('App.booking_details'),
            'booking' => $booking,
            'items'   => $items,
            'logs'    => $logs,
            'qrCode'  => $qrCode,
        ]);
    }

    /**
     * Check-in booking directly (admin)
     */
    public function checkIn($id)
    {
        $userId = session()->get('user_id');
        $result = $this->bookingService->checkIn(
            (int) $id, $userId ? (int) $userId : null,
            (int) session()->get('tenant_id')
        );

        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Cancel booking
     */
    public function cancel($id)
    {
        $userId = session()->get('user_id');
        $reason = $this->request->getPost('reason');

        $result = $this->bookingService->cancelBooking($id, $reason, $userId, (int) session()->get('tenant_id'));

        if ($result['success']) {
            return redirect()->to('/admin/bookings')->with('success', lang('App.booking_cancelled_success'));
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Reschedule booking form
     */
    public function reschedule($id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $booking = $this->bookingModel->findForTenant((int) $id, $tenantId);
        if (!$booking) {
            return redirect()->to('/admin/bookings')->with('error', lang('App.booking_not_found'));
        }

        $tenantId = session()->get('tenant_id');
        $branches = $this->branchModel->getByTenant($tenantId);
        $courts   = $this->courtModel->getByBranch($booking->branch_id);
        $items    = model(BookingItemModel::class)->getByBooking($id);

        return $this->render('admin/bookings/reschedule', [
            'pageTitle' => lang('App.reschedule_booking'),
            'booking' => $booking,
            'branches' => $branches,
            'courts'   => $courts,
            'items'    => $items,
        ]);
    }

    /**
     * Update reschedule
     */
    public function updateReschedule($id)
    {
        $userId = session()->get('user_id');

        $newData = [
            'booking_date' => $this->request->getPost('booking_date'),
            'start_time'   => $this->request->getPost('start_time'),
            'end_time'     => $this->request->getPost('end_time'),
        ];

        $result = $this->bookingService->rescheduleBooking($id, $newData, $userId, (int) session()->get('tenant_id'));

        if ($result['success']) {
            return redirect()->to('/admin/bookings/show/' . $id)->with('success', lang('App.booking_rescheduled_success'));
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    /**
     * Check-in via QR (admin manual)
     */
    public function checkInQr()
    {
        $token = $this->request->getPost('qr_token');
        $userId = session()->get('user_id');

        $result = $this->bookingService->checkInByQr($token, $userId, (int) session()->get('tenant_id'));

        if ($result['success']) {
            return redirect()->to('/admin/bookings/show/' . $result['booking']->id)->with('success', lang('App.check_in_success'));
        }

        return redirect()->back()->with('error', $result['message']);
    }
}
