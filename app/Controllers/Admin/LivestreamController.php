<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\TournamentModel;

class LivestreamController extends BaseController
{
    private $service;

    public function __construct() { $this->service = service('livestreamService'); }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        return $this->render('admin/livestream/index', [
            'pageTitle' => lang('App.menu_livestream'),
            'channels' => $tenantId ? $this->service->channels($tenantId) : [],
            'branches' => $tenantId ? (new BranchModel())->getByTenant($tenantId) : [],
            'tournaments' => $tenantId ? (new TournamentModel())->getByTenant($tenantId) : [],
        ]);
    }

    public function store()
    {
        $result = $this->service->create($this->request->getPost(), (int) current_tenant_id(), (int) user_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function status(int $id)
    {
        $result = $this->service->updateStatus($id, (string) $this->request->getPost('status'), (int) current_tenant_id(), (int) user_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
