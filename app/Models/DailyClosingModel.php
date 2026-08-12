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
        'other_total', 'billed_total', 'collected_total', 'refund_total', 'discrepancy_amount',
        'manual_adjustment', 'adjustment_reason', 'discrepancy_reason',
        'is_locked', 'digital_signature_name', 'digital_signature_at', 'digital_signature_by',
        'locked_at', 'locked_by',
        'notes',
        'closed_by', 'closed_at', 'reopened_by', 'reopened_at',
    ];

    public function findForScope(int $tenantId, ?int $branchId, string $date): ?object
    {
        $query = $this->where('tenant_id', $tenantId)->where('closing_date', $date);
        if ($branchId === null) {
            return $query->where('branch_id', null)->first();
        }
        return $query->where('branch_id', $branchId)->first();
    }
}
