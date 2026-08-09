<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BookingItemModel;
use App\Models\BookingLogModel;
use App\Models\BookingModel;
use App\Models\BranchHolidayModel;
use App\Models\BranchOpeningHourModel;
use App\Models\CourtMaintenanceModel;
use App\Models\CourtModel;
use App\Models\BranchModel;
use App\Models\PricingRuleModel;
use App\Services\BookingService;
use App\Services\PricingService;

class OpsAjaxController extends BaseController
{
    public function availableCourts()
    {
        $tenantId = (int) current_tenant_id();
        $branchId = (int) $this->request->getGet('branch_id');
        $date = (string) $this->request->getGet('date');
        $startTime = (string) $this->request->getGet('start_time');
        $endTime = (string) $this->request->getGet('end_time');

        if (! $branchId || ! $date || ! $startTime || ! $endTime) {
            return $this->response->setJSON(['success' => false, 'message' => 'Thiếu thông tin lọc sân.']);
        }

        if (!$tenantId || !model(BranchModel::class)->findForTenant($branchId, $tenantId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Chi nhánh không thuộc tenant hiện tại.']);
        }

        $courtModel = model(CourtModel::class);
        $bookingService = service('bookingService');
        $courts = $courtModel->getByBranch($branchId);
        $courts = array_values(array_filter($courts, static fn ($court) => (int) $court->tenant_id === $tenantId));
        $data = [];

        foreach ($courts as $court) {
            if (! in_array($court->status, ['available'], true)) {
                continue;
            }

            if (! $bookingService->checkCourtAvailable((int) $court->id, $date, $startTime, $endTime)) {
                continue;
            }

            $dayOfWeek = (int) date('w', strtotime($date));
            $opening = model(BranchOpeningHourModel::class)->where('branch_id', $branchId)->where('day_of_week', $dayOfWeek)->where('deleted_at', null)->first();
            if ($opening && ((int) $opening->is_closed === 1 || ($opening->open_time && $opening->close_time && (substr($startTime, 0, 5) < substr($opening->open_time, 0, 5) || substr($endTime, 0, 5) > substr($opening->close_time, 0, 5))))) {
                continue;
            }

            $holiday = model(BranchHolidayModel::class)->where('branch_id', $branchId)->where('holiday_date', $date)->where('is_closed', 1)->where('deleted_at', null)->first();
            if ($holiday) {
                continue;
            }

            $maintenance = model(CourtMaintenanceModel::class)
                ->where('court_id', $court->id)
                ->whereIn('status', ['scheduled', 'doing'])
                ->where('deleted_at', null)
                ->groupStart()
                    ->where('start_time <', $date . ' ' . $endTime)
                    ->where('end_time >', $date . ' ' . $startTime)
                ->groupEnd()
                ->first();
            if ($maintenance) {
                continue;
            }

            $data[] = [
                'id' => (int) $court->id,
                'code' => $court->code,
                'name' => $court->getName(),
                'court_type' => $court->court_type_name_vi ?? null,
                'status' => $court->status,
                'is_indoor' => (bool) $court->is_indoor,
            ];
        }

        return $this->response->setJSON(['success' => true, 'data' => $data, 'tenant_id' => $tenantId]);
    }

    public function pricingTest()
    {
        $tenantId = (int) current_tenant_id();
        $branchId = (int) ($this->request->getPost('branch_id') ?: $this->request->getGet('branch_id'));
        $courtId = (int) ($this->request->getPost('court_id') ?: $this->request->getGet('court_id'));
        $date = (string) ($this->request->getPost('date') ?: $this->request->getGet('date') ?: date('Y-m-d'));
        $startTime = (string) ($this->request->getPost('start_time') ?: $this->request->getGet('start_time') ?: '18:00');
        $endTime = (string) ($this->request->getPost('end_time') ?: $this->request->getGet('end_time') ?: '19:00');
        $playerId = $this->request->getPost('player_id') ?: $this->request->getGet('player_id');

        if (! $branchId || ! $courtId || !$tenantId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Thiếu chi nhánh hoặc sân.']);
        }

        $court = model(CourtModel::class)->findForTenant($courtId, $tenantId);
        if (!$court || (int) $court->branch_id !== $branchId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sân không thuộc tenant/chi nhánh.']);
        }

        $result = (new PricingService())->getPrice($tenantId, $branchId, $courtId, $date, $startTime, $endTime, $playerId ? (int) $playerId : null);

        return $this->response->setJSON([
            'success' => true,
            'final_price' => $result['final_price'],
            'formatted_price' => format_money($result['final_price']),
            'selected_rule' => $result['selected_rule'] ? [
                'id' => (int) $result['selected_rule']->id,
                'name' => $result['selected_rule']->name_vi,
                'code' => $result['selected_rule']->code,
            ] : null,
            'breakdown' => $result['breakdown'],
            'log_id' => $result['log_id'],
        ]);
    }

    public function bookingDrawer(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $booking = $tenantId ? model(BookingModel::class)->findForTenant($id, $tenantId) : null;
        if (! $booking) {
            return $this->response->setJSON(['success' => false, 'message' => 'Không tìm thấy booking.']);
        }

        $items = model(BookingItemModel::class)->getByBooking($id, $tenantId);
        return $this->response->setJSON([
            'success' => true,
            'html' => view('admin/bookings/partials/drawer', ['booking' => $booking, 'items' => $items]),
        ]);
    }

    public function courtDrawer(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $court = $tenantId ? model(CourtModel::class)->findForTenant($id, $tenantId) : null;
        if (! $court) {
            return $this->response->setJSON(['success' => false, 'message' => 'Không tìm thấy sân.']);
        }

        $rules = model(PricingRuleModel::class)->getApplicableRules((int) $court->tenant_id, (int) $court->branch_id, (int) $court->court_type_id, (int) $court->id);
        return $this->response->setJSON([
            'success' => true,
            'html' => view('admin/courts/partials/drawer', ['court' => $court, 'rules' => $rules]),
        ]);
    }

    public function courtPricingRules(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $court = $tenantId ? model(CourtModel::class)->findForTenant($id, $tenantId) : null;
        if (! $court) {
            return $this->response->setJSON(['success' => false, 'message' => 'Không tìm thấy sân.']);
        }

        $rules = model(PricingRuleModel::class)->getApplicableRules((int) $court->tenant_id, (int) $court->branch_id, (int) $court->court_type_id, (int) $court->id);
        return $this->response->setJSON(['success' => true, 'data' => $rules]);
    }

    public function reschedulePreview(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $booking = $tenantId ? model(BookingModel::class)->findForTenant($id, $tenantId) : null;
        if (! $booking) {
            return $this->response->setJSON(['success' => false, 'message' => 'Không tìm thấy booking.']);
        }

        $date = (string) $this->request->getGet('date');
        $startTime = (string) $this->request->getGet('start_time');
        $endTime = (string) $this->request->getGet('end_time');
        $items = model(BookingItemModel::class)->getByBooking($id, $tenantId);
        $preview = [];

        foreach ($items as $item) {
            $available = service('bookingService')->checkCourtAvailable((int) $item->court_id, $date, $startTime, $endTime, $id);
            $price = (new PricingService())->getPrice((int) $booking->tenant_id, (int) $booking->branch_id, (int) $item->court_id, $date, $startTime, $endTime, $booking->player_id ? (int) $booking->player_id : null);
            $preview[] = [
                'court_id' => (int) $item->court_id,
                'available' => $available,
                'final_price' => $price['final_price'],
                'formatted_price' => format_money($price['final_price']),
                'rule' => $price['selected_rule']->name_vi ?? null,
            ];
        }

        return $this->response->setJSON(['success' => true, 'data' => $preview]);
    }
}
