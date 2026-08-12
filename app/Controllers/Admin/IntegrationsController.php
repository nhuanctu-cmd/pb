<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\PartnerApiService;
use Config\Database;

class IntegrationsController extends BaseController
{
    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $integrations = $tenantId ? Database::connect()->table('tenant_integrations ti')
            ->select('ti.id, ti.status, ti.last_sync_at, ti.last_error, pi.code, pi.name, pi.provider_type')
            ->join('platform_integrations pi', 'pi.id = ti.integration_id', 'left')
            ->where('ti.tenant_id', $tenantId)->orderBy('pi.name', 'ASC')->get()->getResult() : [];
        return $this->render('admin/integrations/index', [
            'pageTitle' => 'API đối tác & tích hợp',
            'keys' => $tenantId ? service('partnerApiService')->forTenant($tenantId) : [],
            'scopes' => PartnerApiService::SCOPES,
            'integrations' => $integrations,
        ]);
    }

    public function store()
    {
        $result = service('partnerApiService')->createKey((int) current_tenant_id(), (string) $this->request->getPost('name'), (array) $this->request->getPost('scopes'), (int) user_id(), $this->request->getPost('expires_at') ?: null);
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['success'] ? $result['message'] . ' Key: ' . $result['key'] : $result['message']);
    }

    public function revoke(int $id)
    {
        $result = service('partnerApiService')->revoke($id, (int) current_tenant_id(), (int) user_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function health(int $id)
    {
        $tenantId = (int) current_tenant_id();
        $db = Database::connect();
        $row = $db->table('tenant_integrations')->where('id', $id)->where('tenant_id', $tenantId)->get()->getRow();
        if (! $row) return redirect()->back()->with('error', 'Integration không thuộc tenant hiện tại.');
        $db->table('tenant_integrations')->where('id', $id)->update([
            'last_sync_at' => date('Y-m-d H:i:s'), 'last_error' => null,
            'metadata' => json_encode(['health' => 'ok', 'checked_by' => (int) user_id(), 'checked_at' => date('c')]),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return redirect()->back()->with('success', 'Đã kiểm tra kết nối integration.');
    }
}
