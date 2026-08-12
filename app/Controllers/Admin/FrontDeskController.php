<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingModel;
use App\Models\CourtModel;
use App\Models\BranchModel;
use App\Services\BookingService;
use App\Services\OperationsDashboardService;

class FrontDeskController extends BaseController
{
    private BookingService $bookingService;
    private BookingModel $bookingModel;
    private CourtModel $courtModel;
    private BranchModel $branchModel;

    public function __construct()
    {
        $this->bookingService = service('bookingService');
        $this->bookingModel = model(BookingModel::class);
        $this->courtModel = model(CourtModel::class);
        $this->branchModel = model(BranchModel::class);
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $branchId = (int) current_branch_id();
        $date = OperationsDashboardService::normalizeDate($this->request->getGet('date'));
        $search = trim((string) $this->request->getGet('search'));
        $status = strtolower(trim((string) $this->request->getGet('status')));
        $timeframe = trim((string) $this->request->getGet('frame'));
        $scopeBranchId = is_superadmin() ? ((int) $this->request->getGet('branch_id') ?: null) : ($branchId ?: null);
        $this->bookingService->releaseExpiredBookings();

        $operations = service('operationsDashboardService')->get($tenantId, $date, is_superadmin() ? $scopeBranchId : ($branchId ?: null));

        $query = $this->bookingModel
            ->select('bookings.*, GROUP_CONCAT(CONCAT(c.code, " - ", c.name_vi) ORDER BY c.sort_order SEPARATOR ", ") AS court_names')
            ->join('booking_items bi', 'bi.booking_id = bookings.id AND bi.status = "active"', 'left')
            ->join('courts c', 'c.id = bi.court_id', 'left')
            ->where('bookings.tenant_id', $tenantId)
            ->where('bookings.booking_date', $date)
            ->where('bookings.deleted_at', null)
            ->groupBy('bookings.id')
            ->orderBy('bookings.start_time', 'ASC');

        if ($scopeBranchId) {
            $query->where('bookings.branch_id', $scopeBranchId);
        }
        if ($status !== '') {
            $query->where('bookings.status', $status);
        }
        if ($search !== '') {
            $query->groupStart()
                ->like('bookings.customer_name', $search)
                ->orLike('bookings.customer_phone', $search)
                ->orLike('bookings.id', $search)
                ->groupEnd();
        }
        if ($timeframe === 'upcoming') {
            $query->where('STR_TO_DATE(CONCAT(bookings.booking_date, " ", bookings.start_time), "%Y-%m-%d %H:%i:%s") >=', $date . ' 00:00:00');
        }

        $bookings = $query->get()->getResult();

        $playNow = [];
        $lateBookings = [];
        $nowTs = strtotime($date . ' ' . date('H:i:s'));
        $holdQueue = [];
        foreach ($bookings as $booking) {
            $startTs = strtotime((string) $booking->booking_date . ' ' . substr((string) $booking->start_time, 0, 8));
            if ($startTs === false) {
                continue;
            }
            if (in_array($booking->status, ['checked_in', 'in_progress'], true) && $startTs <= time()) {
                $playNow[] = $booking;
            }
            if (in_array($booking->status, ['reserved', 'paid'], true) && $startTs < $nowTs) {
                $booking->late_minutes = max(0, (int) floor(($nowTs - $startTs) / 60));
                $lateBookings[] = $booking;
            }
            if ((string) $booking->status === 'hold' && ! empty($booking->hold_until)) {
                $booking->hold_remaining_minutes = max(0, (int) floor((strtotime((string) $booking->hold_until) - time()) / 60));
            }
            if ((string) $booking->status === 'hold') {
                $holdQueue[] = $booking;
            }
        }

        $this->viewData['pageTitle'] = 'Front Desk';
        $this->viewData['date'] = $date;
        $this->viewData['operations'] = $operations;
        $this->viewData['search'] = $search;
        $this->viewData['statusFilter'] = $status;
        $this->viewData['timeframe'] = $timeframe;
        $this->viewData['bookings'] = $bookings;
        $this->viewData['playNow'] = $playNow;
        $this->viewData['lateBookings'] = $lateBookings;
        $this->viewData['holdQueue'] = $holdQueue;
        $this->viewData['nextBooking'] = $this->nextBooking($bookings, $date);
        $this->viewData['courts'] = $this->courtsLiveState($tenantId, $scopeBranchId);
        $this->viewData['branches'] = $tenantId > 0 ? $this->branchModel->where('tenant_id', $tenantId)->where('deleted_at', null)->findAll() : [];
        $this->viewData['scopeBranchId'] = $scopeBranchId;

        return $this->render('admin/front_desk/index', $this->viewData);
    }

    public function checkIn(int $id)
    {
        $date = OperationsDashboardService::normalizeDate((string) $this->request->getPost('date', '')) ?: OperationsDashboardService::normalizeDate(date('Y-m-d'));
        $result = $this->bookingService->checkIn($id, (int) user_id(), (int) current_tenant_id());
        if (! empty($result['success'])) {
            $this->auditFrontDeskAction('check_in', $id, $result['booking'] ?? null);
        }

        return redirect()->to('/admin/front-desk?date=' . $date)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function complete(int $id)
    {
        $date = OperationsDashboardService::normalizeDate((string) $this->request->getPost('date', '')) ?: OperationsDashboardService::normalizeDate(date('Y-m-d'));
        $result = $this->bookingService->markCompleted($id, (int) user_id(), (int) current_tenant_id());
        if (! empty($result['success'])) {
            $this->auditFrontDeskAction('complete', $id, $result['booking'] ?? null);
        }

        return redirect()->to('/admin/front-desk?date=' . $date)->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function noShow(int $id)
    {
        $date = OperationsDashboardService::normalizeDate((string) $this->request->getPost('date', '')) ?: OperationsDashboardService::normalizeDate(date('Y-m-d'));
        $reason = trim((string) $this->request->getPost('reason'));
        $result = $this->bookingService->markNoShow(
            $id,
            (int) user_id(),
            (int) current_tenant_id(),
            $reason !== '' ? $reason : null
        );
        if (! empty($result['success'])) {
            $this->auditFrontDeskAction('no_show', $id, $result['booking'] ?? null, ['reason' => $reason]);
        }

        return redirect()->to('/admin/front-desk?date=' . $date)->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function cancel(int $id)
    {
        $date = OperationsDashboardService::normalizeDate((string) $this->request->getPost('date', '')) ?: OperationsDashboardService::normalizeDate(date('Y-m-d'));
        $reason = trim((string) $this->request->getPost('reason'));
        $result = $this->bookingService->cancelBooking($id, $reason !== '' ? $reason : null, (int) user_id(), (int) current_tenant_id());
        if (! empty($result['success'])) {
            $this->auditFrontDeskAction('cancel', $id, $result['booking'] ?? null, ['reason' => $reason]);
        }

        return redirect()->to('/admin/front-desk?date=' . $date)->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function hold(int $id)
    {
        $date = OperationsDashboardService::normalizeDate((string) $this->request->getPost('date', '')) ?: OperationsDashboardService::normalizeDate(date('Y-m-d'));
        $minutes = (int) ($this->request->getPost('timeout_minutes') ?: 5);
        $result = $this->bookingService->holdBookingById($id, $minutes, user_id(), (int) current_tenant_id());
        if (! empty($result['success'])) {
            $this->auditFrontDeskAction('hold', $id, $result['booking'] ?? null, ['timeout_minutes' => $minutes]);
        }

        return redirect()->to('/admin/front-desk?date=' . $date)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function releaseHold(int $id)
    {
        $date = OperationsDashboardService::normalizeDate((string) $this->request->getPost('date', '')) ?: OperationsDashboardService::normalizeDate(date('Y-m-d'));
        $result = $this->bookingService->releaseHoldById($id, user_id(), (int) current_tenant_id());
        if (! empty($result['success'])) {
            $this->auditFrontDeskAction('release_hold', $id, $result['booking'] ?? null);
        }

        return redirect()->to('/admin/front-desk?date=' . $date)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function auditFrontDeskAction(string $action, int $id, ?object $booking, array $extra = []): void
    {
        if (! function_exists('log_audit')) {
            return;
        }

        log_audit([
            'action' => 'front_desk_' . $action,
            'table' => 'bookings',
            'record_id' => $id,
            'tenant_id' => current_tenant_id(),
            'data' => [
                'booking_status' => is_object($booking) ? ($booking->status ?? null) : null,
                'extra' => $extra,
            ],
            'metadata' => array_merge([
                'action' => $action,
                'record_type' => 'booking',
            ], $extra),
        ]);
    }

    private function courtsLiveState(int $tenantId, ?int $branchId = null): array
    {
        $rows = $this->courtModel->where('tenant_id', $tenantId)->where('deleted_at', null);
        if ($branchId) {
            $rows = $rows->where('branch_id', $branchId);
        }
        $rows = $rows->select('id, code, name_vi, name_en, status')->findAll();

        $occupied = 0;
        $available = 0;
        $maintenance = 0;
        $other = 0;
        foreach ($rows as $row) {
            if ($row->status === 'occupied') {
                $occupied++;
            } elseif ($row->status === 'available') {
                $available++;
            } elseif ($row->status === 'maintenance') {
                $maintenance++;
            } else {
                $other++;
            }
        }

        return [
            'total' => count($rows),
            'occupied' => $occupied,
            'available' => $available,
            'maintenance' => $maintenance,
            'other' => $other,
        ];
    }

    private function nextBooking(array $bookings, string $date): ?object
    {
        $nowTs = strtotime($date . ' ' . date('H:i:s'));
        $candidate = null;
        $candidateTs = null;

        foreach ($bookings as $booking) {
            if (! in_array($booking->status, ['reserved', 'paid'], true)) {
                continue;
            }
            $startTs = strtotime((string) $booking->booking_date . ' ' . substr((string) $booking->start_time, 0, 8));
            if ($startTs === false || $startTs < $nowTs) {
                continue;
            }
            if ($candidateTs === null || $startTs < $candidateTs) {
                $candidateTs = $startTs;
                $candidate = $booking;
            }
        }

        return $candidate;
    }
}
