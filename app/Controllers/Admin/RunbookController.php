<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class RunbookController extends BaseController
{
    public function index(): string
    {
        $today = date('Y-m-d');
        $this->viewData['pageTitle'] = 'Runbook vận hành thương mại (5 module)';
        $this->viewData['today'] = $today;

        $this->viewData['flow'] = [
            [
                'title'       => '1) Front Desk · Quầy vận hành',
                'description' => 'Xử lý booking vào sân, check-in, hold, phát hiện booking trễ.',
                'steps'       => [
                    ['label' => 'Danh sách quầy hôm nay', 'url' => '/admin/front-desk?date=' . $today],
                    ['label' => 'Các booking đang hold', 'url' => '/admin/front-desk?date=' . $today . '&status=hold'],
                    ['label' => 'Các booking cần vào quầy', 'url' => '/admin/front-desk?date=' . $today . '&status=reserved'],
                    ['label' => 'Khách đã đang chơi', 'url' => '/admin/front-desk?date=' . $today . '&status=in_progress'],
                ],
            ],
            [
                'title'       => '2) Owner Dashboard',
                'description' => 'KPI MTD/YTD, cảnh báo công nợ, cảnh báo gia hạn.',
                'steps'       => [
                    ['label' => 'Dashboard KPI', 'url' => '/admin/owner-dashboard?date=' . $today],
                    ['label' => 'Danh sách cảnh báo top khách', 'url' => '/admin/owner-dashboard?date=' . $today],
                ],
            ],
            [
                'title'       => '3) Daily Closing · Chốt ca',
                'description' => 'Khóa số liệu theo chi nhánh + ngày, xuất in PDF/CSV.',
                'steps'       => [
                    ['label' => 'Mở chốt ca ngày hôm nay', 'url' => '/admin/daily-closing?date=' . $today],
                    ['label' => 'In HTML', 'url' => '/admin/daily-closing/print?date=' . $today],
                    ['label' => 'In PDF', 'url' => '/admin/daily-closing/pdf?date=' . $today],
                    ['label' => 'Xuất CSV', 'url' => '/admin/daily-closing/csv?date=' . $today],
                ],
            ],
            [
                'title'       => '4) Membership Renewal',
                'description' => 'Danh sách hội viên sắp hết hạn, gia hạn đơn & nhắc nhở SMS/Zalo/Email.',
                'steps'       => [
                    ['label' => 'Hồ sơ cần gia hạn trong 30 ngày', 'url' => '/admin/memberships/renewals?days=30&status=active'],
                    ['label' => 'Tìm hội viên còn hạn > 60 ngày', 'url' => '/admin/memberships/renewals?days=60&status=active'],
                    ['label' => 'Xuất CSV danh sách', 'url' => '/admin/memberships/renewals/export?days=30&status=active'],
                ],
            ],
            [
                'title'       => '5) CRM Campaign',
                'description' => 'Tạo campaign, test gửi, dispatch theo throttle + retry.',
                'steps'       => [
                    ['label' => 'Danh sách CRM Campaign', 'url' => '/admin/crm-campaigns'],
                    ['label' => 'Dispatch hàng đợi đến hạn', 'url' => '/admin/crm-campaigns'],
                    ['label' => 'Lịch sử gửi trong dashboard', 'url' => '/admin/crm-campaigns'],
                ],
            ],
        ];

        return $this->render('admin/runbook/index', $this->viewData);
    }
}
