<?php

namespace App\Models;

use CodeIgniter\Model;

class DynamicPriceLogModel extends Model
{
    protected $table            = 'dynamic_price_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'booking_id', 'court_id', 'branch_id', 'court_type_id',
        'input_json', 'matched_rule_ids', 'final_price', 'applied_rules', 'created_at',
    ];
    protected $useTimestamps = false;

    public function logPrice(int $tenantId, ?int $bookingId, int $courtId, ?int $branchId, ?int $courtTypeId, array $input, array $matchedRuleIds, float $finalPrice, array $breakdown): int
    {
        return (int) $this->insert([
            'tenant_id'        => $tenantId,
            'booking_id'       => $bookingId,
            'court_id'         => $courtId,
            'branch_id'        => $branchId,
            'court_type_id'    => $courtTypeId,
            'input_json'       => json_encode($input, JSON_UNESCAPED_UNICODE),
            'matched_rule_ids' => json_encode(array_values($matchedRuleIds)),
            'final_price'      => $finalPrice,
            'applied_rules'    => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    public function getLogs(int $tenantId, ?int $branchId = null, int $limit = 50): array
    {
        $builder = $this->where('tenant_id', $tenantId);

        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }

        return $builder->orderBy('created_at', 'DESC')->limit($limit)->findAll();
    }
}
