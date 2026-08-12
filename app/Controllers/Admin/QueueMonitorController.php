<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class QueueMonitorController extends BaseController
{
    public function index()
    {
        return $this->render('admin/queue/index', ['pageTitle' => 'Queue Monitoring', 'report' => service('queueMonitorService')->report((int) current_tenant_id())]);
    }

    public function retry(int $id)
    {
        $result = service('queueMonitorService')->retry($id, (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function deadLetter(int $id)
    {
        $result = service('queueMonitorService')->deadLetter($id, (int) current_tenant_id(), (string) $this->request->getPost('reason'));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
