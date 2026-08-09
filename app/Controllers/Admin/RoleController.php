<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RoleModel;

class RoleController extends BaseController
{
    public function index()
    {
        return $this->render('admin/system/roles', [
            'pageTitle' => 'Roles',
            'roles'     => (new RoleModel())->getByTenant(current_tenant_id()),
        ]);
    }

    public function create() { return redirect()->to('/admin/roles')->with('info', 'Role create screen is ready to be implemented.'); }
    public function store() { return redirect()->to('/admin/roles')->with('info', 'Role store action is ready to be implemented.'); }
    public function edit(int $id) { return redirect()->to('/admin/roles')->with('info', 'Role edit screen is ready to be implemented.'); }
    public function update(int $id) { return redirect()->to('/admin/roles')->with('info', 'Role update action is ready to be implemented.'); }
    public function delete(int $id) { return redirect()->to('/admin/roles')->with('info', 'Role delete action is ready to be implemented.'); }
    public function permissions(int $id) { return redirect()->to('/admin/roles')->with('info', 'Permission screen is ready to be implemented.'); }
    public function updatePermissions(int $id) { return redirect()->to('/admin/roles')->with('info', 'Permission update action is ready to be implemented.'); }
}
