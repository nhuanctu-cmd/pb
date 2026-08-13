<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\FacilityModel;
use App\Models\CourtDeviceModel;
use App\Services\FacilityService;

class FacilitiesController extends BaseController
{
    protected FacilityModel $facilityModel;
    protected BranchModel $branchModel;
    protected FacilityService $facilityService;
    protected CourtDeviceModel $deviceModel;

    public function __construct()
    {
        $this->facilityModel = new FacilityModel();
        $this->branchModel = new BranchModel();
        $this->facilityService = new FacilityService();
        $this->deviceModel = new CourtDeviceModel();
    }

    public function index()
    {
        $tenantId = current_tenant_id();

        $this->viewData['pageTitle'] = lang('App.menu_facilities');
        $this->viewData['filters'] = $this->request->getGet();
        $this->viewData['facilities'] = $tenantId
            ? $this->facilityModel->getByTenant($tenantId, $this->request->getGet())
            : [];
        $this->viewData['facilityClubs'] = [];
        foreach ($this->viewData['facilities'] as $facility) {
            $this->viewData['facilityClubs'][(int) $facility->id] = $this->facilityService->getFacilityClubs((int) $facility->id, (int) $tenantId);
        }

        return $this->render('admin/facilities/index', $this->viewData);
    }

    public function create()
    {
        $this->viewData['pageTitle'] = lang('App.facility_create');
        return $this->render('admin/facilities/form', $this->viewData);
    }

    public function store()
    {
        $tenantId = current_tenant_id();
        if (!$tenantId) {
            return redirect()->to('/admin/tenants/select')->with('warning', lang('App.forbidden'));
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

        return redirect()->to('/admin/facilities')->with('success', lang('App.created_success'));
    }

    public function edit(int $id)
    {
        $facility = $this->findTenantFacility($id);
        if (!$facility) {
            return redirect()->to('/admin/facilities')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle'] = lang('App.facility_edit');
        $this->viewData['facility'] = $facility;

        return $this->render('admin/facilities/form', $this->viewData);
    }

    public function update(int $id)
    {
        $facility = $this->findTenantFacility($id);
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

        return redirect()->to('/admin/facilities')->with('success', lang('App.updated_success'));
    }

    public function delete(int $id)
    {
        if (!$this->findTenantFacility($id)) {
            return redirect()->to('/admin/facilities')->with('error', lang('App.no_data'));
        }
        $this->facilityModel->delete($id);
        return redirect()->to('/admin/facilities')->with('success', lang('App.deleted_success'));
    }

    public function dashboard(int $id)
    {
        $facility = $this->findTenantFacility($id);
        if (!$facility) {
            return redirect()->to('/admin/facilities')->with('error', lang('App.no_data'));
        }

        $dashboard = $this->facilityService->getFacilityDashboard($id);

        return $this->render('admin/facilities/overview', [
            'pageTitle' => 'Facility Dashboard',
            'facility'  => $facility,
            'branches'  => $dashboard['branches'] ?? [],
            'facilityClubs' => $this->facilityService->getFacilityClubs($id, (int) current_tenant_id()),
            'dashboard' => $dashboard,
        ]);
    }

    public function branches(int $id)
    {
        return $this->dashboard($id);
    }

    public function clubs(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $facility = $tenantId ? $this->facilityModel->findForTenant($id, $tenantId) : null;
        if (! $facility) return redirect()->to('/admin/facilities')->with('error', lang('App.no_data'));

        return $this->render('admin/facilities/clubs', [
            'pageTitle' => 'CLB thuộc cụm sân',
            'facility' => $facility,
            'assignedClubs' => $this->facilityService->getFacilityClubs($id, $tenantId, false),
            'clubs' => $this->facilityService->getTenantClubs($tenantId),
        ]);
    }

    public function assignClub(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $startDate = trim((string) $this->request->getPost('start_date'));
        $endDate = trim((string) $this->request->getPost('end_date'));
        $revenueShareRaw = (string) $this->request->getPost('revenue_share');
        $bookingPriority = (int) $this->request->getPost('booking_priority');
        $allowedCourts = $this->request->getPost('allowed_courts');
        $allowedHours = $this->request->getPost('allowed_hours');

        $revenueShare = null;
        if ($revenueShareRaw !== '') {
            $revenueShare = is_numeric($revenueShareRaw) ? max(0, (float) $revenueShareRaw) : null;
        }

        $result = $this->facilityService->assignClubToFacility(
            $id,
            (int) $this->request->getPost('club_id'),
            $tenantId,
            (int) user_id(),
            (bool) $this->request->getPost('is_primary'),
            trim((string) $this->request->getPost('notes')) ?: null,
            $startDate ?: null,
            $endDate ?: null,
            $revenueShare,
            $bookingPriority > 0 ? $bookingPriority : 0,
            $allowedCourts,
            $allowedHours
        );
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function removeClub(int $facilityId, int $assignmentId)
    {
        $ok = $this->facilityService->removeClubFromFacility($assignmentId, (int) current_tenant_id(), (int) user_id());
        return redirect()->to('/admin/facilities/clubs/' . $facilityId)->with($ok ? 'success' : 'error', $ok ? 'Đã bỏ gán CLB khỏi cụm sân.' : 'Không thể bỏ gán CLB.');
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
        $tenantId = current_tenant_id();
        $branch = $tenantId ? $this->branchModel->findForTenant($branchId, (int) $tenantId) : null;
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
        $tenantId = current_tenant_id();
        if (!$tenantId || !$this->deviceModel->findForTenant($deviceId, (int) $tenantId)) {
            return redirect()->back()->with('error', lang('App.no_data'));
        }
        $this->facilityService->toggleDevice($deviceId);
        return redirect()->back()->with('success', lang('App.updated_success'));
    }

    private function findTenantFacility(int $id): ?object
    {
        $tenantId = current_tenant_id();
        return $tenantId ? $this->facilityModel->findForTenant($id, (int) $tenantId) : null;
    }
}
