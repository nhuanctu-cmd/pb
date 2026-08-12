<?php

namespace App\Services;

use App\Models\BranchModel;

/** Tenant-safe availability facade used by player booking and future channels. */
class AvailabilityService
{
    private array $requestCache = [];

    public function weekly(int $branchId, string $weekStart, int $tenantId, int $slotDurationMinutes = 60): array
    {
        if ($tenantId <= 0 || ! model(BranchModel::class)->findForTenant($branchId, $tenantId)) return ['success' => false, 'code' => 'TENANT_ISOLATION', 'message' => 'Chi nhánh không thuộc tenant hiện tại.'];
        $slotDurationMinutes = max(15, min(240, $slotDurationMinutes));
        $cacheKey = implode(':', ['weekly', $tenantId, $branchId, $weekStart, $slotDurationMinutes]);
        if (isset($this->requestCache[$cacheKey])) return $this->requestCache[$cacheKey];
        try {
            return $this->requestCache[$cacheKey] = ['success' => true, 'data' => service('bookingService')->getWeeklyAvailability($branchId, $weekStart, $tenantId, $slotDurationMinutes)];
        } catch (\InvalidArgumentException $exception) {
            return ['success' => false, 'code' => 'INVALID_WEEK', 'message' => 'Tuần không hợp lệ.'];
        }
    }

    public function slots(int $courtId, string $date, int $tenantId, int $slotDurationMinutes = 60): array
    {
        if ($tenantId <= 0) return ['success' => false, 'code' => 'TENANT_REQUIRED', 'slots' => []];
        $slotDurationMinutes = max(15, min(240, $slotDurationMinutes));
        $cacheKey = implode(':', ['slots', $tenantId, $courtId, $date, $slotDurationMinutes]);
        if (isset($this->requestCache[$cacheKey])) return $this->requestCache[$cacheKey];
        return $this->requestCache[$cacheKey] = ['success' => true, 'slots' => service('bookingService')->getAvailableSlots($courtId, $date, $slotDurationMinutes, $tenantId)];
    }
}
