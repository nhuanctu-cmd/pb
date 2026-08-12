<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\CrmCampaignService;

class CrmCampaignsController extends BaseController
{
    private CrmCampaignService $service;

    public function __construct()
    {
        $this->service = new CrmCampaignService();
    }

    public function index()
    {
        $this->viewData['pageTitle'] = 'CRM Campaign · Chăm sóc khách hàng';
        $this->viewData['campaigns'] = $this->service->list((int) current_tenant_id());
        return $this->render('admin/crm_campaigns/index', $this->viewData);
    }

    public function store()
    {
        $data = [
            'name' => trim((string) $this->request->getPost('name')),
            'channel' => $this->request->getPost('channel') ?: 'in_app',
            'segment' => $this->request->getPost('segment') ?: 'all',
            'subject' => trim((string) $this->request->getPost('subject')),
            'message' => trim((string) $this->request->getPost('message')),
        ];
        if ($data['name'] === '' || $data['message'] === '') return redirect()->back()->withInput()->with('error', 'Tên chiến dịch và nội dung là bắt buộc.');
        $id = $this->service->createDraft((int) current_tenant_id(), $data, user_id());
        return redirect()->to('/admin/crm-campaigns')->with($id ? 'success' : 'error', $id ? 'Đã tạo chiến dịch nháp.' : 'Không thể tạo chiến dịch.');
    }

    public function launch(int $id)
    {
        $count = $this->service->launch((int) current_tenant_id(), $id, user_id());
        return redirect()->to('/admin/crm-campaigns')->with($count >= 0 ? 'success' : 'error', $count >= 0 ? 'Đã tạo danh sách người nhận: ' . $count . ' khách.' : 'Không tìm thấy chiến dịch.');
    }
}
