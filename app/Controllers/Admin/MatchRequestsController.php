<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\MatchRequestModel;
use App\Models\SocialMatchModel;
use App\Models\SocialMatchPlayerModel;

class MatchRequestsController extends BaseController
{
    public function index()
    {
        $tenantId = (int) session('tenant_id');
        return $this->render('admin/matches/index', [
            'pageTitle' => 'Quản lý kèo mở',
            'requests' => model(MatchRequestModel::class)->getOpen($tenantId, [
                'status' => $this->request->getGet('status'),
                'branch_id' => $this->request->getGet('branch_id'),
                'preferred_date' => $this->request->getGet('date'),
            ]),
            'matches' => model(SocialMatchModel::class)->getByTenant($tenantId),
            'branches' => model(BranchModel::class)->getByTenant($tenantId),
        ]);
    }

    public function show($id)
    {
        $request = model(MatchRequestModel::class)->find($id);
        return $this->render('admin/matches/show', [
            'pageTitle' => 'Chi tiết kèo',
            'request' => $request,
            'suggestedPlayers' => service('matchingService')->findCompatiblePlayers((int) $id),
        ]);
    }

    public function approve($id)
    {
        $result = service('matchingService')->autoMatch((int) $id);
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function cancel($id)
    {
        model(MatchRequestModel::class)->update($id, ['status' => 'cancelled']);
        return redirect()->back()->with('success', 'Đã cancel kèo.');
    }

    public function convert($socialMatchId)
    {
        $result = service('matchingService')->convertToBooking((int) $socialMatchId, (int) session('user_id'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
