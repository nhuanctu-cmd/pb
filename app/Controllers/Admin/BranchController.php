<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\BranchOpeningHourModel;
use App\Models\BranchHolidayModel;
use App\Models\CourtModel;

class BranchController extends BaseController
{
    protected BranchModel $branchModel;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
    }

    public function index()
    {
        $tenantId = session('tenant_id');
        $branches = $tenantId ? $this->branchModel->getByTenant($tenantId) : [];

        // Đếm số sân mỗi chi nhánh
        $courtModel = new CourtModel();
        $courtCounts = [];
        foreach ($branches as $branch) {
            $courtCounts[$branch->id] = $courtModel->where('branch_id', $branch->id)
                                                   ->where('tenant_id', $tenantId)
                                                   ->where('deleted_at', null)
                                                   ->countAllResults();
        }

        $this->viewData['pageTitle']       = lang('App.menu_branches');
        $this->viewData['pageDescription'] = lang('App.branch_subtitle');
        $this->viewData['branches']        = $branches;
        $this->viewData['courtCounts']     = $courtCounts;
        $this->viewData['pageActions']     = '<a href="' . base_url('admin/branches/create') . '" class="btn btn-primary"><i class="bi bi-plus-lg"></i> ' . lang('App.branch_create') . '</a>';

        return $this->render('admin/branches/index', $this->viewData);
    }

    public function create()
    {
        $this->viewData['pageTitle'] = lang('App.branch_create');
        $this->viewData['branch']    = null;

        return $this->render('admin/branches/form', $this->viewData);
    }

    public function store()
    {
        $tenantId = session('tenant_id');
        if (! $tenantId) {
            return redirect()->to('/admin/tenants/select')->with('warning', lang('App.forbidden'));
        }

        $rules = [
            'code'   => 'required|max_length[50]',
            'name'   => 'required|max_length[255]',
            'email'  => 'permit_empty|valid_email|max_length[255]',
            'phone'  => 'permit_empty|max_length[50]',
            'status' => 'required|in_list[active,inactive,maintenance,closed]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $id = $this->branchModel->insert($this->collectData($tenantId));
        if (! $id) {
            return redirect()->back()->withInput()->with('error', lang('App.validation_error'));
        }

        // Tạo giờ mở cửa mặc định cả tuần 06:00-22:00
        $this->seedDefaultHours((int) $id, $tenantId);

        return redirect()->to('/admin/branches')->with('success', lang('App.branch_created'));
    }

    public function edit(int $id)
    {
        $branch = $this->findTenantBranch($id);
        if (! $branch) {
            return redirect()->to('/admin/branches')->with('error', lang('App.not_found'));
        }

        $this->viewData['pageTitle'] = lang('App.branch_edit');
        $this->viewData['branch']    = $branch;

        return $this->render('admin/branches/form', $this->viewData);
    }

    public function update(int $id)
    {
        $branch = $this->findTenantBranch($id);
        if (! $branch) {
            return redirect()->to('/admin/branches')->with('error', lang('App.not_found'));
        }

        $rules = [
            'code'   => 'required|max_length[50]',
            'name'   => 'required|max_length[255]',
            'email'  => 'permit_empty|valid_email|max_length[255]',
            'phone'  => 'permit_empty|max_length[50]',
            'status' => 'required|in_list[active,inactive,maintenance,closed]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->collectData((int) $branch->tenant_id);
        $data['updated_by'] = session('userId');
        $this->branchModel->update($id, $data);

        return redirect()->to('/admin/branches')->with('success', lang('App.branch_updated'));
    }

    public function delete(int $id)
    {
        $branch = $this->findTenantBranch($id);
        if (! $branch) {
            return redirect()->to('/admin/branches')->with('error', lang('App.not_found'));
        }

        // Không cho xóa chi nhánh còn sân
        $courtCount = (new CourtModel())->where('branch_id', $id)->where('deleted_at', null)->countAllResults();
        if ($courtCount > 0) {
            return redirect()->to('/admin/branches')->with('error', lang('App.branch_delete_has_courts', [$courtCount]));
        }

        $this->branchModel->delete($id);

        return redirect()->to('/admin/branches')->with('success', lang('App.branch_deleted'));
    }

    /** Trang giờ mở cửa theo tuần */
    public function hours(int $id)
    {
        $branch = $this->findTenantBranch($id);
        if (! $branch) {
            return redirect()->to('/admin/branches')->with('error', lang('App.not_found'));
        }

        $hourModel = new BranchOpeningHourModel();
        $rows = $hourModel->where('branch_id', $id)->where('deleted_at', null)->findAll();
        $hours = [];
        foreach ($rows as $row) {
            $hours[(int) $row->day_of_week] = $row;
        }

        $this->viewData['pageTitle'] = lang('App.branch_hours') . ' — ' . $branch->name;
        $this->viewData['branch']    = $branch;
        $this->viewData['hours']     = $hours;
        $this->viewData['days']      = [
            1 => lang('App.day_mon'), 2 => lang('App.day_tue'), 3 => lang('App.day_wed'),
            4 => lang('App.day_thu'), 5 => lang('App.day_fri'), 6 => lang('App.day_sat'),
            0 => lang('App.day_sun'),
        ];

        return $this->render('admin/branches/hours', $this->viewData);
    }

    /** Lưu giờ mở cửa cả tuần (upsert theo day_of_week) */
    public function saveHours(int $id)
    {
        $branch = $this->findTenantBranch($id);
        if (! $branch) {
            return redirect()->to('/admin/branches')->with('error', lang('App.not_found'));
        }

        $hourModel = new BranchOpeningHourModel();
        $tenantId  = (int) $branch->tenant_id;

        foreach (range(0, 6) as $day) {
            $isClosed  = (int) ($this->request->getPost("hours[$day][is_closed]") ? 1 : 0);
            $openTime  = $this->request->getPost("hours[$day][open_time]") ?: null;
            $closeTime = $this->request->getPost("hours[$day][close_time]") ?: null;

            $existing = $hourModel->where('branch_id', $id)->where('day_of_week', $day)->first();

            $data = [
                'tenant_id'   => $tenantId,
                'branch_id'   => $id,
                'day_of_week' => $day,
                'open_time'   => $isClosed ? null : $openTime,
                'close_time'  => $isClosed ? null : $closeTime,
                'is_closed'   => $isClosed,
                'updated_by'  => session('userId'),
            ];

            if ($existing) {
                $hourModel->update($existing->id, $data);
            } else {
                $data['created_by'] = session('userId');
                $hourModel->insert($data);
            }
        }

        return redirect()->to('/admin/branches/hours/' . $id)->with('success', lang('App.branch_hours_saved'));
    }

    /** Trang ngày nghỉ/lễ */
    public function holidays(int $id)
    {
        $branch = $this->findTenantBranch($id);
        if (! $branch) {
            return redirect()->to('/admin/branches')->with('error', lang('App.not_found'));
        }

        $this->viewData['pageTitle'] = lang('App.branch_holidays') . ' — ' . $branch->name;
        $this->viewData['branch']    = $branch;
        $this->viewData['holidays']  = (new BranchHolidayModel())
            ->where('branch_id', $id)->where('deleted_at', null)
            ->orderBy('holiday_date', 'ASC')->findAll();

        return $this->render('admin/branches/holidays', $this->viewData);
    }

    public function storeHoliday(int $id)
    {
        $branch = $this->findTenantBranch($id);
        if (! $branch) {
            return redirect()->to('/admin/branches')->with('error', lang('App.not_found'));
        }

        $rules = [
            'holiday_date' => 'required|valid_date',
            'name_vi'      => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new BranchHolidayModel())->insert([
            'tenant_id'    => $branch->tenant_id,
            'branch_id'    => $id,
            'holiday_date' => $this->request->getPost('holiday_date'),
            'name_vi'      => $this->request->getPost('name_vi'),
            'name_en'      => $this->request->getPost('name_en'),
            'is_closed'    => 1,
            'note'         => $this->request->getPost('note'),
            'created_by'   => session('userId'),
        ]);

        return redirect()->to('/admin/branches/holidays/' . $id)->with('success', lang('App.created_success'));
    }

    public function deleteHoliday(int $id, int $holidayId)
    {
        $branch = $this->findTenantBranch($id);
        if (!$branch) {
            return redirect()->to('/admin/branches')->with('error', lang('App.not_found'));
        }

        $holidayModel = new BranchHolidayModel();
        if (!$holidayModel->findForTenant($holidayId, $id, (int) $branch->tenant_id)) {
            return redirect()->to('/admin/branches/holidays/' . $id)->with('error', lang('App.not_found'));
        }
        $holidayModel->delete($holidayId);

        return redirect()->to('/admin/branches/holidays/' . $id)->with('success', lang('App.deleted_success'));
    }

    private function findTenantBranch(int $id): ?object
    {
        $tenantId = session('tenant_id');

        return $tenantId ? $this->branchModel->findForTenant($id, (int) $tenantId) : null;
    }

    private function collectData(int $tenantId): array
    {
        $status = $this->request->getPost('status');

        return [
            'tenant_id' => $tenantId,
            'code'      => $this->request->getPost('code'),
            'name'      => $this->request->getPost('name'),
            'email'     => $this->request->getPost('email'),
            'phone'     => $this->request->getPost('phone'),
            'address'   => $this->request->getPost('address'),
            'city'      => $this->request->getPost('city'),
            'district'  => $this->request->getPost('district'),
            'is_main'   => $this->request->getPost('is_main') ? 1 : 0,
            'status'    => $status,
            'is_active' => $status === 'active' ? 1 : 0,
            'created_by' => session('userId'),
        ];
    }

    private function seedDefaultHours(int $branchId, int $tenantId): void
    {
        $hourModel = new BranchOpeningHourModel();
        foreach (range(0, 6) as $day) {
            $hourModel->insert([
                'tenant_id'   => $tenantId,
                'branch_id'   => $branchId,
                'day_of_week' => $day,
                'open_time'   => in_array($day, [0, 6], true) ? '07:00:00' : '06:00:00',
                'close_time'  => '22:00:00',
                'is_closed'   => 0,
                'created_by'  => session('userId'),
            ]);
        }
    }
}
