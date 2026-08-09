<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\PlayerModel;

class OpenPlayController extends BaseController
{
    private $service;
    private $rotationService;
    private BranchModel $branchModel;
    private PlayerModel $playerModel;

    public function __construct()
    {
        $this->service = service('openPlayService');
        $this->rotationService = service('openPlayRotationService');
        $this->branchModel = new BranchModel();
        $this->playerModel = new PlayerModel();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $sessions = $tenantId ? $this->service->list($tenantId, ['session_date' => $this->request->getGet('date') ?: date('Y-m-d')]) : [];
        $players = $rotations = [];
        foreach ($sessions as $session) {
            $players[(int) $session->id] = $this->service->players((int) $session->id, $tenantId);
            $rotations[(int) $session->id] = $this->rotationService->schedule((int) $session->id, $tenantId);
        }
        return $this->render('admin/open_play/index', [
            'pageTitle' => lang('App.menu_open_play'),
            'sessions' => $sessions,
            'sessionPlayers' => $players,
            'branches' => $tenantId ? $this->branchModel->getByTenant($tenantId) : [],
            'players' => $tenantId ? $this->playerModel->getByTenant($tenantId, ['status' => 'active']) : [],
            'rotations' => $rotations,
        ]);
    }

    public function store()
    {
        $tenantId = (int) current_tenant_id();
        $result = $tenantId ? $this->service->create($this->request->getPost(), $tenantId, (int) user_id()) : ['success' => false, 'message' => lang('App.forbidden')];
        return $result['success'] ? redirect()->to('/admin/open-play')->with('success', 'Đã tạo Open Play.') : redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function join(int $id)
    {
        $result = $this->service->requestJoin($id, (int) $this->request->getPost('player_id'), (int) current_tenant_id(), (int) user_id());
        return $this->action($result);
    }

    public function approve(int $id)
    {
        return $this->action($this->service->approve($id, (int) current_tenant_id(), (int) user_id()));
    }

    public function leave(int $id)
    {
        return $this->action($this->service->leave($id, (int) current_tenant_id(), (int) user_id()));
    }

    public function generateRotation(int $id)
    {
        return $this->action($this->rotationService->generate($id, (int) current_tenant_id(), (int) $this->request->getPost('round_minutes'), (int) user_id()));
    }

    private function action(array $result)
    {
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }
}
