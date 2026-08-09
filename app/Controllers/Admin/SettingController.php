<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\SettingService;

class SettingController extends BaseController
{
    protected SettingService $settingService;

    public function __construct()
    {
        $this->settingService = new SettingService();
    }

    public function index()
    {
        $group = $this->request->getGet('group') ?: 'general';

        $this->viewData['pageTitle']       = lang('App.menu_settings');
        $this->viewData['pageDescription'] = lang('App.settings_subtitle');
        $this->viewData['currentGroup']    = $group;
        $this->viewData['groups']          = $this->getGroups();
        $this->viewData['settings']        = $this->settingService->getGroup($group);

        return $this->render('admin/settings/index', $this->viewData);
    }

    public function update()
    {
        $group    = $this->request->getPost('group') ?: 'general';
        $settings = (array) $this->request->getPost('settings');
        $tenantId = current_tenant_id();

        if (! $tenantId) {
            return redirect()->back()->with('error', lang('App.forbidden'));
        }

        $this->settingService->setGroup($group, $settings, $tenantId);

        log_audit([
            'table' => 'settings',
            'data'  => ['group' => $group, 'count' => count($settings)],
        ]);

        return redirect()->to('/admin/settings?group=' . $group)
                         ->with('success', lang('App.updated_success'));
    }

    private function getGroups(): array
    {
        return [
            'general'      => lang('App.settings_group_general'),
            'booking'      => lang('App.settings_group_booking'),
            'payment'      => lang('App.settings_group_payment'),
            'notifications'=> lang('App.settings_group_notifications'),
            'business'     => lang('App.settings_group_business'),
        ];
    }
}
