<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UserApi extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function profile()
    {
        $userId = (int) ($this->request->api_user_id ?? user_id() ?? 0);
        return $this->show($userId);
    }

    public function show(int $id)
    {
        $user = $id > 0 ? $this->userModel->find($id) : null;
        if (!$user) {
            return service('apiResponseService')->notFound();
        }

        $data = method_exists($user, 'toRawArray') ? $user->toRawArray() : (array) $user;
        unset($data['password']);

        return service('apiResponseService')->success($data);
    }
}
