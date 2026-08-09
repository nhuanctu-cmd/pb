<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class UiDemoController extends BaseController
{
    public function dashboard()
    {
        return $this->render('admin/ui_demo/dashboard', [
            'pageTitle' => 'Dashboard vận hành',
            'pageDescription' => 'Dashboard vận hành cụm sân Pickleball theo phong cách ERP thương mại.',
            'breadcrumbs' => [
                ['label' => 'Trang chủ', 'url' => '/admin/dashboard'],
                ['label' => 'UI Foundation'],
            ],
        ]);
    }

    public function list()
    {
        return $this->render('admin/ui_demo/list', [
            'pageTitle' => 'Danh sách đặt sân',
            'pageDescription' => 'Trang danh sách booking chuẩn ERP với filter, summary, action và quick drawer.',
            'breadcrumbs' => [
                ['label' => 'Trang chủ', 'url' => '/admin/dashboard'],
                ['label' => 'Danh sách ERP'],
            ],
        ]);
    }

    public function form()
    {
        return $this->render('admin/ui_demo/form', [
            'pageTitle' => 'Tạo booking',
            'pageDescription' => 'Form tạo/sửa booking 2 cột, nhiều section, validation và sticky action.',
            'breadcrumbs' => [
                ['label' => 'Trang chủ', 'url' => '/admin/dashboard'],
                ['label' => 'Form ERP'],
            ],
        ]);
    }

    public function detail()
    {
        return $this->render('admin/ui_demo/detail', [
            'pageTitle' => 'Hồ sơ người chơi',
            'pageDescription' => 'Mẫu trang chi tiết có entity header, tabs, timeline và records liên quan.',
            'breadcrumbs' => [
                ['label' => 'Trang chủ', 'url' => '/admin/dashboard'],
                ['label' => 'Detail ERP'],
            ],
        ]);
    }
}
