<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\TenantModel;
use App\Models\BranchModel;
use App\Models\PlayerModel;
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
            return redirect()->to(session()->get('account_type') === 'player' ? '/player' : '/admin/dashboard');
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
        $branchModel = new BranchModel();
        $userRoleModel = new UserRoleModel();
        $roles = $userRoleModel->getRolesByUser((int) $user->id);
        $roleSlugs = array_values(array_filter(array_map(static fn ($role) => (string) ($role->slug ?? ''), $roles)));
        $primaryRole = $roleSlugs[0] ?? ($user->is_superadmin ? 'super-admin' : 'staff');
        foreach (['super-admin', 'owner', 'branch-manager', 'staff', 'referee', 'player'] as $preferredRole) {
            if (in_array($preferredRole, $roleSlugs, true)) {
                $primaryRole = $preferredRole;
                break;
            }
        }
        $accountType = $user->is_superadmin || in_array('super-admin', $roleSlugs, true)
            ? 'superadmin'
            : ($primaryRole === 'player' ? 'player' : 'admin');

        if (empty($tenantId)) {
            // Chỉ tài khoản demo mới tự động vào tenant demo. Superadmin
            // khác vẫn vào tenant active đầu tiên theo hành vi mặc định.
            $tenant = $user->email === 'admin@demo-pickleball.vn'
                ? $tenantModel->where('code', 'DEMO-PB')->where('status', 'active')->first()
                : null;
            $tenant ??= $tenantModel->where('status', 'active')->orderBy('id', 'ASC')->first();
            $tenantId = $tenant->id ?? null;
        } else {
            $tenant = $tenantModel->find($tenantId);
        }

        if (!empty($tenant)) {
            $tenantName = $tenant->name ?? null;
        }

        // Branch của user phải thuộc đúng tenant hiện tại.
        if (!empty($branchId) && !empty($tenantId)) {
            $validBranch = $branchModel->where('id', $branchId)
                ->where('tenant_id', $tenantId)
                ->where('deleted_at', null)
                ->first();
            if (!$validBranch) {
                $branchId = null;
            }
        }
        if (empty($branchId) && !empty($tenantId)) {
            $branch = $branchModel->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('is_main', 'DESC')->first();
            $branchId = $branch->id ?? null;
        }

        $playerId = null;
        $playerName = null;
        if ($accountType === 'player' && !empty($tenantId)) {
            $playerModel = new PlayerModel();
            $player = $playerModel->where('user_id', $user->id)
                ->where('tenant_id', $tenantId)
                ->where('deleted_at', null)
                ->first();
            $player ??= $playerModel->where('tenant_id', $tenantId)
                ->where('email', $user->email)
                ->where('deleted_at', null)
                ->first();
            if (!$player) {
                $playerId = $playerModel->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'player_code' => 'USER-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
                    'full_name' => $user->getFullName(),
                    'email' => $user->email,
                    'home_branch_id' => $branchId,
                    'status' => 'active',
                ]) ?: null;
                $player = $playerId ? $playerModel->find($playerId) : null;
            }
            $playerId = $player->id ?? $playerId;
            $playerName = $player->full_name ?? $user->getFullName();
        }

        session()->set([
            'userId'        => $user->id,
            'user_id'       => $user->id,
            'username'      => $user->username,
            'email'         => $user->email,
            'fullName'      => $user->getFullName(),
            'avatar'        => $user->avatar,
            'isLoggedIn'    => true,
            'is_superadmin' => (bool) $user->is_superadmin,
            'roles'         => $roleSlugs,
            'primary_role'  => $primaryRole,
            'account_type'  => $accountType,
            'tenant_id'     => $tenantId,
            'tenant_name'   => $tenantName,
            'branch_id'     => $branchId,
            'player_id'     => $playerId,
            'player_name'   => $playerName,
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

        return redirect()->to($accountType === 'player' ? '/player' : '/admin/dashboard');
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
