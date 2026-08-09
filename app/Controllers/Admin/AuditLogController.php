<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;

class AuditLogController extends BaseController
{
    public function index()
    {
        $model = new AuditLogModel();

        return $this->render('admin/system/audit_logs', [
            'pageTitle' => 'Audit Logs',
            'logs'      => $model->orderBy('created_at', 'DESC')->limit(100)->findAll(),
        ]);
    }
}
