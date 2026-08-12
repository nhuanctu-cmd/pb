<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\TournamentModel;

class AiSchedulingController extends BaseController
{
    private $service;
    public function __construct() { $this->service = service('aiSchedulingService'); }
    public function index()
    {
        $tenantId = (int) current_tenant_id();
        return $this->render('admin/ai_scheduling/index', ['pageTitle' => lang('App.menu_ai_scheduling'), 'requests' => $tenantId ? $this->service->requests($tenantId) : [], 'branches' => $tenantId ? (new BranchModel())->getByTenant($tenantId) : [], 'tournaments' => $tenantId ? (new TournamentModel())->getByTenant($tenantId) : []]);
    }
    public function store()
    {
        $result = $this->service->create($this->request->getPost(), (int) current_tenant_id(), (int) user_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }
    public function run(int $id)
    {
        $result = $this->service->run($id, (int) current_tenant_id(), (int) user_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }
}
