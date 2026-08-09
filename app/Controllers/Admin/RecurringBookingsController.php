<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;
use App\Services\RecurringBookingService;

class RecurringBookingsController extends BaseController
{
    private RecurringBookingService $service;
    private BranchModel $branchModel;
    private CourtModel $courtModel;
    private PlayerModel $playerModel;

    public function __construct()
    {
        $this->service = service('recurringBookingService');
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
        $this->playerModel = new PlayerModel();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $branchId = (int) ($this->request->getGet('branch_id') ?? 0);
        $branches = $tenantId ? $this->branchModel->getByTenant($tenantId) : [];
        $courts = [];
        if ($tenantId) {
            if ($branchId && $this->branchModel->findForTenant($branchId, $tenantId)) {
                $courts = $this->courtModel->getByBranch($branchId);
            } else {
                foreach ($branches as $branch) {
                    $courts = array_merge($courts, $this->courtModel->getByBranch((int) $branch->id));
                }
            }
        }
        $courts = array_values(array_filter($courts, static fn ($court) => (int) $court->tenant_id === $tenantId));

        return $this->render('admin/recurring_bookings/index', [
            'pageTitle' => 'Lịch đặt sân định kỳ',
            'templates' => $tenantId ? $this->service->list($tenantId, ['branch_id' => $branchId]) : [],
            'branches' => $branches,
            'courts' => $courts,
            'players' => $tenantId ? $this->playerModel->getByTenant($tenantId, ['status' => 'active']) : [],
            'branchId' => $branchId,
            'branchNames' => array_reduce($branches, static function (array $map, object $branch): array {
                $map[(int) $branch->id] = $branch->name;
                return $map;
            }, []),
        ]);
    }

    public function store()
    {
        $tenantId = (int) current_tenant_id();
        if (!$tenantId) {
            return redirect()->to('/admin/tenants/select')->with('error', lang('App.forbidden'));
        }
        $result = $this->service->createTemplate([
            'branch_id' => $this->request->getPost('branch_id'),
            'court_id' => $this->request->getPost('court_id'),
            'player_id' => $this->request->getPost('player_id'),
            'name' => $this->request->getPost('name'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date' => $this->request->getPost('end_date'),
            'start_time' => $this->request->getPost('start_time'),
            'end_time' => $this->request->getPost('end_time'),
            'repeat_type' => $this->request->getPost('repeat_type'),
            'repeat_interval' => $this->request->getPost('repeat_interval'),
            'repeat_days' => $this->request->getPost('repeat_days') ?? [],
            'exclude_dates' => $this->request->getPost('exclude_dates') ?? [],
        ], $tenantId, (int) user_id());

        return $result['success']
            ? redirect()->to('/admin/recurring-bookings')->with('success', 'Đã tạo lịch đặt sân định kỳ.')
            : redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function status(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $status = (string) $this->request->getPost('status');
        $ok = $tenantId && $this->service->changeStatus($id, $status, $tenantId, (int) user_id());
        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Đã cập nhật lịch định kỳ.' : 'Không thể cập nhật lịch định kỳ.');
    }

    public function processDue()
    {
        $tenantId = (int) current_tenant_id();
        $results = $tenantId ? $this->service->processDue($tenantId, 50) : [];
        $success = count(array_filter($results, static fn (array $result) => !empty($result['success'])));
        return redirect()->back()->with('success', "Đã xử lý {$success}/" . count($results) . ' occurrence đến hạn.');
    }
}
