<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\TenantService;

class TenantController extends BaseController
{
    protected TenantService $tenantService;

    public function __construct()
    {
        $this->tenantService = new TenantService();
    }

    public function index()
    {
        $this->viewData['pageTitle'] = lang('Tenant.tenants');
        $this->viewData['tenants'] = $this->tenantService->getAll($this->request->getGet());
        $this->viewData['pager'] = \Config\Services::pager();

        return $this->render('admin/tenants/index', $this->viewData);
    }

    public function create()
    {
        $this->viewData['pageTitle'] = lang('Tenant.create');
        return $this->render('admin/tenants/create', $this->viewData);
    }

    public function store()
    {
        $rules = [
            'code'    => 'required|is_unique[tenants.code]|min_length[2]|max_length[50]',
            'name'    => 'required|max_length[255]',
            'email'   => 'permit_empty|valid_email|max_length[255]',
            'phone'   => 'permit_empty|max_length[50]',
            'status'  => 'required|in_list[active,inactive,suspended]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost(['code', 'name', 'email', 'phone', 'address', 'status']);
        $data['created_by'] = user_id();

        $tenantId = $this->tenantService->create($data);

        if (!$tenantId) {
            return redirect()->back()->withInput()->with('error', lang('App.error'));
        }

        return redirect()->to('/admin/tenants')->with('success', lang('Tenant.createdSuccess'));
    }

    public function edit(int $id)
    {
        $tenant = $this->tenantService->getById($id);
        if (!$tenant) {
            return redirect()->to('/admin/tenants')->with('error', lang('App.no_data'));
        }

        $this->viewData['pageTitle'] = lang('Tenant.edit');
        $this->viewData['tenant'] = $tenant;
        return $this->render('admin/tenants/edit', $this->viewData);
    }

    public function update(int $id)
    {
        $rules = [
            'code'    => 'required|is_unique[tenants.code,id,' . $id . ']|min_length[2]|max_length[50]',
            'name'    => 'required|max_length[255]',
            'email'   => 'permit_empty|valid_email|max_length[255]',
            'phone'   => 'permit_empty|max_length[50]',
            'status'  => 'required|in_list[active,inactive,suspended]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost(['code', 'name', 'email', 'phone', 'address', 'status']);
        $data['updated_by'] = user_id();

        if ($this->tenantService->update($id, $data)) {
            return redirect()->to('/admin/tenants')->with('success', lang('Tenant.updatedSuccess'));
        }

        return redirect()->back()->withInput()->with('error', lang('App.error'));
    }

    public function delete(int $id)
    {
        if ($this->tenantService->delete($id)) {
            return redirect()->to('/admin/tenants')->with('success', lang('Tenant.deletedSuccess'));
        }

        return redirect()->back()->with('error', lang('App.error'));
    }

    public function select()
    {
        if (is_superadmin()) {
            $this->viewData['tenants'] = $this->tenantService->getActiveTenants();
        } else {
            $this->viewData['tenants'] = $this->tenantService->getActiveTenants();
        }
        $this->viewData['pageTitle'] = lang('Tenant.selectRequired');

        return $this->render('admin/tenants/select', $this->viewData);
    }

    public function setSession(int $id)
    {
        $tenant = $this->tenantService->getById($id);
        if (!$tenant) {
            return redirect()->back()->with('error', lang('App.no_data'));
        }

        session()->set('tenant_id', $tenant->id);
        session()->set('tenant_code', $tenant->code);
        session()->set('tenant_name', $tenant->name);

        $branches = $this->tenantService->getBranches($id);
        if (!empty($branches)) {
            session()->set('branch_id', $branches[0]->id);
        }

        return redirect()->to('/admin/dashboard');
    }
}
