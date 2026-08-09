<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UserController extends BaseController
{
    public function index()
    {
        return $this->render('admin/system/users', [
            'pageTitle' => 'Users',
            'users'     => (new UserModel())->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function create()
    {
        return redirect()->to('/admin/users')->with('info', 'User create screen is ready to be implemented.');
    }

    public function store()
    {
        return redirect()->to('/admin/users')->with('info', 'User store action is ready to be implemented.');
    }

    public function edit(int $id)
    {
        return redirect()->to('/admin/users')->with('info', 'User edit screen is ready to be implemented.');
    }

    public function update(int $id)
    {
        return redirect()->to('/admin/users')->with('info', 'User update action is ready to be implemented.');
    }

    public function delete(int $id)
    {
        return redirect()->to('/admin/users')->with('info', 'User delete action is ready to be implemented.');
    }
}
