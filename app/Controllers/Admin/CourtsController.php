<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\CourtService;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\CourtTypeModel;
use App\Models\CourtImageModel;
use App\Models\CourtMaintenanceModel;
use App\Models\ClubModel;

class CourtsController extends BaseController
{
    protected CourtService $courtService;
    protected BranchModel $branchModel;
    protected CourtModel $courtModel;
    protected CourtTypeModel $courtTypeModel;
    protected CourtImageModel $courtImageModel;
    protected CourtMaintenanceModel $maintenanceModel;
    protected ClubModel $clubModel;

    public function __construct()
    {
        $this->courtService = new CourtService();
        $this->branchModel  = new BranchModel();
        $this->courtModel   = new CourtModel();
        $this->courtTypeModel = new CourtTypeModel();
        $this->courtImageModel = new CourtImageModel();
        $this->maintenanceModel = new CourtMaintenanceModel();
        $this->clubModel = new ClubModel();
    }

    public function index()
    {
        $tenantId = session('tenant_id');
        $this->viewData['pageTitle'] = lang('Court.courts');

        $filters = $this->request->getGet();
        $branchId = $this->request->getGet('branch_id');
        $branches = $this->branchModel->getByTenant($tenantId);

        $this->viewData['branches'] = $branches;
        $this->viewData['courtTypes'] = $this->courtService->getActiveCourtTypes($tenantId);
        $this->viewData['clubs'] = $this->clubModel->getByTenant((int) $tenantId, ['status' => 'active']);
        $requestedBranch = $branchId ? $this->branchModel->findForTenant((int) $branchId, (int) $tenantId) : null;
        $this->viewData['currentBranchId'] = $requestedBranch?->id ?? ($branches[0]->id ?? null);
        $this->viewData['filters'] = $filters;

        if ($this->viewData['currentBranchId']) {
            $this->viewData['courtGrid'] = $this->courtService->getCourtGridByBranch(
                (int) $this->viewData['currentBranchId'],
                $filters
            );
        } else {
            $this->viewData['courtGrid'] = [];
        }

        return $this->render('admin/courts/index', $this->viewData);
    }

    public function create()
    {
        $tenantId = session('tenant_id');
        $this->viewData['pageTitle'] = lang('Court.create');
        $this->viewData['branches'] = $this->branchModel->getByTenant($tenantId);
        $this->viewData['courtTypes'] = $this->courtService->getActiveCourtTypes($tenantId);
        $this->viewData['clubs'] = $this->clubModel->getByTenant((int) $tenantId, ['status' => 'active']);

        return $this->render('admin/courts/form', $this->viewData);
    }

    public function store()
    {
        $tenantId = session('tenant_id');

        // SaaS: kiểm tra hạn mức sân theo gói
        if (!$tenantId) {
            return redirect()->to('/admin/tenants/select')->with('warning', lang('App.forbidden'));
        }

        $limit = (new \App\Services\TenantPlanService())->checkLimit((int) $tenantId, 'courts');
        if (! $limit['allowed']) {
            return redirect()->back()->withInput()
                ->with('error', lang('App.planLimitReached', [lang('App.plans_limit_courts'), $limit['max']]));
        }

        $branchId = (int) $this->request->getPost('branch_id');
        if (!$this->branchModel->findForTenant($branchId, (int) $tenantId)
            || !$this->courtTypeModel->where('id', (int) $this->request->getPost('court_type_id'))
                ->where('tenant_id', (int) $tenantId)->where('deleted_at', null)->first()) {
            return redirect()->back()->withInput()->with('error', lang('App.forbidden'));
        }

        $clubId = (int) $this->request->getPost('club_id');
        if ($clubId && ! $this->clubModel->findForTenant($clubId, (int) $tenantId)) {
            return redirect()->back()->withInput()->with('error', 'CLB không thuộc tenant hiện tại.');
        }

        $rules = [
            'branch_id'     => 'required|integer',
            'court_type_id' => 'required|integer',
            'code'          => 'required|max_length[50]',
            'name_vi'       => 'required|max_length[255]',
            'name_en'       => 'permit_empty|max_length[255]',
            'floor'         => 'permit_empty|integer',
            'area'          => 'permit_empty|decimal',
            'status'        => 'required|in_list[available,occupied,maintenance,inactive]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Validate unique code within branch
        $code = $this->request->getPost('code');
        if (!$this->courtService->isCodeUnique($code, $branchId)) {
            return redirect()->back()->withInput()->with('error', lang('Court.codeExists'));
        }

        $data = [
            'tenant_id'     => $tenantId,
            'club_id'       => $clubId ?: null,
            'branch_id'     => (int) $this->request->getPost('branch_id'),
            'court_type_id' => (int) $this->request->getPost('court_type_id'),
            'code'          => $code,
            'name_vi'       => $this->request->getPost('name_vi'),
            'name_en'       => $this->request->getPost('name_en'),
            'floor'         => (int) ($this->request->getPost('floor') ?: 1),
            'area'          => $this->request->getPost('area') ?: null,
            'is_indoor'     => $this->request->getPost('is_indoor') ? 1 : 0,
            'has_light'     => $this->request->getPost('has_light') ? 1 : 0,
            'has_fan'       => $this->request->getPost('has_fan') ? 1 : 0,
            'has_camera'    => $this->request->getPost('has_camera') ? 1 : 0,
            'status'        => $this->request->getPost('status'),
            'sort_order'    => (int) ($this->request->getPost('sort_order') ?: 0),
            'created_by'    => user_id(),
        ];

        $courtId = $this->courtService->createCourt($data);

        if (!$courtId) {
            return redirect()->back()->withInput()->with('error', lang('App.error'));
        }

        return redirect()->to('/admin/courts?branch_id=' . $branchId)->with('success', lang('Court.createdSuccess'));
    }

    public function edit(int $id)
    {
        $tenantId = (int) session('tenant_id');
        $court = $tenantId ? $this->courtModel->findForTenant($id, $tenantId) : null;
        if (!$court) {
            return redirect()->to('/admin/courts')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle'] = lang('Court.edit');
        $this->viewData['court'] = $court;
        $this->viewData['branches'] = $this->branchModel->getByTenant($tenantId);
        $this->viewData['courtTypes'] = $this->courtService->getActiveCourtTypes($tenantId);
        $this->viewData['clubs'] = $this->clubModel->getByTenant((int) $tenantId, ['status' => 'active']);
        $this->viewData['courtImages'] = $this->courtService->getCourtImages($id);
        $this->viewData['maintenanceRecords'] = $this->courtService->getMaintenanceByCourt($id);

        return $this->render('admin/courts/form', $this->viewData);
    }

    public function update(int $id)
    {
        $tenantId = (int) session('tenant_id');
        $court = $tenantId ? $this->courtModel->findForTenant($id, $tenantId) : null;
        if (!$court) {
            return redirect()->to('/admin/courts')->with('error', lang('App.no_data'));
        }

        $branchId = (int) ($this->request->getPost('branch_id') ?: $court->branch_id);
        if (!$this->branchModel->findForTenant($branchId, $tenantId)
            || !$this->courtTypeModel->where('id', (int) $this->request->getPost('court_type_id'))
                ->where('tenant_id', $tenantId)->where('deleted_at', null)->first()) {
            return redirect()->back()->withInput()->with('error', lang('App.forbidden'));
        }

        $clubId = (int) $this->request->getPost('club_id');
        if ($clubId && ! $this->clubModel->findForTenant($clubId, $tenantId)) {
            return redirect()->back()->withInput()->with('error', 'CLB không thuộc tenant hiện tại.');
        }

        $rules = [
            'branch_id'     => 'required|integer',
            'court_type_id' => 'required|integer',
            'code'          => 'required|max_length[50]',
            'name_vi'       => 'required|max_length[255]',
            'name_en'       => 'permit_empty|max_length[255]',
            'floor'         => 'permit_empty|integer',
            'area'          => 'permit_empty|decimal',
            'status'        => 'required|in_list[available,occupied,maintenance,inactive]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Validate unique code within branch (exclude current)
        $code = $this->request->getPost('code');
        if ($code !== $court->code && !$this->courtService->isCodeUnique($code, $branchId, $id)) {
            return redirect()->back()->withInput()->with('error', lang('Court.codeExists'));
        }

        $data = [
            'branch_id'     => (int) $this->request->getPost('branch_id'),
            'club_id'       => $clubId ?: null,
            'court_type_id' => (int) $this->request->getPost('court_type_id'),
            'code'          => $code,
            'name_vi'       => $this->request->getPost('name_vi'),
            'name_en'       => $this->request->getPost('name_en'),
            'floor'         => (int) ($this->request->getPost('floor') ?: 1),
            'area'          => $this->request->getPost('area') ?: null,
            'is_indoor'     => $this->request->getPost('is_indoor') ? 1 : 0,
            'has_light'     => $this->request->getPost('has_light') ? 1 : 0,
            'has_fan'       => $this->request->getPost('has_fan') ? 1 : 0,
            'has_camera'    => $this->request->getPost('has_camera') ? 1 : 0,
            'status'        => $this->request->getPost('status'),
            'sort_order'    => (int) ($this->request->getPost('sort_order') ?: 0),
            'updated_by'    => user_id(),
        ];

        if ($this->courtService->updateCourt($id, $data)) {
            return redirect()->to('/admin/courts?branch_id=' . $data['branch_id'])->with('success', lang('Court.updatedSuccess'));
        }

        return redirect()->back()->withInput()->with('error', lang('App.error'));
    }

    public function delete(int $id)
    {
        $tenantId = (int) session('tenant_id');
        $court = $tenantId ? $this->courtModel->findForTenant($id, $tenantId) : null;
        if (!$court) {
            return redirect()->to('/admin/courts')->with('error', lang('App.no_data'));
        }

        if ($this->courtService->deleteCourt($id)) {
            return redirect()->to('/admin/courts?branch_id=' . $court->branch_id)->with('success', lang('Court.deletedSuccess'));
        }

        return redirect()->back()->with('error', lang('Court.hasBookings'));
    }

    public function status(int $id)
    {
        $tenantId = (int) session('tenant_id');
        $court = $tenantId ? $this->courtModel->findForTenant($id, $tenantId) : null;
        if (!$court) {
            return $this->response->setJSON(['success' => false, 'message' => lang('App.no_data')]);
        }

        $status = $this->request->getPost('status');
        if (!$status) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing status']);
        }

        if ($this->courtService->changeCourtStatus($id, $status)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('App.error')]);
    }

    public function uploadImage(int $id)
    {
        $tenantId = (int) session('tenant_id');
        $court = $tenantId ? $this->courtModel->findForTenant($id, $tenantId) : null;
        if (!$court) {
            return $this->response->setJSON(['success' => false, 'message' => lang('App.no_data')]);
        }

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'No file uploaded']);
        }

        // Validate image
        if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid file type']);
        }

        $newName = $file->getRandomName();
        $uploadPath = FCPATH . 'uploads/courts/' . $id;

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if ($file->move($uploadPath, $newName)) {
            $filePath = 'uploads/courts/' . $id . '/' . $newName;

            $imageId = $this->courtService->uploadImage($id, [
                'tenant_id'  => $court->tenant_id,
                'file_path'  => $filePath,
                'created_by' => user_id(),
            ]);

            if ($imageId) {
                return $this->response->setJSON(['success' => true, 'id' => $imageId, 'path' => base_url($filePath)]);
            }
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('App.error')]);
    }

    public function deleteImage(int $id)
    {
        $tenantId = (int) session('tenant_id');
        if (!$tenantId || !$this->courtImageModel->findForTenant($id, $tenantId)) {
            return $this->response->setJSON(['success' => false]);
        }
        if ($this->courtService->deleteImage($id)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function setPrimaryImage(int $courtId, int $imageId)
    {
        $tenantId = (int) session('tenant_id');
        $court = $tenantId ? $this->courtModel->findForTenant($courtId, $tenantId) : null;
        $image = $tenantId ? $this->courtImageModel->findForTenant($imageId, $tenantId) : null;
        if (!$court || !$image || (int) $image->court_id !== $courtId) {
            return $this->response->setJSON(['success' => false]);
        }
        if ($this->courtService->setPrimaryImage($courtId, $imageId)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function maintenance(int $id)
    {
        $tenantId = (int) session('tenant_id');
        $court = $tenantId ? $this->courtModel->findForTenant($id, $tenantId) : null;
        if (!$court) {
            return redirect()->to('/admin/courts')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle'] = lang('Court.scheduleMaintenance');
        $this->viewData['court'] = $court;
        $this->viewData['maintenanceRecords'] = $this->courtService->getMaintenanceByCourt($id);

        return $this->render('admin/courts/maintenance', $this->viewData);
    }

    public function storeMaintenance(int $courtId)
    {
        $tenantId = (int) session('tenant_id');
        $court = $tenantId ? $this->courtModel->findForTenant($courtId, $tenantId) : null;
        if (!$court) {
            return redirect()->back()->with('error', lang('App.no_data'));
        }

        $rules = [
            'start_time' => 'required|valid_date',
            'end_time'   => 'permit_empty|valid_date',
            'reason'     => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $startTime = $this->request->getPost('start_time');
        $endTime = $this->request->getPost('end_time');

        // Check conflict
        if ($this->courtService->checkCourtConflict($courtId, $startTime, $endTime)) {
            return redirect()->back()->withInput()->with('error', lang('Court.maintenanceConflict'));
        }

        $data = [
            'tenant_id'  => $court->tenant_id,
            'branch_id'  => $court->branch_id,
            'court_id'   => $courtId,
            'start_time' => $startTime,
            'end_time'   => $endTime,
            'reason'     => $this->request->getPost('reason'),
            'status'     => 'scheduled',
            'created_by' => user_id(),
        ];

        $maintenanceId = $this->courtService->scheduleMaintenance($data);

        if ($maintenanceId) {
            return redirect()->to('/admin/courts/maintenance/' . $courtId)->with('success', lang('Court.maintenanceCreated'));
        }

        return redirect()->back()->withInput()->with('error', lang('App.error'));
    }

    public function updateMaintenanceStatus(int $id)
    {
        $tenantId = (int) session('tenant_id');
        if (!$tenantId || !$this->maintenanceModel->findForTenant($id, $tenantId)) {
            return $this->response->setJSON(['success' => false]);
        }
        $status = $this->request->getPost('status');
        if (!in_array($status, ['scheduled', 'doing', 'completed', 'cancelled'])) {
            return $this->response->setJSON(['success' => false]);
        }

        $data = ['status' => $status, 'updated_by' => user_id()];

        if ($this->courtService->updateMaintenance($id, $data)) {
            return $this->response->setJSON(['success' => true]);
        }

        return $this->response->setJSON(['success' => false]);
    }

    public function calendar()
    {
        $tenantId = session('tenant_id');
        $this->viewData['pageTitle'] = lang('Court.calendar');
        $this->viewData['branches'] = $this->branchModel->getByTenant($tenantId);

        $branchId = $this->request->getGet('branch_id');
        if ($branchId && $this->branchModel->findForTenant((int) $branchId, (int) $tenantId)) {
            $this->viewData['currentBranchId'] = (int) $branchId;
            $this->viewData['courts'] = $this->courtService->getCourtGridByBranch((int) $branchId);
        } else {
            $this->viewData['currentBranchId'] = null;
            $this->viewData['courts'] = [];
        }

        return $this->render('admin/courts/calendar', $this->viewData);
    }
}
