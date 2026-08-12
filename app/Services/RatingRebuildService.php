<?php

namespace App\Services;

use Config\Database;

/** Deterministic, read-first rebuild over the immutable official result stream. */
class RatingRebuildService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function run(array $filters = []): array
    {
        if (! $this->db->tableExists('player_rating_profiles') || ! $this->db->tableExists('rating_transactions')) return ['success' => false, 'message' => 'Rating foundation migration chưa được chạy.'];
        $tenantId = isset($filters['tenant_id']) && (int) $filters['tenant_id'] > 0 ? (int) $filters['tenant_id'] : null;
        $playerId = isset($filters['player_id']) && (int) $filters['player_id'] > 0 ? (int) $filters['player_id'] : null;
        $disciplineFilter = $filters['discipline'] ?? null;
        $dryRun = ! empty($filters['dry_run']);
        $provider = $this->db->table('rating_providers')->where('code', $filters['provider'] ?? 'internal-v1')->where('status', 'active')->get()->getRow();
        if (! $provider) return ['success' => false, 'message' => 'Rating provider không tồn tại.'];
        $disciplines = $this->db->table('rating_disciplines')->where('active', 1)->get()->getResult();
        $disciplineMap = [];
        foreach ($disciplines as $discipline) $disciplineMap[$discipline->code] = $discipline;
        if ($disciplineFilter && ! isset($disciplineMap[$disciplineFilter])) return ['success' => false, 'message' => 'Discipline không hợp lệ.'];

        $matches = $this->db->table('matches')->where('status', 'official')->where('verification_status', 'official');
        // A rebuild is an operational write and can never widen into another
        // tenant. A platform-wide rebuild must be invoked once per tenant by
        // the orchestrator, not by passing a missing tenant accidentally.
        if ($tenantId !== null) $matches->where('tenant_id', $tenantId);
        else if (! empty($filters['require_tenant'])) return ['success' => false, 'message' => 'Rating rebuild cần tenant_id khi chạy trong tenant context.'];
        $matches = $matches->orderBy('completed_at', 'ASC')->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->get()->getResult();
        $states = [];
        $processed = 0;
        $skipped = [];
        foreach ($matches as $match) {
            $matchDate = strtotime((string) ($match->completed_at ?: $match->created_at));
            if (! empty($filters['from']) && $matchDate < strtotime((string) $filters['from'])) continue;
            if (! empty($filters['to']) && $matchDate > strtotime((string) $filters['to'])) continue;
            $discipline = strtolower((string) ($match->discipline ?: 'singles'));
            if ($discipline === 'mixed') $discipline = 'mixed_doubles';
            if (! isset($disciplineMap[$discipline]) || ($disciplineFilter && $discipline !== $disciplineFilter)) continue;
            $result = $this->db->table('match_results')->where('match_id', $match->id)->get()->getRow();
            $version = $result && $result->current_version_id ? $this->db->table('match_result_versions')->where('id', $result->current_version_id)->get()->getRow() : null;
            if (! $result || $result->status !== 'official' || ! $version) { $skipped[] = (int) $match->id; continue; }
            $payload = is_string($version->payload ?? null) ? (json_decode($version->payload, true) ?: []) : (array) ($version->payload ?? []);
            if (isset($payload['discipline'])) $discipline = $payload['discipline'] === 'mixed' ? 'mixed_doubles' : $payload['discipline'];
            $participants = $this->db->table('match_participants')->where('match_id', $match->id)->orderBy('side')->orderBy('sort_order')->get()->getResult();
            if (count($participants) < 2 || ($playerId !== null && ! in_array($playerId, array_map(static fn ($p): int => (int) $p->player_id, $participants), true))) continue;
            $policy = $this->policy((int) $provider->id, (int) $disciplineMap[$discipline]->id);
            if (! $policy) { $skipped[] = (int) $match->id; continue; }
            $configuration = json_decode((string) $policy->configuration, true) ?: [];
            $sideA = array_values(array_filter($participants, static fn ($p): bool => (int) $p->side === 1));
            $sideB = array_values(array_filter($participants, static fn ($p): bool => (int) $p->side === 2));
            $stateData = function (array $side) use (&$states, $match, $provider, $disciplineMap, $discipline, $configuration): array {
                return array_map(function ($participant) use (&$states, $match, $provider, $disciplineMap, $discipline, $configuration): array {
                    $key = $this->stateKey((int) ($match->tenant_id ?: 0), (int) $participant->player_id, $discipline);
                    if (! isset($states[$key])) $states[$key] = ['rating' => (float) ($configuration['initial_rating'] ?? 3.000), 'reliability' => 0.0, 'count' => 0, 'verified' => 0];
                    return ['rating' => $states[$key]['rating'], 'reliability' => $states[$key]['reliability']];
                }, $side);
            };
            $calculated = service('ratingCalculator')->calculate(['side_a' => $stateData($sideA), 'side_b' => $stateData($sideB), 'games' => $payload['games'] ?? [], 'winner_side' => $result->winner_side, 'match_weight' => $this->matchWeight((int) $policy->id), 'configuration' => $configuration]);
            foreach ($participants as $participant) {
                $key = $this->stateKey((int) ($match->tenant_id ?: 0), (int) $participant->player_id, $discipline);
                $delta = (int) $participant->side === 1 ? $calculated['delta_a'] : $calculated['delta_b'];
                $states[$key]['rating'] = round(max(2.000, min(5.999, $states[$key]['rating'] + $delta)), 3);
                $states[$key]['count']++;
                $states[$key]['verified']++;
                $states[$key]['reliability'] = service('ratingReliabilityEngine')->calculate(['rated_match_count' => $states[$key]['count'], 'verified_match_count' => $states[$key]['verified'], 'opponent_count' => $states[$key]['count'], 'competition_type_count' => 1, 'last_rated_match_at' => $match->completed_at ?: $match->created_at], $configuration, $match->completed_at ?: $match->created_at)['score'];
            }
            $processed++;
        }
        if (! $dryRun) $this->persist($provider, $states, $tenantId, $playerId, $disciplineFilter, $filters, $processed);
        return ['success' => true, 'dry_run' => $dryRun, 'processed_matches' => $processed, 'skipped_matches' => $skipped, 'profiles' => array_values($states)];
    }

    private function persist(object $provider, array $states, ?int $tenantId, ?int $playerId, ?string $discipline, array $filters, int $processed): void
    {
        $this->db->transStart();
        foreach ($states as $key => $state) {
            [$stateTenant, $statePlayer, $stateDiscipline] = explode(':', $key, 3);
            $disciplineRow = $this->db->table('rating_disciplines')->where('code', $stateDiscipline)->get()->getRow();
            if (! $disciplineRow) continue;
            $policy = $this->policy((int) $provider->id, (int) $disciplineRow->id);
            $configuration = $policy ? (json_decode((string) $policy->configuration, true) ?: []) : [];
            $band = service('skillBandResolver')->resolve($state['rating']);
            $status = $state['reliability'] >= (float) ($configuration['established_reliability'] ?? 70) ? 'established' : 'provisional';
            $existing = $this->db->table('player_rating_profiles')->where('tenant_id', (int) $stateTenant)->where('player_id', (int) $statePlayer)->where('provider_id', $provider->id)->where('discipline_id', $disciplineRow->id)->get()->getRow();
            $data = ['rating_value' => $state['rating'], 'skill_band_id' => $band->id ?? null, 'reliability_score' => $state['reliability'], 'status' => $status, 'rated_match_count' => $state['count'], 'verified_match_count' => $state['verified'], 'highest_rating' => $state['rating'], 'lowest_rating' => $state['rating'], 'calculated_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            if ($existing) $this->db->table('player_rating_profiles')->where('id', $existing->id)->update($data);
            else $this->db->table('player_rating_profiles')->insert(array_merge($data, ['tenant_id' => (int) $stateTenant, 'player_id' => (int) $statePlayer, 'provider_id' => $provider->id, 'discipline_id' => $disciplineRow->id, 'created_at' => date('Y-m-d H:i:s')]));
        }
        if ($this->db->tableExists('rating_rebuild_jobs')) $this->db->table('rating_rebuild_jobs')->insert(['rating_provider_id' => $provider->id, 'tenant_id' => $tenantId, 'player_id' => $playerId, 'status' => 'completed', 'payload' => json_encode(array_merge($filters, ['processed_matches' => $processed]), JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        $this->db->transComplete();
    }

    private function policy(int $providerId, int $disciplineId): ?object { return $this->db->table('rating_policy_versions')->where('provider_id', $providerId)->where('discipline_id', $disciplineId)->where('status', 'active')->orderBy('effective_from', 'DESC')->get()->getRow(); }
    private function matchWeight(int $policyId): float { $row = $this->db->table('rating_match_type_weights')->where('policy_version_id', $policyId)->where('match_type', 'official')->where('eligible', 1)->get()->getRow(); return $row ? (float) $row->weight : 1.0; }
    private function stateKey(int $tenantId, int $playerId, string $discipline): string { return implode(':', [$tenantId, $playerId, $discipline]); }
}
