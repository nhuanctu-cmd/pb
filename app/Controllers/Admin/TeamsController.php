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
        $team = model(TeamModel::class)->find($id);
        return $this->render('admin/teams/show', [
            'pageTitle' => $team ? $team->team_name : 'Team',
            'team' => $team,
            'members' => model(TeamMemberModel::class)->getByTeam((int) $id),
        ]);
    }

    public function status($id)
    {
        model(TeamModel::class)->update($id, ['status' => $this->request->getPost('status') ?: 'inactive']);
        return redirect()->back()->with('success', 'Đã cập nhật trạng thái team.');
    }
}
