<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class WebhookController extends BaseController
{
    private $service;

    public function __construct() { $this->service = service('webhookService'); }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        return $this->render('admin/webhooks/index', [
            'pageTitle' => lang('App.menu_webhooks'),
            'endpoints' => $tenantId ? $this->service->endpoints($tenantId) : [],
            'eventTypes' => \App\Services\WebhookService::EVENT_TYPES,
        ]);
    }

    public function store()
    {
        $result = $this->service->createEndpoint($this->request->getPost(), (int) current_tenant_id(), (int) user_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function status(int $id)
    {
        $result = $this->service->updateEndpointStatus($id, (string) $this->request->getPost('status'), (int) current_tenant_id(), (int) user_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
