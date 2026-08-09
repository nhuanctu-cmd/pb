<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ClubModel;
use App\Models\PlayerModel;

class ClubsController extends BaseController
{
    protected ClubModel $clubModel;

    public function __construct()
    {
        $this->clubModel = model(ClubModel::class);
    }

    public function index()
    {
        $tenantId = (int) session('tenant_id');
        return $this->render('admin/clubs/index', [
            'pageTitle' => 'Quản lý club',
            'clubs' => $this->clubModel->getByTenant($tenantId, [
                'status' => $this->request->getGet('status'),
                'search' => $this->request->getGet('search'),
            ]),
        ]);
    }

    public function create()
    {
        return $this->render('admin/clubs/form', [
            'pageTitle' => 'Tạo club',
            'club' => null,
            'players' => model(PlayerModel::class)->getByTenant((int) session('tenant_id'), ['status' => 'active']),
        ]);
    }

    public function store()
    {
        $id = $this->clubModel->insert($this->payload());

        return $id
            ? redirect()->to('/admin/clubs')->with('success', 'Đã tạo club.')
            : redirect()->back()->withInput()->with('error', implode(' ', $this->clubModel->errors()));
    }

    public function edit($id)
    {
        $tenantId = (int) session('tenant_id');
        $club = $tenantId ? $this->clubModel->findForTenant((int) $id, $tenantId) : null;
        if (!$club) {
            return redirect()->to('/admin/clubs')->with('error', 'Không tìm thấy club.');
        }
        return $this->render('admin/clubs/form', [
            'pageTitle' => 'Cập nhật club',
            'club' => $club,
            'players' => model(PlayerModel::class)->getByTenant($tenantId, ['status' => 'active']),
        ]);
    }

    public function update($id)
    {
        $tenantId = (int) session('tenant_id');
        if (!$tenantId || !$this->clubModel->findForTenant((int) $id, $tenantId)) {
            return redirect()->to('/admin/clubs')->with('error', 'Không tìm thấy club.');
        }
        $ok = $this->clubModel->update($id, $this->payload());

        return $ok
            ? redirect()->to('/admin/clubs')->with('success', 'Đã cập nhật club.')
            : redirect()->back()->withInput()->with('error', implode(' ', $this->clubModel->errors()));
    }

    public function delete($id)
    {
        $tenantId = (int) session('tenant_id');
        if (!$tenantId || !$this->clubModel->findForTenant((int) $id, $tenantId)) {
            return redirect()->to('/admin/clubs')->with('error', 'Không tìm thấy club.');
        }
        $this->clubModel->delete($id);
        return redirect()->to('/admin/clubs')->with('success', 'Đã xóa club.');
    }

    private function payload(): array
    {
        return [
            'tenant_id' => (int) session('tenant_id'),
            'name_vi' => $this->request->getPost('name_vi'),
            'name_en' => $this->request->getPost('name_en'),
            'logo' => $this->request->getPost('logo'),
            'description_vi' => $this->request->getPost('description_vi'),
            'description_en' => $this->request->getPost('description_en'),
            'owner_player_id' => $this->request->getPost('owner_player_id') ?: null,
            'status' => $this->request->getPost('status') ?: 'active',
        ];
    }
}
