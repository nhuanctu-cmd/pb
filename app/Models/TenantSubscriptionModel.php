<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantSubscriptionModel extends Model
{
    protected $table            = 'tenant_subscriptions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'plan_id', 'status', 'starts_at', 'ends_at',
        'trial_ends_at', 'cancelled_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'plan_id'   => 'required|integer',
        'status'    => 'required|in_list[trial,active,expired,cancelled]',
        'starts_at' => 'required|valid_date',
    ];

    /**
     * Lấy subscription đang hiệu lực của tenant (kèm thông tin gói)
     */
    public function getCurrentForTenant(int $tenantId): ?array
    {
        $row = $this->select('tenant_subscriptions.*, tenant_plans.code AS plan_code, tenant_plans.name_vi AS plan_name_vi, tenant_plans.name_en AS plan_name_en, tenant_plans.max_branches, tenant_plans.max_courts, tenant_plans.max_players, tenant_plans.max_staff, tenant_plans.features')
                    ->join('tenant_plans', 'tenant_plans.id = tenant_subscriptions.plan_id')
                    ->where('tenant_subscriptions.tenant_id', $tenantId)
                    ->whereIn('tenant_subscriptions.status', ['trial', 'active'])
                    ->orderBy('tenant_subscriptions.id', 'DESC')
                    ->first();

        return $row ?: null;
    }
}
