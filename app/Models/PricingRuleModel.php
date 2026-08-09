<?php

namespace App\Models;

use CodeIgniter\Model;

class PricingRuleModel extends Model
{
    protected $table            = 'pricing_rules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'court_type_id', 'court_id', 'code',
        'name_vi', 'name_en', 'description', 'priority', 'price_type',
        'price_amount', 'member_price_amount', 'start_date', 'end_date',
        'start_time', 'end_time', 'day_of_week', 'is_holiday', 'status',
        'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->where('tenant_id', $tenantId)->where('deleted_at', null);

        if (!empty($filters['branch_id'])) {
            $builder->where('branch_id', $filters['branch_id']);
        }

        if (!empty($filters['court_type_id'])) {
            $builder->where('court_type_id', $filters['court_type_id']);
        }

        if (!empty($filters['court_id'])) {
            $builder->where('court_id', $filters['court_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('status', $filters['status']);
        }

        return $builder->orderBy('priority', 'DESC')->orderBy('id', 'DESC')->findAll();
    }

    public function getApplicableRules(int $tenantId, int $branchId, ?int $courtTypeId = null, ?int $courtId = null): array
    {
        $builder = $this->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('branch_id', $branchId)
                ->orWhere('branch_id IS NULL', null, false)
            ->groupEnd();

        if ($courtTypeId) {
            $builder->groupStart()
                ->where('court_type_id', $courtTypeId)
                ->orWhere('court_type_id IS NULL', null, false)
                ->groupEnd();
        }

        if ($courtId) {
            $builder->groupStart()
                ->where('court_id', $courtId)
                ->orWhere('court_id IS NULL', null, false)
                ->groupEnd();
        }

        return $builder->orderBy('priority', 'DESC')->orderBy('id', 'DESC')->findAll();
    }
}
