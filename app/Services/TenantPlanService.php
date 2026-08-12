<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\PlayerModel;
use App\Models\TenantPlanModel;
use App\Models\TenantSubscriptionModel;
use App\Models\TenantUsageModel;
use App\Models\TenantModel;
use App\Models\UserModel;

/**
 * Dịch vụ gói dịch vụ SaaS: kiểm tra hạn mức, tính năng theo gói, usage.
 */
class TenantPlanService
{
    protected TenantPlanModel $planModel;
    protected TenantSubscriptionModel $subModel;
    protected TenantUsageModel $usageModel;

    public function __construct()
    {
        $this->planModel  = new TenantPlanModel();
        $this->subModel   = new TenantSubscriptionModel();
        $this->usageModel = new TenantUsageModel();
    }

    /**
     * Lấy gói đang hiệu lực của tenant (null = chưa đăng ký gói nào)
     */
    public function getCurrentPlan(int $tenantId): ?array
    {
        return $this->subModel->getCurrentForTenant($tenantId);
    }

    /**
     * Tenant có quyền dùng tính năng không? (vd: 'pos', 'tournament', 'ai_scheduling')
     * Không có gói = chỉ tính năng lõi miễn phí.
     */
    public function hasFeature(int $tenantId, string $feature): bool
    {
        $plan = $this->getCurrentPlan($tenantId);

        if (! $plan) {
            return in_array($feature, ['booking', 'court'], true);
        }

        $features = $this->planModel->getFeatures($plan);

        return in_array('*', $features, true) || in_array($feature, $features, true);
    }

    /**
     * Kiểm tra hạn mức tài nguyên.
     *
     * @param string $resource branches|courts|players|staff
     * @return array{allowed: bool, used: int, max: int}
     */
    public function checkLimit(int $tenantId, string $resource): array
    {
        $plan = $this->getCurrentPlan($tenantId);

        // Không có gói → dùng hạn mức Free mặc định
        $max = match ($resource) {
            'branches' => (int) ($plan['max_branches'] ?? 1),
            'courts'   => (int) ($plan['max_courts'] ?? 5),
            'players'  => (int) ($plan['max_players'] ?? 100),
            'staff'    => (int) ($plan['max_staff'] ?? 5),
            default    => 0,
        };

        $used = $this->countResource($tenantId, $resource);

        // -1 = không giới hạn
        $allowed = ($max === -1) || ($used < $max);

        return ['allowed' => $allowed, 'used' => $used, 'max' => $max];
    }

    /**
     * Ghi nhận mức dùng theo tháng (bookings, api_calls, storage_mb)
     */
    public function trackUsage(int $tenantId, string $metric, int $amount = 1): void
    {
        $this->usageModel->incrementUsage($tenantId, $metric, $amount);
    }

    public function getUsage(int $tenantId, string $metric): int
    {
        return $this->usageModel->getUsage($tenantId, $metric);
    }

    /**
     * Đăng ký gói cho tenant (transaction-safe)
     */
    public function subscribe(int $tenantId, int $planId, string $status = 'trial', ?int $trialDays = 14): ?int
    {
        $plan = $this->planModel->find($planId);
        if (! $plan) {
            return null;
        }

        $tenant = model(TenantModel::class)->where('id', $tenantId)->first();
        $currency = $this->resolveTenantCurrency((array) $tenant, $tenantId);
        $pricingTerm = $status === 'active' ? 'yearly' : ($status === 'trial' ? 'trial' : 'monthly');
        $snapshotPrice = $pricingTerm === 'yearly' ? (float) ($plan['price_yearly'] ?? 0) : (float) ($plan['price_monthly'] ?? 0);

        $db = $this->subModel->db;
        $db->transStart();

        // Hủy các gói đang hiệu lực
        $this->subModel->where('tenant_id', $tenantId)
                       ->whereIn('status', ['trial', 'active'])
                       ->set('status', 'cancelled')
                       ->set('cancelled_at', date('Y-m-d H:i:s'))
                       ->update();

        $subscriptionData = [
            'tenant_id'     => $tenantId,
            'plan_id'       => $planId,
            'status'        => $status,
            'starts_at'     => date('Y-m-d'),
            'trial_ends_at' => $status === 'trial' ? date('Y-m-d', time() + $trialDays * 86400) : null,
            'ends_at'       => $status === 'active' ? date('Y-m-d', strtotime('+1 month')) : null,
        ];

        if ($db->fieldExists('plan_snapshot', 'tenant_subscriptions')) {
            $subscriptionData['plan_snapshot'] = json_encode([
                'plan_code' => $plan['code'] ?? '',
                'plan_name_vi' => $plan['name_vi'] ?? '',
                'plan_name_en' => $plan['name_en'] ?? '',
                'features' => $this->planModel->getFeatures($plan),
                'limits' => [
                    'max_branches' => (int) ($plan['max_branches'] ?? 0),
                    'max_courts' => (int) ($plan['max_courts'] ?? 0),
                    'max_players' => (int) ($plan['max_players'] ?? 0),
                    'max_staff' => (int) ($plan['max_staff'] ?? 0),
                ],
            ]);
        }

        if ($db->fieldExists('pricing_term', 'tenant_subscriptions')) {
            $subscriptionData['pricing_term'] = $pricingTerm;
        }

        if ($db->fieldExists('currency_snapshot', 'tenant_subscriptions')) {
            $subscriptionData['currency_snapshot'] = $currency;
        }

        if ($db->fieldExists('price_snapshot_amount', 'tenant_subscriptions')) {
            $subscriptionData['price_snapshot_amount'] = round($snapshotPrice, 2);
        }

        $id = $this->subModel->insert($subscriptionData);

        $db->transComplete();

        return $db->transStatus() === false ? null : (int) $id;
    }

    protected function resolveTenantCurrency(array $tenant, int $tenantId): string
    {
        if (! empty($tenant['default_currency'])) {
            return strtoupper((string) $tenant['default_currency']);
        }

        $tenantModel = model(TenantModel::class);
        $db = $tenantModel->db;

        if ($db->tableExists('platform_countries') && $db->fieldExists('country_code', 'tenants') && $db->fieldExists('code', 'platform_countries') && $db->fieldExists('default_currency', 'platform_countries')) {
            $currency = $db->table('platform_countries pc')
                ->select('pc.default_currency')
                ->join('tenants t', 't.country_code = pc.code', 'left')
                ->where('t.id', $tenantId)
                ->get()
                ->getRow();

            if (! empty($currency?->default_currency)) {
                return strtoupper((string) $currency->default_currency);
            }
        }

        return 'VND';
    }

    /**
     * Đếm tài nguyên hiện có của tenant
     */
    protected function countResource(int $tenantId, string $resource): int
    {
        return match ($resource) {
            'branches' => (new BranchModel())->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults(),
            'courts'   => (new CourtModel())->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults(),
            'players'  => (new PlayerModel())->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults(),
            'staff'    => (new UserModel())->where('tenant_id', $tenantId)->where('deleted_at', null)->where('is_superadmin', 0)->countAllResults(),
            default    => 0,
        };
    }
}
