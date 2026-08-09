<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class SettingController extends BaseController
{
    public function index()
    {
        $model = new SettingModel();

        return $this->render('admin/system/settings', [
            'pageTitle' => 'Settings',
            'settings'  => $model->where('tenant_id', current_tenant_id())->orWhere('tenant_id', null)->findAll(),
        ]);
    }

    public function update()
    {
        $model = new SettingModel();
        foreach ((array) $this->request->getPost('settings') as $key => $value) {
            $model->setSetting($key, $value, current_tenant_id());
        }

        return redirect()->to('/admin/settings')->with('success', 'Settings updated successfully.');
    }
}
