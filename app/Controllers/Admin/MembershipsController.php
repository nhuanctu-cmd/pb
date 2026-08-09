<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\MembershipService;
use App\Services\PlayerService;

class MembershipsController extends BaseController
{
    protected MembershipService $membershipService;
    protected PlayerService $playerService;

    public function __construct()
    {
        $this->membershipService = new MembershipService();
        $this->playerService     = new PlayerService();
    }

    public function index()
    {
        $tenantId = current_tenant_id();
        $filters  = $this->request->getGet();

        $this->viewData['pageTitle']   = lang('App.memberships');
        $this->viewData['memberships'] = $this->membershipService->getMemberships($tenantId, $filters);
        $this->viewData['filters']     = $filters;
        $this->viewData['pager']       = model(\App\Models\MembershipModel::class)->pager;

        return $this->render('admin/memberships/index', $this->viewData);
    }

    public function create()
    {
        $tenantId = current_tenant_id();
        $this->viewData['pageTitle'] = lang('App.create_membership');
        $this->viewData['packages']  = $this->membershipService->getAllPackages($tenantId);
        $this->viewData['players']   = $this->playerService->getPlayers($tenantId, ['status' => 'active']);

        return $this->render('admin/memberships/form', $this->viewData);
    }

    public function store()
    {
        $tenantId = current_tenant_id();

        $rules = [
            'player_id'  => 'required|integer',
            'package_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $playerId  = (int) $this->request->getPost('player_id');
        $packageId = (int) $this->request->getPost('package_id');

        $membershipId = $this->membershipService->buyPackage($playerId, $packageId, $tenantId, user_id());
        if (!$membershipId) {
            return redirect()->back()->withInput()->with('error', lang('App.error'));
        }

        return redirect()->to('/admin/memberships')->with('success', lang('App.membership_created'));
    }

    public function cancel(int $id)
    {
        if ($this->membershipService->cancel($id, (int) current_tenant_id())) {
            return redirect()->to('/admin/memberships')->with('success', lang('App.membership_cancelled'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }

    public function packages()
    {
        $tenantId = current_tenant_id();
        $this->viewData['pageTitle'] = lang('App.membership_packages');
        $this->viewData['packages']  = $this->membershipService->getAllPackages($tenantId);

        return $this->render('admin/memberships/packages', $this->viewData);
    }

    public function createPackage()
    {
        $this->viewData['pageTitle'] = lang('App.create_package');
        return $this->render('admin/memberships/package_form', $this->viewData);
    }

    public function storePackage()
    {
        $tenantId = current_tenant_id();

        $rules = [
            'name_vi'       => 'required|max_length[255]',
            'duration_days' => 'required|integer|greater_than[0]',
            'price'         => 'required|decimal|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'tenant_id'        => $tenantId,
            'name_vi'          => $this->request->getPost('name_vi'),
            'name_en'          => $this->request->getPost('name_en'),
            'duration_days'    => (int) $this->request->getPost('duration_days'),
            'price'            => (float) $this->request->getPost('price'),
            'discount_percent' => (float) ($this->request->getPost('discount_percent') ?: 0),
            'booking_priority' => (int) ($this->request->getPost('booking_priority') ?: 0),
            'status'           => $this->request->getPost('status') ?: 'active',
            'created_by'       => user_id(),
        ];

        if ($this->membershipService->createPackage($data)) {
            return redirect()->to('/admin/memberships/packages')->with('success', lang('App.package_created'));
        }

        return redirect()->back()->withInput()->with('error', lang('App.error'));
    }

    public function editPackage(int $id)
    {
        $package = $this->membershipService->getPackageById($id, (int) current_tenant_id());
        if (!$package) {
            return redirect()->to('/admin/memberships/packages')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle'] = lang('App.edit_package');
        $this->viewData['package']   = $package;

        return $this->render('admin/memberships/package_form', $this->viewData);
    }

    public function updatePackage(int $id)
    {
        $package = $this->membershipService->getPackageById($id, (int) current_tenant_id());
        if (!$package) {
            return redirect()->to('/admin/memberships/packages')->with('error', lang('App.no_data'));
        }

        $rules = [
            'name_vi'       => 'required|max_length[255]',
            'duration_days' => 'required|integer|greater_than[0]',
            'price'         => 'required|decimal|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name_vi'          => $this->request->getPost('name_vi'),
            'name_en'          => $this->request->getPost('name_en'),
            'duration_days'    => (int) $this->request->getPost('duration_days'),
            'price'            => (float) $this->request->getPost('price'),
            'discount_percent' => (float) ($this->request->getPost('discount_percent') ?: 0),
            'booking_priority' => (int) ($this->request->getPost('booking_priority') ?: 0),
            'status'           => $this->request->getPost('status'),
            'updated_by'       => user_id(),
        ];

        if ($this->membershipService->updatePackage($id, $data, (int) current_tenant_id())) {
            return redirect()->to('/admin/memberships/packages')->with('success', lang('App.package_updated'));
        }

        return redirect()->back()->withInput()->with('error', lang('App.error'));
    }

    public function deletePackage(int $id)
    {
        if ($this->membershipService->deletePackage($id, (int) current_tenant_id())) {
            return redirect()->to('/admin/memberships/packages')->with('success', lang('App.package_deleted'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }
}
