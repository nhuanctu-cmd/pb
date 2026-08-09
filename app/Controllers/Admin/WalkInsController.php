<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;

class WalkInsController extends BaseController
{
    private $service;
    private BranchModel $branchModel;
    private CourtModel $courtModel;
    private PlayerModel $playerModel;

    public function __construct()
    {
        $this->service = service('walkInService');
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
        $this->playerModel = new PlayerModel();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $branches = $tenantId ? $this->branchModel->getByTenant($tenantId) : [];
        $courts = [];
        foreach ($branches as $branch) {
            $courts = array_merge($courts, $this->courtModel->getByBranch((int) $branch->id, ['status' => 'available']));
        }
        $courts = array_values(array_filter($courts, static fn ($court) => (int) $court->tenant_id === $tenantId));
        return $this->render('admin/walk_ins/index', [
            'pageTitle' => lang('App.menu_walk_ins'),
            'entries' => $tenantId ? $this->service->list($tenantId, ['date' => date('Y-m-d')]) : [],
            'branches' => $branches,
            'courts' => $courts,
            'players' => $tenantId ? $this->playerModel->getByTenant($tenantId, ['status' => 'active']) : [],
        ]);
    }

    public function store()
    {
        $tenantId = (int) current_tenant_id();
        $result = $tenantId ? $this->service->create($this->request->getPost(), $tenantId, (int) user_id()) : ['success' => false, 'message' => lang('App.forbidden')];
        return $result['success']
            ? redirect()->to('/admin/walk-ins')->with('success', !empty($result['duplicate']) ? 'Phiên walk-in đã tồn tại.' : 'Đã tạo phiên walk-in.')
            : redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function checkIn(int $id)
    {
        return $this->action($this->service->checkIn($id, (int) current_tenant_id(), (int) user_id()));
    }

    public function checkout(int $id)
    {
        return $this->action($this->service->checkout($id, (int) current_tenant_id(), (int) user_id()));
    }

    public function cancel(int $id)
    {
        return $this->action($this->service->cancel($id, (int) current_tenant_id(), (int) user_id()));
    }

    private function action(array $result)
    {
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }
}
