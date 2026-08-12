<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Repairs installations where the first V1 seed ran before all disciplines were available. */
class BackfillRatingEnginePolicies extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('rating_policy_versions') || ! $this->db->tableExists('rating_disciplines') || ! $this->db->tableExists('rating_providers')) return;
        $provider = $this->db->table('rating_providers')->where('code', 'internal-v1')->get()->getRow();
        if (! $provider) return;
        $now = date('Y-m-d H:i:s');
        $configuration = [
            'initial_rating' => 3.000, 'base_delta' => 0.160, 'max_delta' => 0.350, 'expected_rating_divisor' => 2.0, 'established_reliability' => 70,
            'provisional_volatility' => 1.35, 'score_margin_impact' => 0.15, 'recency_half_life_days' => 365,
            'reliability_weights' => ['volume' => 0.30, 'verification' => 0.25, 'recency' => 0.20, 'opponent_diversity' => 0.15, 'competition_diversity' => 0.10],
            'team_strategy' => 'TEAM_AVERAGE', 'skill_band_hysteresis' => 0.05, 'allow_play_up' => true, 'allow_play_down' => false,
        ];
        foreach ($this->db->table('rating_disciplines')->where('active', 1)->get()->getResult() as $discipline) {
            $policy = $this->db->table('rating_policy_versions')->where('provider_id', $provider->id)->where('discipline_id', $discipline->id)->where('version', '1.0')->get()->getRow();
            if (! $policy) {
                $this->db->table('rating_policy_versions')->insert(['provider_id' => $provider->id, 'discipline_id' => $discipline->id, 'name' => 'Internal Pickleball Rating', 'version' => '1.0', 'effective_from' => $now, 'configuration' => json_encode($configuration), 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
                $policy = $this->db->table('rating_policy_versions')->where('provider_id', $provider->id)->where('discipline_id', $discipline->id)->where('version', '1.0')->get()->getRow();
            }
            if (! $policy || ! $this->db->tableExists('rating_match_type_weights')) continue;
            foreach (['self_reported' => 0.50, 'club_verified' => 0.75, 'league_verified' => 0.90, 'tournament_verified' => 1.00, 'official' => 1.00] as $type => $weight) {
                if (! $this->db->table('rating_match_type_weights')->where('policy_version_id', $policy->id)->where('match_type', $type)->countAllResults()) $this->db->table('rating_match_type_weights')->insert(['policy_version_id' => $policy->id, 'match_type' => $type, 'weight' => $weight, 'eligible' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down() {}
}
