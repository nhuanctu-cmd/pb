<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;
use App\Services\BookingWaitlistService;

class WaitlistController extends BaseController
{
    private BookingWaitlistService $service;
    private BranchModel $branchModel;
    private CourtModel $courtModel;
    private PlayerModel $playerModel;

    public function __construct()
    {
        $this->service = service('bookingWaitlistService');
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
        return $this->render('admin/waitlist/index', [
            'pageTitle' => 'Danh sách chờ đặt sân',
            'entries' => $tenantId ? $this->service->list($tenantId, ['status' => $this->request->getGet('status')]) : [],
            'branches' => $branches,
            'courts' => $courts,
            'players' => $tenantId ? $this->playerModel->getByTenant($tenantId, ['status' => 'active']) : [],
        ]);
    }

    public function store()
    {
        $tenantId = (int) current_tenant_id();
        $playerId = $this->request->getPost('player_id') ?: null;
        $courtId = $this->request->getPost('court_id') ?: null;
        $data = [
            'branch_id' => $this->request->getPost('branch_id'),
            'court_id' => $courtId,
            'player_id' => $playerId,
            'customer_name' => $this->request->getPost('customer_name'),
            'customer_phone' => $this->request->getPost('customer_phone'),
            'customer_email' => $this->request->getPost('customer_email'),
            'booking_date' => $this->request->getPost('booking_date'),
            'start_time' => $this->request->getPost('start_time'),
            'end_time' => $this->request->getPost('end_time'),
            'priority' => $this->request->getPost('priority') ?: 100,
            'idempotency_key' => $this->request->getPost('idempotency_key'),
        ];
        $result = $tenantId ? $this->service->join($data, $tenantId, (int) user_id()) : ['success' => false, 'message' => lang('App.forbidden')];
        return $result['success']
            ? redirect()->to('/admin/waitlist')->with('success', !empty($result['duplicate']) ? 'Mục chờ đã tồn tại.' : 'Đã thêm vào danh sách chờ.')
            : redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function notifyNext()
    {
        $tenantId = (int) current_tenant_id();
        $result = $tenantId ? $this->service->notifyNext(
            $tenantId,
            (int) $this->request->getPost('branch_id'),
            $this->request->getPost('court_id') ? (int) $this->request->getPost('court_id') : null,
            (string) $this->request->getPost('booking_date'),
            (string) $this->request->getPost('start_time'),
            (string) $this->request->getPost('end_time')
        ) : ['success' => false, 'message' => lang('App.forbidden')];
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }

    public function claim(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $result = $tenantId ? $this->service->claim($id, $tenantId, (int) user_id()) : ['success' => false, 'message' => lang('App.forbidden')];
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['success'] ? 'Đã tạo booking từ waitlist.' : $result['message']);
    }

    public function cancel(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $ok = $tenantId && $this->service->cancel($id, $tenantId, (int) user_id());
        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Đã hủy mục chờ.' : 'Không thể hủy mục chờ.');
    }

    public function expire()
    {
        $tenantId = (int) current_tenant_id();
        $count = $tenantId ? $this->service->expire($tenantId) : 0;
        return redirect()->back()->with('success', "Đã hết hạn {$count} mục chờ.");
    }
}
