<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TenantPlanModel;
use App\Services\TenantPlanService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Quản lý gói dịch vụ SaaS của tenant (xem gói hiện tại, hạn mức, nâng cấp)
 */
class PlansController extends BaseController
{
    protected TenantPlanService $planService;
    protected TenantPlanModel $planModel;

    public function __construct()
    {
        $this->planService = new TenantPlanService();
        $this->planModel   = new TenantPlanModel();
    }

    /**
     * Trang gói dịch vụ: gói hiện tại + usage + các gói có thể nâng cấp
     */
    public function index(): string
    {
        $tenantId = (int) (session('tenant_id') ?? 0);

        $currentPlan = $tenantId ? $this->planService->getCurrentPlan($tenantId) : null;

        // Hạn mức tài nguyên
        $limits = [];
        foreach (['branches', 'courts', 'players', 'staff'] as $resource) {
            $limits[$resource] = $tenantId
                ? $this->planService->checkLimit($tenantId, $resource)
                : ['allowed' => true, 'used' => 0, 'max' => -1];
        }

        // Usage theo tháng
        $usage = [
            'bookings'  => $tenantId ? $this->planService->getUsage($tenantId, 'bookings') : 0,
            'api_calls' => $tenantId ? $this->planService->getUsage($tenantId, 'api_calls') : 0,
        ];

        return $this->render('admin/plans/index', [
            'pageTitle'         => lang('App.plans_title'),
            'pageDescription'   => lang('App.plans_subtitle'),
            'plans'             => $this->planModel->getActive(),
            'currentPlan'       => $currentPlan,
            'limits'            => $limits,
            'usage'             => $usage,
            'isSuperAdmin'      => (bool) session('is_superadmin'),
        ]);
    }

    /**
     * Đăng ký/nâng cấp gói (demo: kích hoạt ngay; production: qua cổng thanh toán)
     */
    public function subscribe(int $planId): ResponseInterface
    {
        $tenantId = (int) (session('tenant_id') ?? 0);

        if (! $tenantId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => lang('App.not_found'),
            ]);
        }

        $plan = $this->planModel->find($planId);
        if (! $plan || ! $plan['is_active']) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => lang('App.plan_not_found'),
            ]);
        }

        // Không cho đăng ký lại đúng gói đang dùng
        $current = $this->planService->getCurrentPlan($tenantId);
        if ($current && (int) $current['plan_id'] === $planId) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => lang('App.plan_already_active'),
            ]);
        }

        $status = ((float) $plan['price_monthly'] > 0) ? 'active' : 'trial';
        $id = $this->planService->subscribe($tenantId, $planId, $status);

        if (! $id) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => lang('App.plan_subscribe_failed'),
            ]);
        }

        log_message('info', "[Plans] Tenant {$tenantId} đăng ký gói {$plan['code']} (#{$id})");

        return $this->response->setJSON([
            'success' => true,
            'message' => lang('App.plan_subscribe_success', [lang('App.plan_name_' . $plan['code']) !== 'App.plan_name_' . $plan['code'] ? lang('App.plan_name_' . $plan['code']) : $plan['name_vi']]),
        ]);
    }
}
