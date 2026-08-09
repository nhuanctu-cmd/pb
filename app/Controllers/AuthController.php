<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\TenantModel;
use App\Services\AuditLogService;

class AuthController extends BaseController
{
    protected UserModel $userModel;
    protected AuditLogService $auditLogService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->auditLogService = new AuditLogService();
    }

    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/admin/dashboard');
        }

        return $this->render('public/login', [
            'pageTitle' => lang('Auth.loginTitle'),
        ]);
    }

    public function loginPost()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            return redirect()->back()
                ->withInput()
                ->with('error', lang('Auth.invalidCredentials'));
        }

        if (!$user->is_active) {
            return redirect()->back()
                ->with('error', lang('Auth.accountInactive'));
        }

        if ($user->status !== 'active') {
            return redirect()->back()
                ->with('error', lang('Auth.accountSuspended'));
        }

        // Set session
        $tenantId = $user->tenant_id;
        $branchId = $user->branch_id;
        $tenantName = null;
        $tenantModel = new TenantModel();

        if (empty($tenantId)) {
            $tenant = $tenantModel->where('status', 'active')->orderBy('id', 'ASC')->first();
            $tenantId = $tenant->id ?? null;
        } else {
            $tenant = $tenantModel->find($tenantId);
        }

        if (!empty($tenant)) {
            $tenantName = $tenant->name ?? null;
        }

        if (empty($branchId) && !empty($tenantId)) {
            $branch = model(\App\Models\BranchModel::class)->where('tenant_id', $tenantId)->orderBy('is_main', 'DESC')->first();
            $branchId = $branch->id ?? null;
        }

        session()->set([
            'userId'        => $user->id,
            'user_id'       => $user->id,
            'username'      => $user->username,
            'email'         => $user->email,
            'fullName'      => $user->first_name . ' ' . $user->last_name,
            'avatar'        => $user->avatar,
            'isLoggedIn'    => true,
            'is_superadmin' => (bool) $user->is_superadmin,
            'tenant_id'     => $tenantId,
            'tenant_name'   => $tenantName,
            'branch_id'     => $branchId,
            'locale'        => session()->get('locale') ?? 'en',
        ]);

        // Log login
        $this->auditLogService->log(
            'login', 'auth', 'users', $user->id,
            null, null, 'User logged in',
            $user->tenant_id, $user->branch_id, $user->id
        );

        // Update last login
        $this->userModel->update($user->id, [
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip'    => $this->request->getIPAddress(),
        ]);

        session()->setFlashdata('success', lang('Auth.loginSuccess'));

        return redirect()->to('/admin/dashboard');
    }

    public function logout()
    {
        $userId = user_id();
        if ($userId) {
            $this->auditLogService->log(
                'logout', 'auth', 'users', $userId,
                null, null, 'User logged out'
            );
        }

        session()->destroy();
        return redirect()->to('/login')
            ->with('success', lang('Auth.logoutSuccess'));
    }

    public function switchLocale(string $locale)
    {
        if (!in_array($locale, ['en', 'vi'])) {
            $locale = 'en';
        }

        session()->set('locale', $locale);
        service('language')->setLocale($locale);

        return redirect()->back();
    }
}
