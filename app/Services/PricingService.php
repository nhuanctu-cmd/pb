<?php

namespace App\Services;

use App\Models\CourtModel;
use App\Models\DynamicPriceLogModel;
use App\Models\MembershipModel;
use App\Models\PricingRuleConditionModel;
use App\Models\PricingRuleModel;

class PricingService
{
    protected PricingRuleModel $pricingRuleModel;
    protected PricingRuleConditionModel $conditionModel;
    protected DynamicPriceLogModel $priceLogModel;
    protected CourtModel $courtModel;
    protected MembershipModel $membershipModel;

    protected array $lastMatchedRules = [];

    public function __construct()
    {
        $this->pricingRuleModel = new PricingRuleModel();
        $this->conditionModel   = new PricingRuleConditionModel();
        $this->priceLogModel    = new DynamicPriceLogModel();
        $this->courtModel       = new CourtModel();
        $this->membershipModel  = new MembershipModel();
    }

    public function getPrice(int $tenantId, int $branchId, int $courtId, string $date, string $startTime, string $endTime, ?int $playerId = null, ?int $bookingId = null): array
    {
        $court = $this->courtModel->find($courtId);
        if (! $court) {
            return $this->emptyResult('Court not found');
        }

        $durationMinutes = $this->calculateDuration($startTime, $endTime);
        $hours = max(0.25, $durationMinutes / 60);
        $isMember = $this->isActiveMember($playerId);
        $rules = $this->pricingRuleModel->getApplicableRules($tenantId, $branchId, $court->court_type_id ?? null, $courtId);
        $matchedRules = $this->getMatchedRules($rules, [
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'court_type_id' => $court->court_type_id ?? null,
            'court_id' => $courtId,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'player_id' => $playerId,
            'is_member' => $isMember,
        ]);

        $this->lastMatchedRules = $matchedRules;
        $selectedRule = $matchedRules[0] ?? null;
        $basePrice = $selectedRule ? $this->calculateHourlyPrice($selectedRule, $hours) : 0.0;
        $finalPrice = $selectedRule ? $this->applyMemberPrice($selectedRule, $basePrice, $hours, $isMember) : 0.0;
        $finalPrice = $this->applyHolidayPrice($matchedRules, $finalPrice, $hours, $isMember);

        $breakdown = [];
        foreach ($matchedRules as $rule) {
            $rulePrice = $this->applyMemberPrice($rule, $this->calculateHourlyPrice($rule, $hours), $hours, $isMember);
            $breakdown[] = [
                'rule_id' => (int) $rule->id,
                'name_vi' => $rule->name_vi,
                'priority' => (int) $rule->priority,
                'price_type' => $rule->price_type,
                'price_amount' => (float) $rule->price_amount,
                'member_price_amount' => $rule->member_price_amount !== null ? (float) $rule->member_price_amount : null,
                'calculated_price' => $rulePrice,
                'selected' => $selectedRule && (int) $selectedRule->id === (int) $rule->id,
            ];
        }

        $input = [
            'branch_id' => $branchId,
            'court_id' => $courtId,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $durationMinutes,
            'player_id' => $playerId,
            'is_member' => $isMember,
        ];

        $logId = $this->priceLogModel->logPrice(
            $tenantId,
            $bookingId,
            $courtId,
            $branchId,
            $court->court_type_id ?? null,
            $input,
            array_map(static fn ($rule) => (int) $rule->id, $matchedRules),
            $finalPrice,
            $breakdown
        );

        return [
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'duration_minutes' => $durationMinutes,
            'hours' => $hours,
            'matched_rules' => $matchedRules,
            'matched_rule_ids' => array_map(static fn ($rule) => (int) $rule->id, $matchedRules),
            'selected_rule' => $selectedRule,
            'breakdown' => $breakdown,
            'log_id' => $logId,
            'is_member' => $isMember,
        ];
    }

    public function getMatchedRules(array $rules = [], array $input = []): array
    {
        $matched = [];
        foreach ($rules as $rule) {
            if ($this->ruleMatches($rule, $input)) {
                $matched[] = $rule;
            }
        }

        return $this->sortRulesByPriority($matched);
    }

    public function calculateHourlyPrice(object $rule, float $hours): float
    {
        $amount = (float) $rule->price_amount;
        return $rule->price_type === 'fixed' ? $amount : $amount * $hours;
    }

    public function applyMemberPrice(object $rule, float $currentPrice, float $hours, bool $isMember): float
    {
        if (! $isMember || $rule->member_price_amount === null || $rule->member_price_amount === '') {
            return $currentPrice;
        }

        $memberAmount = (float) $rule->member_price_amount;
        return $rule->price_type === 'fixed' ? $memberAmount : $memberAmount * $hours;
    }

    public function applyHolidayPrice(array $matchedRules, float $currentPrice, float $hours, bool $isMember): float
    {
        foreach ($matchedRules as $rule) {
            if ((int) ($rule->is_holiday ?? 0) !== 1) {
                continue;
            }

            return $this->applyMemberPrice($rule, $this->calculateHourlyPrice($rule, $hours), $hours, $isMember);
        }

        return $currentPrice;
    }

    public function createRule(array $data, array $conditions = []): ?int
    {
        $ruleId = $this->pricingRuleModel->insert($this->normalizeRuleData($data));
        if (! $ruleId) {
            return null;
        }

        $this->syncConditions((int) $ruleId, (int) $data['tenant_id'], $conditions);
        return (int) $ruleId;
    }

    public function updateRule(int $id, array $data, array $conditions = []): bool
    {
        $updated = $this->pricingRuleModel->update($id, $this->normalizeRuleData($data));
        if ($updated) {
            $this->conditionModel->where('pricing_rule_id', $id)->delete();
            $this->syncConditions($id, (int) $data['tenant_id'], $conditions);
        }

        return $updated;
    }

    public function sortRulesByPriority(array $rules): array
    {
        usort($rules, static function ($a, $b) {
            return ((int) $b->priority <=> (int) $a->priority) ?: ((int) $b->id <=> (int) $a->id);
        });

        return $rules;
    }

    public function getAllRules(int $tenantId, array $filters = []): array
    {
        return $this->pricingRuleModel->getByTenant($tenantId, $filters);
    }

    public function getRuleById(int $id)
    {
        return $this->pricingRuleModel->find($id);
    }

    public function getConditions(int $ruleId): array
    {
        return $this->conditionModel->getByRule($ruleId);
    }

    public function deleteRule(int $id): bool
    {
        return $this->pricingRuleModel->delete($id);
    }

    public function getPriceLogs(int $tenantId, ?int $branchId = null, int $limit = 50): array
    {
        return $this->priceLogModel->getLogs($tenantId, $branchId, $limit);
    }

    public function calculatePrice(int $tenantId, int $branchId, int $courtId, string $date, string $startTime, string $endTime, ?int $playerId = null): array
    {
        return $this->getPrice($tenantId, $branchId, $courtId, $date, $startTime, $endTime, $playerId);
    }

    private function ruleMatches(object $rule, array $input): bool
    {
        $date = $input['date'];
        $startTime = substr($input['start_time'], 0, 5);
        $endTime = substr($input['end_time'], 0, 5);
        $weekday = (int) date('N', strtotime($date));

        if ($rule->start_date && $date < $rule->start_date) {
            return false;
        }

        if ($rule->end_date && $date > $rule->end_date) {
            return false;
        }

        if ($rule->start_time && substr($rule->start_time, 0, 5) > $startTime) {
            return false;
        }

        if ($rule->end_time && substr($rule->end_time, 0, 5) < $endTime) {
            return false;
        }

        if ($rule->day_of_week !== null && $rule->day_of_week !== '') {
            $days = array_map('intval', explode(',', (string) $rule->day_of_week));
            if (! in_array($weekday, $days, true)) {
                return false;
            }
        }

        if ((int) ($rule->is_holiday ?? 0) === 1 && ! $this->isHoliday($date)) {
            return false;
        }

        $conditions = $this->conditionModel->getByRule((int) $rule->id);
        foreach ($conditions as $condition) {
            if (! $this->conditionMatches($condition, $input, $weekday)) {
                return false;
            }
        }

        return true;
    }

    private function conditionMatches(object $condition, array $input, int $weekday): bool
    {
        $value = (string) ($condition->value ?? '');
        $operator = $condition->operator ?? 'equals';

        return match ($condition->condition_type) {
            'branch' => $this->compare((string) $input['branch_id'], $operator, $value),
            'court_type' => $this->compare((string) ($input['court_type_id'] ?? ''), $operator, $value),
            'court' => $this->compare((string) $input['court_id'], $operator, $value),
            'weekday' => $this->compare((string) $weekday, $operator, $value),
            'time_range' => $this->timeRangeMatches($input['start_time'], $input['end_time'], $value, $condition->value_to ?? null),
            'holiday' => $this->compare($this->isHoliday($input['date']) ? '1' : '0', $operator, $value),
            'member_level' => $input['is_member'] === true,
            default => true,
        };
    }

    private function compare(string $actual, string $operator, string $expected): bool
    {
        return match ($operator) {
            'in' => in_array($actual, array_map('trim', explode(',', $expected)), true),
            'not_in' => ! in_array($actual, array_map('trim', explode(',', $expected)), true),
            'not_equals' => $actual !== $expected,
            default => $actual === $expected,
        };
    }

    private function timeRangeMatches(string $startTime, string $endTime, string $from, ?string $to): bool
    {
        $start = substr($startTime, 0, 5);
        $end = substr($endTime, 0, 5);
        $from = substr($from, 0, 5);
        $to = $to ? substr($to, 0, 5) : $from;

        return $start >= $from && $end <= $to;
    }

    private function isHoliday(string $date): bool
    {
        return in_array(substr($date, 5), ['01-01', '04-30', '05-01', '09-02'], true);
    }

    private function isActiveMember(?int $playerId): bool
    {
        if (! $playerId) {
            return false;
        }

        return $this->membershipModel->getActiveByPlayer($playerId) !== null;
    }

    private function calculateDuration(string $startTime, string $endTime): int
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        if ($end <= $start) {
            $end = strtotime('+1 day', $end);
        }

        return (int) (($end - $start) / 60);
    }

    private function syncConditions(int $ruleId, int $tenantId, array $conditions): void
    {
        foreach ($conditions as $condition) {
            $condition = (array) $condition;
            unset($condition['id'], $condition['created_at'], $condition['updated_at']);

            if (empty($condition['condition_type']) || ($condition['value'] ?? '') === '') {
                continue;
            }

            $this->conditionModel->insert([
                'tenant_id' => $tenantId,
                'pricing_rule_id' => $ruleId,
                'condition_type' => $condition['condition_type'],
                'operator' => $condition['operator'] ?? 'equals',
                'value' => $condition['value'],
                'value_to' => $condition['value_to'] ?? null,
            ]);
        }
    }

    private function normalizeRuleData(array $data): array
    {
        foreach (['branch_id', 'court_type_id', 'court_id', 'member_price_amount', 'start_date', 'end_date', 'start_time', 'end_time', 'day_of_week'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        $data['priority'] = (int) ($data['priority'] ?? 10);
        $data['price_amount'] = (float) ($data['price_amount'] ?? 0);
        $data['member_price_amount'] = $data['member_price_amount'] !== null ? (float) $data['member_price_amount'] : null;
        $data['is_holiday'] = (int) ($data['is_holiday'] ?? 0);
        $data['status'] = $data['status'] ?? 'active';
        $data['price_type'] = $data['price_type'] ?? 'hourly';

        return $data;
    }

    private function emptyResult(string $message): array
    {
        return [
            'base_price' => 0,
            'final_price' => 0,
            'duration_minutes' => 0,
            'matched_rules' => [],
            'matched_rule_ids' => [],
            'selected_rule' => null,
            'breakdown' => [],
            'log_id' => null,
            'message' => $message,
        ];
    }
}
