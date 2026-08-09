<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class AuditLogController extends BaseController
{
    public function index()
    {
        $filters = $this->request->getGet();
        $tenantId = current_tenant_id();

        $model = new AuditLogModel();
        $query = $model->orderBy('created_at', 'DESC');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }
        if (! empty($filters['from'])) {
            $query->where('created_at >=', $filters['from'] . ' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $query->where('created_at <=', $filters['to'] . ' 23:59:59');
        }

        $this->viewData['pageTitle']       = lang('App.menu_audit_logs');
        $this->viewData['pageDescription'] = lang('App.audit_subtitle');
        $this->viewData['logs']            = $query->paginate(50);
        $this->viewData['pager']           = $model->pager;
        $this->viewData['filters']         = $filters;
        $this->viewData['modules']         = $this->getModules();
        $this->viewData['actions']         = ['create', 'update', 'delete', 'login', 'logout', 'payment', 'view'];
        $this->viewData['users']           = (new UserModel())->where('tenant_id', $tenantId)->findAll();

        return $this->render('admin/audit_logs/index', $this->viewData);
    }

    private function getModules(): array
    {
        return [
            'system', 'auth', 'booking', 'payment', 'court', 'player',
            'membership', 'tournament', 'pos', 'user', 'setting', 'media',
        ];
    }
}
