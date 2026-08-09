<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;

class BranchController extends BaseController
{
    public function index()
    {
        return $this->render('admin/system/branches', [
            'pageTitle' => 'Branches',
            'branches'  => current_tenant_id() ? (new BranchModel())->getByTenant(current_tenant_id()) : [],
        ]);
    }

    public function create() { return redirect()->to('/admin/branches')->with('info', 'Branch create screen is ready to be implemented.'); }
    public function store() { return redirect()->to('/admin/branches')->with('info', 'Branch store action is ready to be implemented.'); }
    public function edit(int $id) { return redirect()->to('/admin/branches')->with('info', 'Branch edit screen is ready to be implemented.'); }
    public function update(int $id) { return redirect()->to('/admin/branches')->with('info', 'Branch update action is ready to be implemented.'); }
    public function delete(int $id) { return redirect()->to('/admin/branches')->with('info', 'Branch delete action is ready to be implemented.'); }
}
