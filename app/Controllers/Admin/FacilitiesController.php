<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\FacilityModel;
use App\Services\FacilityService;

class FacilitiesController extends BaseController
{
    protected FacilityModel $facilityModel;
    protected BranchModel $branchModel;
    protected FacilityService $facilityService;

    public function __construct()
    {
        $this->facilityModel = new FacilityModel();
        $this->branchModel = new BranchModel();
        $this->facilityService = new FacilityService();
    }

    public function index()
    {
        $tenantId = current_tenant_id();

        $this->viewData['pageTitle'] = 'Facilities';
        $this->viewData['filters'] = $this->request->getGet();
        $this->viewData['facilities'] = $tenantId
            ? $this->facilityModel->getByTenant($tenantId, $this->request->getGet())
            : [];

        return $this->render('admin/facilities/index', $this->viewData);
    }

    public function create()
    {
        $this->viewData['pageTitle'] = 'Create Facility';
        return $this->render('admin/facilities/form', $this->viewData);
    }

    public function store()
    {
        $tenantId = current_tenant_id();
        if (!$tenantId) {
            return redirect()->to('/admin/tenants/select')->with('warning', 'Please select a tenant first.');
        }

        $rules = [
            'code'    => 'required|max_length[50]',
            'name_vi' => 'required|max_length[255]',
            'status'  => 'required|in_list[active,inactive,suspended]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->facilityModel->insert([
            'tenant_id'      => $tenantId,
            'code'           => $this->request->getPost('code'),
            'name_vi'        => $this->request->getPost('name_vi'),
            'name_en'        => $this->request->getPost('name_en'),
            'address'        => $this->request->getPost('address'),
            'phone'          => $this->request->getPost('phone'),
            'email'          => $this->request->getPost('email'),
            'status'         => $this->request->getPost('status'),
            'is_active'      => $this->request->getPost('status') === 'active' ? 1 : 0,
            'created_by'     => user_id(),
        ]);

        return redirect()->to('/admin/facilities')->with('success', 'Facility created successfully.');
    }

    public function edit(int $id)
    {
        $facility = $this->facilityModel->find($id);
        if (!$facility) {
            return redirect()->to('/admin/facilities')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle'] = 'Edit Facility';
        $this->viewData['facility'] = $facility;

        return $this->render('admin/facilities/form', $this->viewData);
    }

    public function update(int $id)
    {
        $facility = $this->facilityModel->find($id);
        if (!$facility) {
            return redirect()->to('/admin/facilities')->with('error', lang('App.no_data'));
        }

        $rules = [
            'code'    => 'required|max_length[50]',
            'name_vi' => 'required|max_length[255]',
            'status'  => 'required|in_list[active,inactive,suspended]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->facilityModel->update($id, [
            'code'       => $this->request->getPost('code'),
            'name_vi'    => $this->request->getPost('name_vi'),
            'name_en'    => $this->request->getPost('name_en'),
            'address'    => $this->request->getPost('address'),
            'phone'      => $this->request->getPost('phone'),
            'email'      => $this->request->getPost('email'),
            'status'     => $this->request->getPost('status'),
            'is_active'  => $this->request->getPost('status') === 'active' ? 1 : 0,
            'updated_by' => user_id(),
        ]);

        return redirect()->to('/admin/facilities')->with('success', 'Facility updated successfully.');
    }

    public function delete(int $id)
    {
        $this->facilityModel->delete($id);
        return redirect()->to('/admin/facilities')->with('success', 'Facility deleted successfully.');
    }

    public function dashboard(int $id)
    {
        $facility = $this->facilityModel->find($id);
        if (!$facility) {
            return redirect()->to('/admin/facilities')->with('error', lang('App.no_data'));
        }

        $dashboard = $this->facilityService->getFacilityDashboard($id);

        return $this->render('admin/facilities/overview', [
            'pageTitle' => 'Facility Dashboard',
            'facility'  => $facility,
            'branches'  => $dashboard['branches'] ?? [],
            'dashboard' => $dashboard,
        ]);
    }

    public function branches(int $id)
    {
        return $this->dashboard($id);
    }

    public function courtGrid(int $branchId)
    {
        return redirect()->to('/admin/courts?branch_id=' . $branchId);
    }

    public function courtTimeline(int $branchId)
    {
        return redirect()->to('/admin/courts/calendar?branch_id=' . $branchId);
    }

    public function realtimeStatus(int $branchId)
    {
        return redirect()->to('/admin/courts/grid/' . $branchId);
    }

    public function report(int $branchId)
    {
        return redirect()->to('/admin/dashboard');
    }

    public function devices(int $branchId)
    {
        $branch = $this->branchModel->find($branchId);
        if (!$branch) {
            return redirect()->to('/admin/facilities')->with('error', lang('App.no_data'));
        }

        return $this->render('admin/facilities/dashboard', [
            'pageTitle' => 'Court Devices',
            'title'     => 'Court Devices',
            'branch'    => $branch,
            'devices'   => $this->facilityService->getDevicesByBranch($branchId),
        ]);
    }

    public function toggleDevice(int $deviceId)
    {
        $this->facilityService->toggleDevice($deviceId);
        return redirect()->back()->with('success', 'Device updated successfully.');
    }
}
