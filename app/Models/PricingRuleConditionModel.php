<?php

namespace App\Models;

use CodeIgniter\Model;

class PricingRuleConditionModel extends Model
{
    protected $table            = 'pricing_rule_conditions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $protectFields    = true;
    protected $allowedFields    = ['tenant_id', 'pricing_rule_id', 'condition_type', 'operator', 'value', 'value_to'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getByRule(int $ruleId): array
    {
        return $this->where('pricing_rule_id', $ruleId)
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
