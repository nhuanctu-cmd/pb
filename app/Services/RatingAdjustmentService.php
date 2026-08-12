<?php

namespace App\Services;

use Config\Database;

/** Manual correction is a compensating transaction, never an overwrite. */
class RatingAdjustmentService
{
    protected $db;
    public function __construct() { $this->db = Database::connect(); }

    public function adjust(int $tenantId, int $playerId, string $discipline, float $newRating, string $reason, int $actorId): array
    {
        $reason = trim($reason); if ($tenantId <= 0 || $playerId <= 0 || $reason === '') return ['success' => false, 'message' => 'Tenant, player và reason là bắt buộc.'];
        if ($newRating < 2 || $newRating > 5.999) return ['success' => false, 'message' => 'Rating phải trong khoảng 2.000–5.999.'];
        $provider = $this->db->table('rating_providers')->where('code', 'internal-v1')->get()->getRow(); $disciplineRow = $this->db->table('rating_disciplines')->where('code', $discipline)->where('active', 1)->get()->getRow();
        if (! $provider || ! $disciplineRow) return ['success' => false, 'message' => 'Provider hoặc discipline chưa sẵn sàng.'];
        $policy = $this->db->table('rating_policy_versions')->where('provider_id', $provider->id)->where('discipline_id', $disciplineRow->id)->where('status', 'active')->orderBy('effective_from', 'DESC')->get()->getRow(); if (! $policy) return ['success' => false, 'message' => 'Chưa có policy active.'];
        $profile = $this->db->table('player_rating_profiles')->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('provider_id', $provider->id)->where('discipline_id', $disciplineRow->id)->get()->getRow();
        if (! $profile) return ['success' => false, 'message' => 'Player chưa có canonical rating profile.'];
        $now = date('Y-m-d H:i:s'); $before = (float) ($profile->rating_value ?? 0); $key = 'adjustment:' . bin2hex(random_bytes(12)); $band = service('skillBandResolver')->resolveStable($newRating, $profile->skill_band_id ? (int) $profile->skill_band_id : null);
        $this->db->transStart();
        $this->db->table('rating_transactions')->insert(['tenant_id' => $tenantId, 'player_id' => $playerId, 'provider_id' => $provider->id, 'discipline_id' => $disciplineRow->id, 'rating_policy_version_id' => $policy->id, 'transaction_type' => 'adjustment', 'before_rating' => $before, 'after_rating' => round($newRating, 3), 'rating_delta' => round($newRating - $before, 3), 'reliability_before' => $profile->reliability_score, 'reliability_after' => $profile->reliability_score, 'match_weight' => 0, 'reason' => 'MANUAL_ADJUSTMENT', 'status' => 'applied', 'idempotency_key' => $key, 'processed_at' => $now, 'metadata' => json_encode(['actor_id' => $actorId, 'reason' => $reason], JSON_UNESCAPED_UNICODE), 'created_at' => $now]);
        $transactionId = $this->db->insertID(); $this->db->table('player_rating_profiles')->where('id', $profile->id)->update(['rating_value' => round($newRating, 3), 'skill_band_id' => $band->id ?? null, 'highest_rating' => max((float) ($profile->highest_rating ?? $newRating), $newRating), 'lowest_rating' => min((float) ($profile->lowest_rating ?? $newRating), $newRating), 'calculated_at' => $now, 'updated_at' => $now]);
        $this->db->transComplete();
        if (! $this->db->transStatus()) return ['success' => false, 'message' => 'Không thể ghi adjustment.'];
        service('auditLogService')->log('adjust', 'rating', 'rating_transactions', (int) $transactionId, ['rating' => $before], ['rating' => $newRating, 'reason' => $reason, 'actor_id' => $actorId], 'Manual canonical rating adjustment', $tenantId, null, $actorId);
        return ['success' => true, 'transaction_id' => (int) $transactionId, 'before_rating' => $before, 'after_rating' => round($newRating, 3)];
    }
}
