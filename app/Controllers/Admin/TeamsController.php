<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeamMemberModel;
use App\Models\TeamModel;

class TeamsController extends BaseController
{
    public function index()
    {
        $tenantId = (int) session('tenant_id');
        return $this->render('admin/teams/index', [
            'pageTitle' => 'Quản lý team',
            'teams' => model(TeamModel::class)->getByTenant($tenantId, ['status' => $this->request->getGet('status')]),
        ]);
    }

    public function show($id)
    {
        $tenantId = (int) session('tenant_id');
        $team = $tenantId ? model(TeamModel::class)->findForTenant((int) $id, $tenantId) : null;
        return $this->render('admin/teams/show', [
            'pageTitle' => $team ? $team->team_name : 'Team',
            'team' => $team,
            'members' => model(TeamMemberModel::class)->getByTeam((int) $id, $tenantId),
        ]);
    }

    public function status($id)
    {
        $tenantId = (int) session('tenant_id');
        if (!$tenantId || !model(TeamModel::class)->findForTenant((int) $id, $tenantId)) {
            return redirect()->back()->with('error', 'Không tìm thấy team.');
        }
        model(TeamModel::class)->update($id, ['status' => $this->request->getPost('status') ?: 'inactive']);
        return redirect()->back()->with('success', 'Đã cập nhật trạng thái team.');
    }
}
