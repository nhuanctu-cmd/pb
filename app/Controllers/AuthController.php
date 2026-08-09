<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\TenantModel;
use App\Services\AuditLogService;
use App\Services\AuthSecurityService;
use App\Services\PasswordResetService;

class AuthController extends BaseController
{
    protected UserModel $userModel;
    protected AuditLogService $auditLogService;
    protected AuthSecurityService $securityService;
    protected PasswordResetService $passwordResetService;

    public function __construct()
    {
        $this->userModel            = new UserModel();
        $this->auditLogService      = new AuditLogService();
        $this->securityService      = new AuthSecurityService();
        $this->passwordResetService = new PasswordResetService();
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

        $email    = mb_strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');
        $ip       = $this->request->getIPAddress();
        $agent    = (string) $this->request->getUserAgent();

        // Chống brute-force: khóa tạm sau N lần sai
        $lockedSeconds = $this->securityService->lockedSecondsRemaining($email, $ip);
        if ($lockedSeconds > 0) {
            $minutes = (int) ceil($lockedSeconds / 60);
            return redirect()->back()
                ->withInput()
                ->with('error', lang('Auth.loginLocked', [$minutes]));
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user->password)) {
            $this->securityService->recordAttempt($email, $ip, $agent, false);
            $remaining = $this->securityService->remainingAttempts($email, $ip);

            $message = lang('Auth.invalidCredentials');
            if ($remaining > 0 && $remaining < AuthSecurityService::MAX_ATTEMPTS) {
                $message .= ' ' . lang('Auth.loginAttemptsLeft', [$remaining]);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $message);
        }

        // Đăng nhập đúng → xóa các lần sai trước đó
        $this->securityService->clearAttempts($email);
        $this->securityService->recordAttempt($email, $ip, $agent, true);

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

        // Theo dõi phiên đăng nhập
        $this->securityService->trackSession(
            (int) $user->id,
            session_id(),
            $ip,
            $agent
        );

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

        $this->securityService->untrackSession(session_id());
        session()->destroy();
        return redirect()->to('/login')
            ->with('success', lang('Auth.logoutSuccess'));
    }

    /**
     * GET /forgot-password — form nhập email
     */
    public function forgotPassword()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/admin/dashboard');
        }

        return $this->render('public/forgot_password', [
            'pageTitle' => lang('Auth.forgotPasswordTitle'),
        ]);
    }

    /**
     * POST /forgot-password — tạo token + gửi email
     * Luôn báo thành công (chống dò tài khoản).
     */
    public function forgotPasswordPost()
    {
        $rules = ['email' => 'required|valid_email'];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = (string) $this->request->getPost('email');
        $token = $this->passwordResetService->createToken($email, $this->request->getIPAddress());

        // Môi trường dev: hiện link trực tiếp để test (SMTP chưa cấu hình)
        if (ENVIRONMENT !== 'production' && $token) {
            session()->setFlashdata('dev_reset_url', base_url("reset-password/{$token}"));
        }

        return redirect()->to('/forgot-password')
            ->with('success', lang('Auth.resetEmailSent'));
    }

    /**
     * GET /reset-password/{token} — form mật khẩu mới
     */
    public function resetPassword(string $token)
    {
        if (!$this->passwordResetService->verifyToken($token)) {
            return redirect()->to('/forgot-password')
                ->with('error', lang('Auth.resetTokenInvalid'));
        }

        return $this->render('public/reset_password', [
            'pageTitle' => lang('Auth.resetPasswordTitle'),
            'token'     => $token,
        ]);
    }

    /**
     * POST /reset-password — đổi mật khẩu bằng token
     */
    public function resetPasswordPost()
    {
        $rules = [
            'token'           => 'required',
            'password'        => 'required|min_length[6]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $token    = (string) $this->request->getPost('token');
        $password = (string) $this->request->getPost('password');

        $result = $this->passwordResetService->resetPassword($token, $password);

        if (!$result['success']) {
            return redirect()->back()
                ->with('error', $result['error']);
        }

        return redirect()->to('/login')
            ->with('success', lang('Auth.resetSuccess'));
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
