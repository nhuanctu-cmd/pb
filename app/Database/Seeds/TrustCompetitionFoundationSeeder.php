<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Idempotent demo fixture for the five trust/competition foundations.
 *
 * Run: php spark db:seed TrustCompetitionFoundationSeeder
 */
class TrustCompetitionFoundationSeeder extends Seeder
{
    private string $now;
    private int $tenantId = 0;
    private int $actorId = 0;
    private array $players = [];

    public function run()
    {
        $this->now = date('Y-m-d H:i:s');
        $required = ['governance_authorities', 'governance_policies', 'rulesets', 'ruleset_versions', 'eligibility_policy_versions', 'seeding_policy_versions', 'draw_policy_versions', 'data_provenance_records', 'player_rating_provider_links', 'provider_rating_records', 'matches', 'match_participants', 'match_result_versions', 'match_results', 'match_sides', 'match_integrity_flags', 'appeals', 'result_correction_requests'];
        foreach ($required as $table) {
            if (! $this->db->tableExists($table)) { echo "Missing table {$table}. Run php spark migrate first.\n"; return; }
        }

        if ($this->db->table('tenants')->where('status', 'active')->countAllResults() === 0) $this->call('App\\Database\\Seeds\\DemoDataSeeder');
        $tenant = $this->db->table('tenants')->where('status', 'active')->where('deleted_at', null)->orderBy('id')->get(1)->getRow();
        if (! $tenant) { echo "No active tenant found.\n"; return; }
        $this->tenantId = (int) $tenant->id;

        $this->players = array_map('intval', array_column($this->db->table('players')->select('id')->where('tenant_id', $this->tenantId)->where('status', 'active')->where('deleted_at', null)->orderBy('id')->get(8)->getResultArray(), 'id'));
        if (count($this->players) < 4) {
            $this->call('App\\Database\\Seeds\\PlayerMembershipSeeder');
            $this->players = array_map('intval', array_column($this->db->table('players')->select('id')->where('tenant_id', $this->tenantId)->where('status', 'active')->where('deleted_at', null)->orderBy('id')->get(8)->getResultArray(), 'id'));
        }
        if (count($this->players) < 4) { echo "Need at least four active players in tenant {$this->tenantId}.\n"; return; }
        $this->actorId = (int) ($this->db->table('users')->where('tenant_id', $this->tenantId)->orderBy('id')->get(1)->getRow('id') ?? 0);

        $authorityId = $this->authority();
        $governancePolicyId = $this->governancePolicy($authorityId);
        $rulesetId = $this->ruleset($authorityId);
        $rulesetVersionId = $this->rulesetVersion($rulesetId);
        $policyIds = [
            'eligibility' => $this->policyVersion('eligibility_policy_versions', 'demo-eligibility', ['minimum_age' => 16, 'identity_required' => true]),
            'seeding' => $this->policyVersion('seeding_policy_versions', 'demo-seeding', ['method' => 'rating_then_random', 'rating_provider' => 'internal-v1']),
            'draw' => $this->policyVersion('draw_policy_versions', 'demo-draw', ['format' => 'single_elimination', 'best_of' => 1]),
        ];
        $matchId = $this->match($authorityId, $rulesetVersionId);
        $provenanceId = $this->provenance($matchId, $rulesetVersionId);
        $this->db->table('matches')->where('id', $matchId)->update(['provenance_id' => $provenanceId, 'ruleset_version_id' => $rulesetVersionId]);
        $providerId = $this->provider();
        $this->providerData($providerId);
        $this->governanceDecision($matchId, $authorityId, $governancePolicyId);
        $appealId = $this->appeal($matchId, $authorityId);
        $correctionId = $this->correction($matchId);
        $this->integrityFlag($matchId);
        $sanctionId = $this->sanction($authorityId, $rulesetVersionId, $policyIds['eligibility']);

        echo json_encode(['tenant_id' => $this->tenantId, 'authority_id' => $authorityId, 'ruleset_id' => $rulesetId, 'ruleset_version_id' => $rulesetVersionId, 'match_id' => $matchId, 'provenance_id' => $provenanceId, 'provider_id' => $providerId, 'appeal_id' => $appealId, 'correction_request_id' => $correctionId, 'sanction_id' => $sanctionId], JSON_UNESCAPED_UNICODE) . "\n";
    }

    private function authority(): int
    {
        $uuid = '00000000-0000-4000-8000-000000000001';
        $row = $this->db->table('governance_authorities')->where('uuid', $uuid)->get()->getRow();
        if ($row) return (int) $row->id;
        $this->db->table('governance_authorities')->insert(['uuid' => $uuid, 'name' => 'Demo National Pickleball Authority', 'authority_type' => 'NATIONAL_FEDERATION', 'country_code' => 'VN', 'scope_reference' => 'demo-foundation', 'status' => 'active', 'effective_from' => $this->now, 'created_by' => $this->actorId, 'created_at' => $this->now, 'updated_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function governancePolicy(int $authorityId): int
    {
        $row = $this->db->table('governance_policies')->where('authority_id', $authorityId)->where('code', 'demo-result-review')->where('version', '1.0')->get()->getRow();
        if ($row) return (int) $row->id;
        $rules = ['official_result_requires' => ['confirmed_result', 'authority_actor', 'provenance'], 'correction_requires' => ['reason', 'evidence', 'reviewer']];
        $this->db->table('governance_policies')->insert(['authority_id' => $authorityId, 'code' => 'demo-result-review', 'version' => '1.0', 'policy_type' => 'RESULT_REVIEW', 'rules' => $this->json($rules), 'content_hash' => hash('sha256', $this->json($rules)), 'effective_from' => $this->now, 'status' => 'active', 'created_by' => $this->actorId, 'created_at' => $this->now, 'updated_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function ruleset(int $authorityId): int
    {
        $row = $this->db->table('rulesets')->where('code', 'demo-pickleball-standard')->get()->getRow();
        if ($row) return (int) $row->id;
        $this->db->table('rulesets')->insert(['code' => 'demo-pickleball-standard', 'name' => 'Demo Pickleball Standard', 'discipline' => 'pickleball', 'authority_id' => $authorityId, 'status' => 'active', 'created_at' => $this->now, 'updated_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function rulesetVersion(int $rulesetId): int
    {
        $row = $this->db->table('ruleset_versions')->where('ruleset_id', $rulesetId)->where('version', '1.0')->get()->getRow();
        if ($row) return (int) $row->id;
        $content = ['best_of' => 1, 'game_to' => 11, 'win_by' => 2, 'score_cap' => 15, 'timeouts' => 2, 'walkover_eligible' => false];
        $this->db->table('ruleset_versions')->insert(['ruleset_id' => $rulesetId, 'version' => '1.0', 'content' => $this->json($content), 'content_hash' => hash('sha256', $this->json($content)), 'effective_from' => $this->now, 'status' => 'active', 'created_by' => $this->actorId, 'created_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function policyVersion(string $table, string $code, array $policy): int
    {
        $row = $this->db->table($table)->where('code', $code)->where('version', '1.0')->get()->getRow();
        if ($row) return (int) $row->id;
        $payload = $this->json($policy);
        $this->db->table($table)->insert(['code' => $code, 'version' => '1.0', 'policy' => $payload, 'content_hash' => hash('sha256', $payload), 'effective_from' => $this->now, 'status' => 'active', 'created_by' => $this->actorId, 'created_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function match(int $authorityId, int $rulesetVersionId): int
    {
        $row = $this->db->table('matches')->where('tenant_id', $this->tenantId)->where('source_type', 'friendly')->where('source_id', 981001)->get()->getRow();
        if ($row) return (int) $row->id;
        // public_id là khóa global, vì vậy fixture phải khác nhau theo tenant.
        // Giữ format UUID ổn định để chạy seed nhiều lần không tạo bản ghi trùng.
        $publicId = sprintf('00000000-0000-4000-8000-%012d', 981000 + $this->tenantId);
        $this->db->table('matches')->insert(['public_id' => $publicId, 'tenant_id' => $this->tenantId, 'source_type' => 'friendly', 'source_id' => 981001, 'source_code' => 'foundation-demo', 'discipline' => 'singles', 'competition_type' => 'demo_competition', 'source_organization_id' => $this->tenantId, 'status' => 'official', 'result_type' => 'normal', 'verification_status' => 'official', 'completed_at' => $this->now, 'created_by' => $this->actorId, 'metadata' => $this->json(['seed' => 'trust-competition-foundation']), 'created_at' => $this->now, 'updated_at' => $this->now]);
        $matchId = (int) $this->db->insertID();
        $this->db->table('match_sides')->insertBatch([['match_id' => $matchId, 'side_code' => 'A', 'side_order' => 1, 'result' => 'won', 'metadata' => $this->json(['players' => [$this->players[0]]]), 'created_at' => $this->now, 'updated_at' => $this->now], ['match_id' => $matchId, 'side_code' => 'B', 'side_order' => 2, 'result' => 'lost', 'metadata' => $this->json(['players' => [$this->players[1]]]), 'created_at' => $this->now, 'updated_at' => $this->now]]);
        $this->db->table('match_participants')->insertBatch([['match_id' => $matchId, 'player_id' => $this->players[0], 'side' => 1, 'participant_role' => 'player', 'result' => 'won', 'score' => '11-7', 'sort_order' => 0, 'created_at' => $this->now, 'updated_at' => $this->now], ['match_id' => $matchId, 'player_id' => $this->players[1], 'side' => 2, 'participant_role' => 'player', 'result' => 'lost', 'score' => '7-11', 'sort_order' => 0, 'created_at' => $this->now, 'updated_at' => $this->now]]);
        $this->db->table('match_games')->insert(['match_id' => $matchId, 'game_no' => 1, 'side_a_score' => 11, 'side_b_score' => 7, 'raw_score' => '11-7', 'created_at' => $this->now, 'updated_at' => $this->now]);
        $this->db->table('match_result_versions')->insert(['match_id' => $matchId, 'version_no' => 1, 'status' => 'official', 'result_type' => 'normal', 'winner_side' => 1, 'payload' => $this->json(['games' => [['side_a_score' => 11, 'side_b_score' => 7]], 'seed' => 'trust-competition-foundation']), 'submitted_by' => $this->actorId, 'confirmed_by' => $this->actorId, 'ruleset_version_id' => $rulesetVersionId, 'authority_id' => $authorityId, 'verified_by' => $this->actorId, 'verified_at' => $this->now, 'source' => 'DEMO_SEED', 'change_reason' => 'Foundation demo fixture', 'created_at' => $this->now]);
        $versionId = (int) $this->db->insertID();
        $this->db->table('match_results')->insert(['match_id' => $matchId, 'current_version_id' => $versionId, 'version_no' => 1, 'status' => 'official', 'result_type' => 'normal', 'winner_side' => 1, 'published_at' => $this->now, 'created_at' => $this->now, 'updated_at' => $this->now]);
        return $matchId;
    }

    private function provenance(int $matchId, int $policyVersionId): int
    {
        $row = $this->db->table('data_provenance_records')->where('entity_type', 'MATCH')->where('entity_id', $matchId)->where('source_type', 'DEMO_SEED')->get()->getRow();
        if ($row) return (int) $row->id;
        $this->db->table('data_provenance_records')->insert(['entity_type' => 'MATCH', 'entity_id' => $matchId, 'source_type' => 'DEMO_SEED', 'source_id' => 'foundation-demo', 'source_organization_id' => $this->tenantId, 'created_by' => $this->actorId, 'verified_by' => $this->actorId, 'verification_level' => 'OFFICIAL', 'external_reference' => 'demo://trust-competition-foundation/match/981001', 'policy_version_id' => $policyVersionId, 'metadata' => $this->json(['seed' => 'trust-competition-foundation']), 'created_at' => $this->now, 'verified_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function provider(): int
    {
        $row = $this->db->table('rating_providers')->where('code', 'demo-external-rating')->get()->getRow();
        if ($row) return (int) $row->id;
        $this->db->table('rating_providers')->insert(['code' => 'demo-external-rating', 'name' => 'Demo External Rating Provider', 'provider_type' => 'external', 'status' => 'active', 'config' => $this->json(['demo' => true]), 'created_at' => $this->now, 'updated_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function providerData(int $providerId): void
    {
        foreach (array_slice($this->players, 0, 2) as $index => $playerId) {
            $link = $this->db->table('player_rating_provider_links')->where('player_id', $playerId)->where('provider_id', $providerId)->get()->getRow();
            $data = ['external_player_id' => 'DEMO-EXT-' . $playerId, 'verification_status' => 'verified', 'consent_status' => 'granted', 'authorization_reference' => 'demo-consent-' . $playerId, 'linked_at' => $this->now, 'last_synced_at' => $this->now, 'sync_state' => 'active', 'metadata' => $this->json(['seed' => 'trust-competition-foundation']), 'updated_at' => $this->now];
            if ($link) $this->db->table('player_rating_provider_links')->where('id', $link->id)->update($data); else $this->db->table('player_rating_provider_links')->insert(['player_id' => $playerId, 'provider_id' => $providerId] + $data + ['created_at' => $this->now]);
            if (! $this->db->table('provider_rating_records')->where('external_record_id', 'DEMO-RATING-' . $playerId)->countAllResults()) $this->db->table('provider_rating_records')->insert(['player_id' => $playerId, 'provider_id' => $providerId, 'discipline' => 'singles', 'rating_value' => 3.25 + ($index * .15), 'rating_label' => $index ? 'Intermediate' : 'Advanced', 'external_record_id' => 'DEMO-RATING-' . $playerId, 'observed_at' => $this->now, 'synced_at' => $this->now, 'payload' => $this->json(['provider' => 'demo-external-rating']), 'created_at' => $this->now]);
        }
    }

    private function governanceDecision(int $matchId, int $authorityId, int $policyId): void
    {
        if ($this->db->table('governance_decisions')->where('subject_type', 'MATCH')->where('subject_id', $matchId)->where('decision', 'APPROVE')->countAllResults()) return;
        $this->db->table('governance_decisions')->insert(['uuid' => '00000000-0000-4000-8000-000000000002', 'subject_type' => 'MATCH', 'subject_id' => $matchId, 'authority_id' => $authorityId, 'policy_id' => $policyId, 'actor_id' => $this->actorId, 'decision' => 'APPROVE', 'reason' => 'Demo match passed official-result review.', 'evidence' => $this->json(['scorecard' => 'complete', 'provenance' => 'verified']), 'created_at' => $this->now]);
    }

    private function appeal(int $matchId, int $authorityId): int
    {
        $row = $this->db->table('appeals')->where('subject_type', 'MATCH')->where('subject_id', $matchId)->where('status', 'open')->get()->getRow();
        if ($row) return (int) $row->id;
        $this->db->table('appeals')->insert(['uuid' => '00000000-0000-4000-8000-000000000003', 'tenant_id' => $this->tenantId, 'subject_type' => 'MATCH', 'subject_id' => $matchId, 'opened_by' => $this->actorId, 'authority_id' => $authorityId, 'status' => 'open', 'reason' => 'Demo appeal for evidence-collection workflow.', 'created_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function correction(int $matchId): int
    {
        $row = $this->db->table('result_correction_requests')->where('match_id', $matchId)->where('status', 'open')->get()->getRow();
        if ($row) return (int) $row->id;
        $version = $this->db->table('match_results')->where('match_id', $matchId)->get()->getRow();
        $this->db->table('result_correction_requests')->insert(['match_id' => $matchId, 'original_result_version_id' => $version->current_version_id, 'requested_result' => $this->json(['winner_side' => 1, 'reason_code' => 'DEMO_REVIEW']), 'reason' => 'Demo correction request awaiting reviewer.', 'evidence' => $this->json(['video' => 'demo://match/981001']), 'requester_id' => $this->players[0], 'status' => 'open', 'created_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function integrityFlag(int $matchId): void
    {
        if ($this->db->table('match_integrity_flags')->where('match_id', $matchId)->where('flag_code', 'DEMO_VERIFIED')->countAllResults()) return;
        $this->db->table('match_integrity_flags')->insert(['match_id' => $matchId, 'player_id' => null, 'flag_code' => 'DEMO_VERIFIED', 'risk_score' => 0, 'status' => 'resolved', 'details' => $this->json(['seed' => 'trust-competition-foundation']), 'reviewed_by' => $this->actorId, 'reviewed_at' => $this->now, 'created_at' => $this->now, 'updated_at' => $this->now]);
    }

    private function sanction(int $authorityId, int $rulesetVersionId, int $policyVersionId): ?int
    {
        if (! $this->db->tableExists('tournaments')) return null;
        $tournament = $this->db->table('tournaments')->where('tenant_id', $this->tenantId)->orderBy('id')->get(1)->getRow();
        if (! $tournament) return null;
        $rankingAuthority = $this->db->table('ranking_authorities')->orderBy('id')->get(1)->getRow();
        if (! $rankingAuthority) return null;
        $rankingAuthorityId = (int) $rankingAuthority->id;
        $row = $this->db->table('tournament_sanctions')->where('tournament_id', $tournament->id)->where('ranking_authority_id', $rankingAuthorityId)->get()->getRow();
        if ($row) return (int) $row->id;
        $this->db->table('tournament_sanctions')->insert(['tournament_id' => $tournament->id, 'ranking_authority_id' => $rankingAuthorityId, 'sanction_id' => 'DEMO-SANCTION-' . (int) $tournament->id, 'status' => 'approved', 'point_multiplier' => 1.00, 'authority_id' => $authorityId, 'workflow_status' => 'approved', 'submitted_by' => $this->actorId, 'submitted_at' => $this->now, 'approved_by' => $this->actorId, 'approved_at' => $this->now, 'ruleset_version_id' => $rulesetVersionId, 'policy_snapshot' => $this->json(['eligibility_policy_version_id' => $policyVersionId]), 'created_at' => $this->now, 'updated_at' => $this->now]);
        return (int) $this->db->insertID();
    }

    private function json(array $value): string { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
}
