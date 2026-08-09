<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class ProfileController extends BaseController
{
    public function index()
    {
        return $this->render('admin/system/profile', [
            'pageTitle' => 'Profile',
            'user'      => user(),
        ]);
    }

    public function update()
    {
        $user = user();
        if (!$user) {
            return redirect()->to('/login')->with('error', lang('Auth.sessionExpired'));
        }

        $data = $this->request->getPost(['first_name', 'last_name', 'phone']);
        $password = $this->request->getPost('password');
        if ($password) {
            $data['password'] = $password;
        }

        (new UserModel())->update($user->id, $data);

        session()->set('fullName', trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')));

        return redirect()->to('/admin/profile')->with('success', 'Profile updated successfully.');
    }
}
