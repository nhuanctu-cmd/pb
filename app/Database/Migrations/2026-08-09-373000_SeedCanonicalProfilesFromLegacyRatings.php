<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Non-destructive bridge: legacy integer ratings become auditable seed transactions. */
class SeedCanonicalProfilesFromLegacyRatings extends Migration
{
    public function up()
    {
        foreach (['player_ratings', 'player_rating_profiles', 'rating_transactions', 'rating_disciplines', 'rating_policy_versions', 'rating_providers'] as $table) if (! $this->db->tableExists($table)) return;
        $provider = $this->db->table('rating_providers')->where('code', 'internal-v1')->get()->getRow();
        $discipline = $this->db->table('rating_disciplines')->where('code', 'singles')->get()->getRow();
        if (! $provider || ! $discipline) return;
        $policy = $this->db->table('rating_policy_versions')->where('provider_id', $provider->id)->where('discipline_id', $discipline->id)->where('status', 'active')->orderBy('effective_from', 'DESC')->get()->getRow();
        if (! $policy) return;
        $now = date('Y-m-d H:i:s');
        foreach ($this->db->table('player_ratings')->where('rating IS NOT NULL')->get()->getResult() as $legacy) {
            $tenantId = (int) ($legacy->tenant_id ?? 0); $playerId = (int) ($legacy->player_id ?? 0); if ($tenantId <= 0 || $playerId <= 0) continue;
            $key = implode(':', ['legacy-seed', $provider->id, $tenantId, $playerId, $discipline->id]);
            if ($this->db->table('rating_transactions')->where('idempotency_key', $key)->countAllResults()) continue;
            $rating = $this->mapLegacyRating((float) $legacy->rating);
            $matches = max(0, (int) ($legacy->games_played ?? 0)); $wins = max(0, (int) ($legacy->wins ?? 0));
            $reliability = min(69, round(min(100, ($matches / 20) * 45) + ($matches > 0 ? min(24, ($wins / max(1, $matches)) * 24) : 0), 2));
            $band = $this->db->table('skill_level_bands')->where('active', 1)->where('min_rating <=', $rating)->groupStart()->where('max_rating >=', $rating)->orWhere('max_rating', null)->groupEnd()->orderBy('display_order', 'DESC')->get()->getRow();
            $existing = $this->db->table('player_rating_profiles')->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('provider_id', $provider->id)->where('discipline_id', $discipline->id)->get()->getRow();
            if (! $existing) {
                $this->db->table('player_rating_profiles')->insert(['tenant_id' => $tenantId, 'player_id' => $playerId, 'provider_id' => $provider->id, 'discipline_id' => $discipline->id, 'rating_value' => $rating, 'skill_band_id' => $band->id ?? null, 'reliability_score' => $reliability, 'status' => 'provisional', 'rated_match_count' => $matches, 'verified_match_count' => 0, 'last_rated_match_at' => $legacy->last_match_at ?? null, 'highest_rating' => $rating, 'lowest_rating' => $rating, 'calculated_at' => $now, 'metadata' => json_encode(['source' => 'legacy_player_ratings', 'legacy_rating' => (float) $legacy->rating, 'legacy_games_played' => $matches], JSON_UNESCAPED_UNICODE), 'created_at' => $now, 'updated_at' => $now]);
            }
            try {
                $this->db->table('rating_transactions')->insert(['tenant_id' => $tenantId, 'player_id' => $playerId, 'provider_id' => $provider->id, 'discipline_id' => $discipline->id, 'match_id' => null, 'match_result_version_id' => null, 'rating_policy_version_id' => $policy->id, 'transaction_type' => 'seed', 'before_rating' => null, 'after_rating' => $rating, 'rating_delta' => 0, 'expected_performance' => null, 'actual_performance' => null, 'reliability_before' => 0, 'reliability_after' => $reliability, 'match_weight' => 0, 'reason' => 'LEGACY_RATING_MIGRATION', 'status' => 'applied', 'idempotency_key' => $key, 'processed_at' => $now, 'metadata' => json_encode(['legacy_table' => 'player_ratings', 'legacy_id' => $legacy->id ?? null], JSON_UNESCAPED_UNICODE), 'created_at' => $now]);
            } catch (\Throwable $e) {
                // A concurrent/repeated migration may race the idempotency check.
                if (stripos($e->getMessage(), 'duplicate') === false) throw $e;
            }
        }
    }

    private function mapLegacyRating(float $value): float
    {
        if ($value >= 2 && $value <= 6) return round($value, 3);
        return round(max(2.000, min(5.999, 2.000 + (($value - 800) / 200))), 3);
    }

    public function down()
    {
        // This bridge only inserts idempotent seed rows. Do not delete
        // canonical profiles or ledgers during rollback; a privileged
        // rebuild/compensation process owns historical data removal.
    }
}
