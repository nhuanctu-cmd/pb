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
        $this->viewData['segmentOptions'] = $this->service->segmentOptions();
        foreach ($this->viewData['campaigns'] as $campaign) {
            $campaign->recipient_count = count($this->service->recipients((int) current_tenant_id(), (int) $campaign->id));
        }
        return $this->render('admin/crm_campaigns/index', $this->viewData);
    }

    public function store()
    {
        $segment = (string) $this->request->getPost('segment');
        $segmentOptions = array_keys($this->service->segmentOptions());
        if (! in_array($segment, $segmentOptions, true)) {
            $segment = 'all';
        }
        $data = [
            'name' => trim((string) $this->request->getPost('name')),
            'channel' => $this->request->getPost('channel') ?: 'in_app',
            'segment' => $segment,
            'subject' => trim((string) $this->request->getPost('subject')),
            'message' => trim((string) $this->request->getPost('message')),
            'scheduled_at' => trim((string) $this->request->getPost('scheduled_at')),
            'throttle_per_minute' => (int) $this->request->getPost('throttle_per_minute'),
            'max_retries' => (int) $this->request->getPost('max_retries'),
        ];
        if ($data['name'] === '' || $data['message'] === '') {
            return redirect()->back()->withInput()->with('error', 'Tên chiến dịch và nội dung là bắt buộc.');
        }
        if ($data['scheduled_at'] !== '') {
            $data['scheduled_at'] = str_replace('T', ' ', $data['scheduled_at']);
        }

        $id = $this->service->createDraft((int) current_tenant_id(), $data, user_id());
        return redirect()->to('/admin/crm-campaigns')->with($id ? 'success' : 'error', $id ? 'Đã tạo chiến dịch nháp.' : 'Không thể tạo chiến dịch.');
    }

    public function launch(int $id)
    {
        $count = $this->service->launch((int) current_tenant_id(), $id, user_id());
        if ($count === -1) {
            return redirect()->to('/admin/crm-campaigns')->with('error', 'Không thể chạy chiến dịch.');
        }
        if ($count === 0) {
            return redirect()->to('/admin/crm-campaigns')->with('success', 'Chiến dịch đã được lên lịch hoặc chưa có khách phù hợp để gởi.');
        }
        return redirect()->to('/admin/crm-campaigns')->with('success', 'Đã tạo danh sách người nhận: ' . $count . ' khách.');
    }

    public function retry(int $id)
    {
        $count = $this->service->retry((int) current_tenant_id(), $id, user_id());
        return redirect()->to('/admin/crm-campaigns')->with(
            $count >= 0 ? 'success' : 'error',
            $count >= 0 ? 'Đã thử gửi lại chiến dịch cho nhóm chưa thành công.' : 'Không thể gửi lại chiến dịch.'
        );
    }

    public function dispatch()
    {
        $count = $this->service->dispatchDue((int) current_tenant_id(), user_id());
        return redirect()->to('/admin/crm-campaigns')->with(
            $count >= 0 ? 'success' : 'error',
            $count >= 0 ? "Đã xử lý {$count} mục vận chuyển CRM." : 'Không thể chạy xử lý CRM campaign.'
        );
    }

    public function sendTest(int $id)
    {
        $recipient = trim((string) $this->request->getPost('recipient'));
        $result = $this->service->sendTest((int) current_tenant_id(), $id, user_id(), $recipient);
        return redirect()->to('/admin/crm-campaigns')->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function cancel(int $id)
    {
        $ok = $this->service->cancel((int) current_tenant_id(), $id, user_id());
        return redirect()->to('/admin/crm-campaigns')->with($ok ? 'success' : 'error', $ok ? 'Đã hủy chiến dịch.' : 'Không thể hủy chiến dịch.');
    }
}
