<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyClosingModel extends Model
{
    protected $table = 'daily_closings';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id', 'branch_id', 'closing_date', 'status', 'cash_total', 'qr_total', 'wallet_total',
        'other_total', 'billed_total', 'collected_total', 'refund_total', 'discrepancy_amount', 'notes',
        'closed_by', 'closed_at', 'reopened_by', 'reopened_at',
    ];

    public function findForScope(int $tenantId, ?int $branchId, string $date): ?object
    {
        $query = $this->where('tenant_id', $tenantId)->where('closing_date', $date);
        return $query->groupStart()->where('branch_id', $branchId)->orWhere('branch_id', null)->groupEnd()->first();
    }
}
