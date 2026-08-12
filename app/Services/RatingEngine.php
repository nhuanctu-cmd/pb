<?php

namespace App\Services;

use App\Models\MatchParticipantModel;
use App\Models\MatchResultModel;
use App\Models\RatingProviderModel;
use App\Models\UnifiedMatchModel;
use Config\Database;

/** Canonical discipline-aware rating engine. Legacy rating services remain compatibility-only. */
class RatingEngine
{
    protected $db;
    protected UnifiedMatchModel $matchModel;
    protected MatchResultModel $resultModel;
    protected MatchParticipantModel $participantModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->matchModel = model(UnifiedMatchModel::class);
        $this->resultModel = model(MatchResultModel::class);
        $this->participantModel = model(MatchParticipantModel::class);
    }

    public function processOfficialMatch(int $matchId, ?int $tenantId = null): array
    {
        if (! $this->foundationReady()) return ['success' => false, 'skipped' => true, 'message' => 'Rating Engine V1 migration chưa được chạy.'];
        $match = $this->findMatch($matchId, $tenantId);
        $result = $match ? $this->resultModel->where('match_id', $matchId)->first() : null;
        $version = $result && $result->current_version_id ? $this->db->table('match_result_versions')->where('id', $result->current_version_id)->get()->getRow() : null;
        $participants = $match ? $this->participantModel->where('match_id', $matchId)->orderBy('side')->orderBy('sort_order')->findAll() : [];
        if (! $match || ! $result || ! $version) return ['success' => false, 'message' => 'Không tìm thấy official match/result version.'];
        $payload = is_string($version->payload ?? null) ? json_decode($version->payload, true) : (array) ($version->payload ?? []);
        $discipline = $this->disciplineCode($match, $payload);
        $integrity = service('ratingIntegrityService')->evaluate($match, $participants, $payload);
        service('ratingIntegrityService')->record((int) ($match->tenant_id ?: $tenantId), $matchId, $integrity['flags']);
        $blockingIntegrity = array_values(array_filter($integrity['flags'], static fn (array $flag): bool => (float) ($flag['risk_score'] ?? 0) >= 80));
        if ($blockingIntegrity) {
            return ['success' => false, 'code' => 'RATING_INTEGRITY_REVIEW', 'reasons' => array_values(array_unique(array_map(static fn (array $flag): string => (string) $flag['code'], $blockingIntegrity))), 'review_required' => true];
        }
        $eligibility = service('ratingEligibilityService')->validate(['match' => $match, 'result' => $result, 'participants' => $participants, 'discipline' => $discipline, 'games' => $payload['games'] ?? []]);
        if (! $eligibility['eligible']) return ['success' => false, 'code' => 'RATING_INELIGIBLE', 'reasons' => $eligibility['reasons']];
        $provider = $this->db->table('rating_providers')->where('code', 'internal-v1')->where('status', 'active')->get()->getRow();
        $disciplineRow = $this->db->table('rating_disciplines')->where('code', $discipline)->where('active', 1)->get()->getRow();
        if (! $provider || ! $disciplineRow) return ['success' => false, 'message' => 'Rating provider/discipline chưa sẵn sàng.'];
        $policy = $this->activePolicy((int) $provider->id, (int) $disciplineRow->id);
        if (! $policy) return ['success' => false, 'message' => 'Chưa có rating policy active cho discipline.'];
        $configuration = is_string($policy->configuration) ? (json_decode($policy->configuration, true) ?: []) : (array) $policy->configuration;
        $keys = array_map(fn ($participant) => $this->impactKey((int) $provider->id, $matchId, (int) $version->id, (int) $policy->id, (int) $participant->player_id), $participants);
        $existing = $this->db->table('rating_transactions')->whereIn('idempotency_key', $keys)->countAllResults();
        if ($existing === count($participants)) return ['success' => true, 'idempotent' => true, 'created' => 0, 'discipline' => $discipline];
        if ($existing > 0) return ['success' => false, 'message' => 'Rating transaction đang ở trạng thái một phần; cần rebuild/review.'];

        $tenant = (int) ($match->tenant_id ?: $tenantId);
        if ($tenant <= 0) return ['success' => false, 'message' => 'Match cần tenant để cập nhật rating.'];
        $this->db->transStart();
        $sideA = array_values(array_filter($participants, static fn ($participant): bool => (int) $participant->side === 1));
        $sideB = array_values(array_filter($participants, static fn ($participant): bool => (int) $participant->side === 2));
        $profiles = [];
        foreach ($participants as $participant) {
            $profiles[(int) $participant->player_id] = $this->getOrCreateProfile((int) ($match->tenant_id ?: $tenantId), (int) $participant->player_id, (int) $provider->id, (int) $disciplineRow->id, $configuration);
        }
        $sideData = static function (array $side) use ($profiles): array {
            return array_map(static fn ($participant): array => ['rating' => (float) ($profiles[(int) $participant->player_id]->rating_value ?? 3.000), 'reliability' => (float) ($profiles[(int) $participant->player_id]->reliability_score ?? 0)], $side);
        };
        $matchType = $this->matchType($match, $payload);
        $matchWeight = $this->matchWeight((int) $policy->id, $matchType);
        $calculated = service('ratingCalculator')->calculate(['side_a' => $sideData($sideA), 'side_b' => $sideData($sideB), 'games' => $payload['games'] ?? [], 'winner_side' => $result->winner_side, 'match_weight' => $matchWeight, 'configuration' => $configuration]);
        $created = 0;
        $now = date('Y-m-d H:i:s');
        foreach ($participants as $participant) {
            $playerId = (int) $participant->player_id;
            $profile = $profiles[$playerId];
            $before = (float) ($profile->rating_value ?? $configuration['initial_rating'] ?? 3.000);
            $delta = (int) $participant->side === 1 ? $calculated['delta_a'] : $calculated['delta_b'];
            $after = round(max(2.000, min(5.999, $before + $delta)), 3);
            $opponentIds = array_values(array_map(static fn ($opponent): int => (int) $opponent->player_id, array_filter($participants, static fn ($other): bool => (int) $other->side !== (int) $participant->side)));
            $partnerIds = array_values(array_map(static fn ($partner): int => (int) $partner->player_id, array_filter($participants, static fn ($other): bool => (int) $other->side === (int) $participant->side && (int) $other->player_id !== $playerId)));
            $reliabilityBefore = (float) ($profile->reliability_score ?? 0);
            $facts = service('ratingReliabilityEngine')->factsForNextMatch($tenant, $playerId, (int) $disciplineRow->id, $opponentIds, $partnerIds, $matchType, $now);
            $facts['rated_match_count']++; $facts['verified_match_count']++;
            $reliabilityAfter = service('ratingReliabilityEngine')->calculate($facts, $configuration, $now);
            $transaction = [
                'tenant_id' => $tenant, 'player_id' => $playerId, 'provider_id' => $provider->id, 'discipline_id' => $disciplineRow->id,
                'match_id' => $matchId, 'match_result_version_id' => $version->id, 'rating_policy_version_id' => $policy->id, 'transaction_type' => 'impact',
                'before_rating' => $before, 'after_rating' => $after, 'rating_delta' => $delta, 'expected_performance' => (int) $participant->side === 1 ? $calculated['expected_a'] : 1 - $calculated['expected_a'],
                'actual_performance' => (int) $participant->side === 1 ? $calculated['actual_a'] : $calculated['actual_b'], 'reliability_before' => $reliabilityBefore,
                'reliability_after' => $reliabilityAfter['score'], 'match_weight' => $matchWeight, 'reason' => 'OFFICIAL_MATCH_RESULT', 'status' => 'applied',
                'idempotency_key' => $this->impactKey((int) $provider->id, $matchId, (int) $version->id, (int) $policy->id, $playerId), 'processed_at' => $now,
                'metadata' => json_encode(['discipline' => $discipline, 'match_type' => $matchType, 'opponent_ids' => $opponentIds, 'partner_ids' => $partnerIds, 'games_count' => $calculated['games_count'], 'score_margin' => $calculated['score_margin'], 'calculation_version' => 'rating-v1'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
            ];
            if (! $this->db->table('rating_transactions')->insert($transaction)) { $this->db->transRollback(); return ['success' => false, 'message' => 'Không ghi được rating transaction.']; }
            $transactionId = $this->db->insertID();
            $band = service('skillBandResolver')->resolveStable($after, $profile->skill_band_id ? (int) $profile->skill_band_id : null, (float) ($configuration['skill_band_hysteresis'] ?? 0.05));
            $established = $reliabilityAfter['score'] >= (float) ($configuration['established_reliability'] ?? 70);
            $profileData = ['rating_value' => $after, 'skill_band_id' => $band->id ?? null, 'reliability_score' => $reliabilityAfter['score'], 'status' => $established ? 'established' : 'provisional', 'rated_match_count' => ((int) ($profile->rated_match_count ?? 0)) + 1, 'verified_match_count' => ((int) ($profile->verified_match_count ?? 0)) + 1, 'last_rated_match_at' => $now, 'highest_rating' => max((float) ($profile->highest_rating ?? $after), $after), 'lowest_rating' => min((float) ($profile->lowest_rating ?? $after), $after), 'established_at' => $established ? ($profile->established_at ?: $now) : null, 'calculated_at' => $now, 'updated_at' => $now];
            $this->db->table('player_rating_profiles')->where('id', $profile->id)->update($profileData);
            $this->db->table('rating_reliability_snapshots')->insert(['profile_id' => $profile->id, 'rating_transaction_id' => $transactionId, 'score' => $reliabilityAfter['score'], 'components' => json_encode($reliabilityAfter['components']), 'reason' => 'OFFICIAL_MATCH_RESULT', 'as_of' => $now, 'created_at' => $now]);
            $created++;
        }
        $this->db->transComplete();
        if (! $this->db->transStatus()) return ['success' => false, 'message' => 'Rating transaction failed.'];
        return ['success' => true, 'created' => $created, 'discipline' => $discipline, 'calculation' => $calculated];
    }

    public function getPublicRating(int $tenantId, int $playerId, string $discipline = 'singles'): ?array
    {
        if (! $this->foundationReady()) return null;
        $provider = $this->db->table('rating_providers')->where('code', 'internal-v1')->get()->getRow();
        $disciplineRow = $this->db->table('rating_disciplines')->where('code', $discipline)->get()->getRow();
        if (! $provider || ! $disciplineRow) return null;
        $row = $this->db->table('player_rating_profiles')->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('provider_id', $provider->id)->where('discipline_id', $disciplineRow->id)->get()->getRow();
        if (! $row) return ['rating' => null, 'reliability' => 0, 'status' => 'nr', 'skill_band' => 'NR', 'skill_label' => 'Not Rated', 'match_count' => 0, 'last_match_at' => null];
        $band = $row->skill_band_id ? $this->db->table('skill_level_bands')->where('id', $row->skill_band_id)->get()->getRow() : null;
        return ['rating' => $row->rating_value !== null ? (float) $row->rating_value : null, 'reliability' => (float) $row->reliability_score, 'status' => $row->status, 'skill_band' => $band->code ?? 'NR', 'skill_label' => $band->name ?? 'Not Rated', 'match_count' => (int) $row->rated_match_count, 'last_match_at' => $row->last_rated_match_at];
    }

    public function history(int $tenantId, int $playerId, string $discipline = 'singles', int $limit = 50): array
    {
        if (! $this->foundationReady()) return [];
        $disciplineRow = $this->db->table('rating_disciplines')->where('code', $discipline)->get()->getRow();
        $provider = $this->db->table('rating_providers')->where('code', 'internal-v1')->get()->getRow();
        if (! $disciplineRow || ! $provider) return [];
        return $this->db->table('rating_transactions')
            ->where('tenant_id', $tenantId)
            ->where('player_id', $playerId)
            ->where('provider_id', $provider->id)
            ->where('discipline_id', $disciplineRow->id)
            ->whereIn('transaction_type', ['seed', 'impact', 'replacement', 'adjustment', 'reversal'])
            ->where('status', 'applied')
            ->orderBy('created_at', 'DESC')
            ->limit(max(1, min(300, $limit)))
            ->get()
            ->getResult();
    }

    public function reverseMatch(int $matchId, int $versionId, ?int $tenantId = null): array
    {
        if (! $this->foundationReady()) return ['success' => false, 'message' => 'Rating foundation migration chưa được chạy.'];
        $rows = $this->db->table('rating_transactions')->where('match_id', $matchId)->where('match_result_version_id', $versionId)->where('transaction_type', 'impact')->where('status', 'applied')->get()->getResult();
        if (! $rows) return ['success' => true, 'reversed' => 0];
        $this->db->transStart();
        foreach ($rows as $row) {
            $key = 'reversal:' . $row->id;
            if ($this->db->table('rating_transactions')->where('idempotency_key', $key)->countAllResults()) continue;
            $this->db->table('rating_transactions')->insert(['tenant_id' => $row->tenant_id, 'player_id' => $row->player_id, 'provider_id' => $row->provider_id, 'discipline_id' => $row->discipline_id, 'match_id' => $row->match_id, 'match_result_version_id' => $row->match_result_version_id, 'rating_policy_version_id' => $row->rating_policy_version_id, 'transaction_type' => 'reversal', 'before_rating' => $row->after_rating, 'after_rating' => $row->before_rating, 'rating_delta' => -((float) $row->rating_delta), 'expected_performance' => $row->expected_performance, 'actual_performance' => $row->actual_performance, 'reliability_before' => $row->reliability_after, 'reliability_after' => $row->reliability_before, 'match_weight' => $row->match_weight, 'reason' => 'RESULT_CORRECTION_REVERSAL', 'status' => 'applied', 'idempotency_key' => $key, 'processed_at' => date('Y-m-d H:i:s'), 'metadata' => json_encode(['original_transaction_id' => $row->id]), 'created_at' => date('Y-m-d H:i:s')]);
            $this->db->table('rating_transactions')->where('id', $row->id)->update(['status' => 'reversed']);
        }
        $this->db->transComplete();
        return ['success' => $this->db->transStatus(), 'reversed' => count($rows), 'requires_rebuild' => true];
    }

    private function foundationReady(): bool { return $this->db->tableExists('player_rating_profiles') && $this->db->tableExists('rating_transactions'); }

    private function findMatch(int $matchId, ?int $tenantId): ?object
    {
        $builder = $this->matchModel->where('id', $matchId);
        if ($tenantId !== null && $tenantId > 0) $builder->where('tenant_id', $tenantId);
        return $builder->first();
    }

    private function disciplineCode(object $match, array $payload): string
    {
        $discipline = strtolower((string) ($payload['discipline'] ?? $match->discipline ?? 'singles'));
        if ($discipline === 'pickleball') $discipline = 'singles';
        if ($discipline === 'mixed') $discipline = 'mixed_doubles';
        return in_array($discipline, ['singles', 'doubles', 'mixed_doubles'], true) ? $discipline : 'singles';
    }

    private function activePolicy(int $providerId, int $disciplineId): ?object
    {
        return $this->db->table('rating_policy_versions')->where('provider_id', $providerId)->where('discipline_id', $disciplineId)->where('status', 'active')->where('effective_from <=', date('Y-m-d H:i:s'))->groupStart()->where('effective_to >=', date('Y-m-d H:i:s'))->orWhere('effective_to', null)->groupEnd()->orderBy('effective_from', 'DESC')->get()->getRow();
    }

    private function matchType(object $match, array $payload): string
    {
        if (($match->verification_status ?? '') === 'official') return 'official';
        return match ($match->source_type ?? 'manual') { 'tournament' => 'tournament_verified', 'league' => 'league_verified', 'club_match' => 'club_verified', default => 'self_reported' };
    }

    private function matchWeight(int $policyId, string $matchType): float
    {
        $row = $this->db->table('rating_match_type_weights')->where('policy_version_id', $policyId)->where('match_type', $matchType)->where('eligible', 1)->get()->getRow();
        return $row ? (float) $row->weight : 1.0;
    }

    private function getOrCreateProfile(int $tenantId, int $playerId, int $providerId, int $disciplineId, array $configuration): object
    {
        $row = $this->db->query('SELECT * FROM player_rating_profiles WHERE tenant_id = ? AND player_id = ? AND provider_id = ? AND discipline_id = ? LIMIT 1 FOR UPDATE', [$tenantId, $playerId, $providerId, $disciplineId])->getRow();
        if ($row) return $row;
        $seed = service('initialRatingService')->forPlayer($tenantId, $playerId, $disciplineId, $configuration);
        $this->db->table('player_rating_profiles')->insert(['tenant_id' => $tenantId, 'player_id' => $playerId, 'provider_id' => $providerId, 'discipline_id' => $disciplineId, 'rating_value' => $seed['initial_rating'], 'skill_band_id' => service('skillBandResolver')->id($seed['initial_rating']), 'reliability_score' => $seed['initial_reliability'], 'status' => 'provisional', 'highest_rating' => $seed['initial_rating'], 'lowest_rating' => $seed['initial_rating'], 'calculated_at' => date('Y-m-d H:i:s'), 'metadata' => json_encode(['seed' => $seed], JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        return $this->db->table('player_rating_profiles')->where('id', $this->db->insertID())->get()->getRow();
    }

    private function impactKey(int $providerId, int $matchId, int $versionId, int $policyId, int $playerId): string { return implode(':', ['impact', $providerId, $matchId, $versionId, $policyId, $playerId]); }
}
