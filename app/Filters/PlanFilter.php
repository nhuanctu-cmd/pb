<?php

namespace App\Filters;

use App\Services\TenantPlanService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SaaS feature gating: chặn truy cập module khi gói hiện tại không có tính năng.
 * Dùng: ['filter' => 'plan:pos'] — super admin luôn vượt qua.
 */
class PlanFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Super admin toàn quyền
        if (session()->get('is_superadmin')) {
            return;
        }

        $tenantId = (int) (session()->get('tenant_id') ?? 0);
        $feature  = $arguments[0] ?? null;

        if (! $tenantId || ! $feature) {
            return;
        }

        $planService = new TenantPlanService();

        if ($planService->hasFeature($tenantId, $feature)) {
            return;
        }

        $message = lang('App.planFeatureLocked', [lang('App.feature_' . $feature)]);

        if ($request->isAJAX()) {
            return service('response')
                ->setStatusCode(ResponseInterface::HTTP_PAYMENT_REQUIRED)
                ->setJSON(['status' => 402, 'message' => $message, 'upgrade_url' => base_url('admin/plans')]);
        }

        return redirect()->to('/admin/plans')
            ->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}
