<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Filters\ApiAuthFilter;
use App\Models\UserModel;

class AuthApi extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return service('apiResponseService')->validationError($this->validator->getErrors());
        }

        $email    = (string) ($this->request->getVar('email') ?? '');
        $password = (string) ($this->request->getVar('password') ?? '');

        $user = $this->userModel->findByEmail($email);
        if (!$user || !password_verify($password, $user->password)) {
            return service('apiResponseService')->unauthorized(lang('App.login_failed'));
        }

        if (!$user->is_active || $user->status !== 'active') {
            return service('apiResponseService')->forbidden(lang('Auth.accountInactive'));
        }

        $token = ApiAuthFilter::generateToken([
            'user_id'   => $user->id,
            'tenant_id' => $user->tenant_id,
            'branch_id' => $user->branch_id,
        ]);

        return service('apiResponseService')->success([
            'token' => $token,
            'user'  => $this->sanitizeUser($user),
        ], lang('Auth.loginSuccess'));
    }

    public function refresh()
    {
        $userId = $this->request->api_user_id ?? null;
        $tenantId = (int) ($this->request->api_tenant_id ?? 0);
        $user = $userId && $tenantId ? $this->userModel->findForTenant((int) $userId, $tenantId) : null;
        if (!$user) {
            return service('apiResponseService')->unauthorized();
        }

        $token = ApiAuthFilter::generateToken([
            'user_id'   => $user->id,
            'tenant_id' => $user->tenant_id,
            'branch_id' => $user->branch_id,
        ]);

        return service('apiResponseService')->success(['token' => $token], lang('App.token_refreshed'));
    }

    private function sanitizeUser($user): array
    {
        $data = method_exists($user, 'toRawArray') ? $user->toRawArray() : (array) $user;
        unset($data['password']);
        return $data;
    }
}
