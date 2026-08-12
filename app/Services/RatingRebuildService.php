<?php

namespace App\Services;

use Config\Database;

/**
 * Deterministic, tenant-aware rebuild over the immutable official result stream.
 */
class RatingRebuildService
{
    protected $db;
    protected const RUN_SUCCESS_MESSAGE = 'Rebuild complete';
    protected const DRIFT_THRESHOLD_PERCENT = 10;
    private array $rebuildJobColumns = [];

    public function __construct()
    {
        $this->db = Database::connect();
        $this->rebuildJobColumns = $this->db->tableExists('rating_rebuild_jobs')
            ? array_flip($this->db->getFieldNames('rating_rebuild_jobs'))
            : [];
    }

    public function queueFromMatch(int $tenantId, int $fromMatchId, ?int $playerId = null, string $reason = 'governance_sync', array $metadata = []): ?int
    {
        if (! $this->db->tableExists('rating_rebuild_jobs')) {
            return null;
        }
        $provider = $this->activeProvider();
        if (! $provider) {
            return null;
        }

        $payload = array_merge([
            'reason' => $reason,
            'trigger' => 'governance_data_sync',
            'from_match_id' => $fromMatchId,
            'player_id' => $playerId,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'requested_at' => date('Y-m-d H:i:s'),
        ], $metadata);
        $payload['rebuild_job_key'] = $this->buildRebuildJobKey($tenantId, $fromMatchId, $playerId, $payload['reason'], $metadata);
        $idempotencyKey = sha1(json_encode($payload['rebuild_job_key'], JSON_UNESCAPED_UNICODE));
        $payload['rebuild_job_key_hash'] = $idempotencyKey;
        $existing = null;

        if ($this->hasRebuildJobColumn('idempotency_key')) {
            $existingQuery = $this->db->table('rating_rebuild_jobs')
                ->where('tenant_id', $tenantId > 0 ? $tenantId : null)
                ->where('from_match_id', $fromMatchId);
            if ($playerId !== null) {
                $existingQuery->where('player_id', $playerId);
            }
            $existing = $existingQuery
                ->where('idempotency_key', $idempotencyKey)
                ->whereIn('status', ['queued', 'running'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getRow();
        } else {
            $existingQuery = $this->db->table('rating_rebuild_jobs')
                ->where('tenant_id', $tenantId > 0 ? $tenantId : null)
                ->where('from_match_id', $fromMatchId);
            if ($playerId !== null) {
                $existingQuery->where('player_id', $playerId);
            }
            $existingRows = $existingQuery
                ->whereIn('status', ['queued', 'running'])
                ->orderBy('id', 'DESC')
                ->get()
                ->getResult();
            $existing = null;
            foreach ($existingRows as $row) {
                $rowPayload = is_string($row->payload) ? (json_decode($row->payload, true) ?: []) : [];
                if (($rowPayload['rebuild_job_key_hash'] ?? null) === $idempotencyKey) {
                    $existing = $row;
                    break;
                }
                if (
                    ! isset($rowPayload['rebuild_job_key_hash']) &&
                    isset($rowPayload['reason'], $rowPayload['provider'], $rowPayload['discipline']) &&
                    $rowPayload['reason'] === $payload['reason'] &&
                    $rowPayload['provider'] === ($payload['provider'] ?? 'internal-v1') &&
                    $rowPayload['discipline'] === ($payload['discipline'] ?? null)
                ) {
                    $existing = $row;
                    break;
                }
            }
        }

        if ($existing) {
            return (int) $existing->id;
        }

        $insert = [
            'rating_provider_id' => (int) $provider->id,
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'player_id' => $playerId,
            'from_match_id' => $fromMatchId,
            'status' => 'queued',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($this->hasRebuildJobColumn('idempotency_key')) {
            $insert['idempotency_key'] = $idempotencyKey;
        }

        $inserted = $this->db->table('rating_rebuild_jobs')->insert($insert);
        if (! $inserted) {
            return null;
        }
        return (int) $this->db->insertID();
    }

    public function run(array $filters = [], bool $createJob = true): array
    {
        $migrationGuard = $this->migrationGuard();

        if (! $this->db->tableExists('player_rating_profiles') || ! $this->db->tableExists('rating_transactions')) {
            return ['success' => false, 'message' => 'Rating foundation migration chưa được chạy.'];
        }

        $tenantId = isset($filters['tenant_id']) && (int) $filters['tenant_id'] > 0 ? (int) $filters['tenant_id'] : null;
        $playerId = isset($filters['player_id']) && (int) $filters['player_id'] > 0 ? (int) $filters['player_id'] : null;
        $disciplineFilter = $filters['discipline'] ?? null;
        $fromMatchId = isset($filters['from_match_id']) ? (int) $filters['from_match_id'] : 0;
        $toMatchId = isset($filters['to_match_id']) ? (int) $filters['to_match_id'] : 0;
        if ($fromMatchId > 0 && $toMatchId > 0 && $fromMatchId > $toMatchId) {
            return ['success' => false, 'message' => 'from_match_id phải nhỏ hơn hoặc bằng to_match_id.'];
        }

        $fromDate = ! empty($filters['from']) ? strtotime((string) $filters['from']) : false;
        $toDate = ! empty($filters['to']) ? strtotime((string) $filters['to']) : false;
        if (! empty($filters['from']) && $fromDate === false) return ['success' => false, 'message' => 'Tham số --from không đúng định dạng ngày/thời gian.'];
        if (! empty($filters['to']) && $toDate === false) return ['success' => false, 'message' => 'Tham số --to không đúng định dạng ngày/thời gian.'];
        $dryRun = ! empty($filters['dry_run']);
        $provider = $this->db->table('rating_providers')
            ->where('code', $filters['provider'] ?? 'internal-v1')
            ->where('status', 'active')
            ->get()
            ->getRow();
        if (! $provider) {
            return ['success' => false, 'message' => 'Rating provider không tồn tại.'];
        }

        $disciplines = $this->db->table('rating_disciplines')->where('active', 1)->get()->getResult();
        $disciplineMap = [];
        foreach ($disciplines as $discipline) {
            $disciplineMap[$discipline->code] = $discipline;
        }
        if ($disciplineFilter && ! isset($disciplineMap[$disciplineFilter])) {
            return ['success' => false, 'message' => 'Discipline không hợp lệ.'];
        }

        $matches = $this->db->table('matches')
            ->where('status', 'official')
            ->where('verification_status', 'official');

        if ($tenantId !== null) {
            $matches->where('tenant_id', $tenantId);
        } elseif (! empty($filters['require_tenant'])) {
            return ['success' => false, 'message' => 'Rating rebuild cần tenant_id khi chạy trong tenant context.'];
        }
        if ($fromMatchId > 0) $matches->where('id >=', $fromMatchId);
        if ($toMatchId > 0) $matches->where('id <=', $toMatchId);

        $matches = $matches->orderBy('completed_at', 'ASC')->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->get()->getResult();

        $createSummary = $this->buildRunFilters($filters, $tenantId, $playerId, $fromMatchId, $toMatchId, $disciplineFilter, $dryRun);
        $jobId = null;
        if ($createJob) {
            $jobId = $this->createRebuildJob(
                (int) $provider->id,
                $tenantId,
                $playerId,
                array_merge($createSummary, ['requested' => date('Y-m-d H:i:s')]),
                'running'
            );
            if ($jobId === null && ! $dryRun) {
                return ['success' => false, 'message' => 'Không thể tạo job rebuild mới.'];
            }
        }

        if (! $dryRun && $jobId && ! empty($filters['queue_job_id'])) {
            $this->updateRebuildJobStatus((int) $filters['queue_job_id'], 'running', ['message' => 'Rebuild running.', 'attempt' => (int) $this->nextAttempt((int) $filters['queue_job_id'])], true);
            $jobId = (int) $filters['queue_job_id'];
        }

        $states = [];
        $processed = 0;
        $skipped = [];
        $drift = [];

        foreach ($matches as $match) {
            $matchDate = strtotime((string) ($match->completed_at ?: $match->created_at));
            if (! empty($filters['from']) && $fromDate !== false && $matchDate < $fromDate) {
                continue;
            }
            if (! empty($filters['to']) && $toDate !== false && $matchDate > $toDate) {
                continue;
            }

            $discipline = strtolower((string) ($match->discipline ?: 'singles'));
            if ($discipline === 'mixed') {
                $discipline = 'mixed_doubles';
            }
            if (! isset($disciplineMap[$discipline]) || ($disciplineFilter && $discipline !== $disciplineFilter)) {
                continue;
            }

            $result = $this->db->table('match_results')->where('match_id', $match->id)->get()->getRow();
            $version = $result && $result->current_version_id ? $this->db->table('match_result_versions')->where('id', $result->current_version_id)->get()->getRow() : null;
            if (! $result || $result->status !== 'official' || ! $version) {
                $skipped[] = (int) $match->id;
                continue;
            }

            $payload = is_string($version->payload ?? null) ? (json_decode($version->payload, true) ?: []) : (array) ($version->payload ?? []);
            if (isset($payload['discipline'])) {
                $discipline = $payload['discipline'] === 'mixed' ? 'mixed_doubles' : $payload['discipline'];
            }
            if (! isset($disciplineMap[$discipline])) {
                $skipped[] = (int) $match->id;
                continue;
            }

            $participants = $this->db->table('match_participants')
                ->where('match_id', $match->id)
                ->orderBy('side')
                ->orderBy('sort_order')
                ->get()
                ->getResult();
            if (count($participants) < 2) {
                $skipped[] = (int) $match->id;
                continue;
            }
            if ($playerId !== null) {
                $hasTarget = array_filter($participants, static fn ($p): bool => (int) $p->player_id === $playerId);
                if (! $hasTarget) {
                    continue;
                }
            }

            $policy = $this->policy((int) $provider->id, (int) $disciplineMap[$discipline]->id);
            if (! $policy) {
                $skipped[] = (int) $match->id;
                continue;
            }
            $configuration = json_decode((string) $policy->configuration, true) ?: [];
            $sideA = array_values(array_filter($participants, static fn ($p): bool => (int) $p->side === 1));
            $sideB = array_values(array_filter($participants, static fn ($p): bool => (int) $p->side === 2));
            $stateData = function (array $side) use (&$states, $match, $provider, $discipline, $configuration): array {
                return array_map(function ($participant) use (&$states, $match, $provider, $discipline, $configuration): array {
                    $key = $this->stateKey((int) ($match->tenant_id ?: 0), (int) $participant->player_id, $discipline);
                    if (! isset($states[$key])) {
                        $states[$key] = $this->initState((int) $match->tenant_id, (int) $participant->player_id, $discipline, (int) $provider->id, $configuration);
                    }
                    return ['rating' => $states[$key]['rating'], 'reliability' => $states[$key]['reliability']];
                }, $side);
            };
            $calculated = service('ratingCalculator')->calculate([
                'side_a' => $stateData($sideA),
                'side_b' => $stateData($sideB),
                'games' => $payload['games'] ?? [],
                'winner_side' => $result->winner_side,
                'match_weight' => $this->matchWeight((int) $policy->id),
                'configuration' => $configuration,
            ]);

            foreach ($participants as $participant) {
                $stateKey = $this->stateKey((int) ($match->tenant_id ?: 0), (int) $participant->player_id, $discipline);
                $delta = (int) $participant->side === 1 ? $calculated['delta_a'] : $calculated['delta_b'];
                $opponentIds = array_values(array_map(static fn ($opponent): int => (int) $opponent->player_id, array_filter($participants, static fn ($other): bool => (int) $other->side !== (int) $participant->side)));
                $partnerIds = array_values(array_map(static fn ($partner): int => (int) $partner->player_id, array_filter($participants, static fn ($other): bool => (int) $other->side === (int) $participant->side && (int) $other->player_id !== (int) $participant->player_id)));

                foreach ($opponentIds as $opponentId) {
                    $states[$stateKey]['opponents'][(int) $opponentId] = true;
                }
                foreach ($partnerIds as $partnerId) {
                    $states[$stateKey]['partners'][(int) $partnerId] = true;
                }
                $states[$stateKey]['rating'] = round(max(2.000, min(5.999, $states[$stateKey]['rating'] + $delta)), 3);
                $states[$stateKey]['count']++;
                $states[$stateKey]['verified']++;
                $states[$stateKey]['reliability'] = service('ratingReliabilityEngine')->calculate([
                    'rated_match_count' => $states[$stateKey]['count'],
                    'verified_match_count' => $states[$stateKey]['verified'],
                    'opponent_count' => count($states[$stateKey]['opponents']),
                    'partner_count' => count($states[$stateKey]['partners']),
                    'competition_type_count' => 1,
                    'last_rated_match_at' => $match->completed_at ?: $match->created_at,
                ], $configuration, $match->completed_at ?: $match->created_at)['score'];
            }
            $processed++;
        }

        $response = [
            'success' => true,
            'migrations_ready' => $migrationGuard['ready'],
            'dry_run' => $dryRun,
            'processed_matches' => $processed,
            'skipped_matches' => $skipped,
            'profiles' => array_values($states),
            'drift' => $drift,
            'job_id' => $jobId,
        ];

        if (! $dryRun && $createJob) {
            $this->persist($provider, $states);
            $this->updateRebuildJobStatus($jobId, 'completed', [
                'dry_run' => false,
                'mode' => $dryRun ? 'dry-run' : 'apply',
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'from_match_id' => $fromMatchId ?: null,
                'to_match_id' => $toMatchId ?: null,
                'discipline' => $disciplineFilter,
                'processed_matches' => $processed,
                'skipped_matches' => $skipped,
                'drift_count' => count($drift),
                'message' => self::RUN_SUCCESS_MESSAGE,
            ]);
        } elseif ($dryRun && $jobId) {
            $this->updateRebuildJobStatus($jobId, 'completed', [
                'dry_run' => true,
                'mode' => 'dry-run',
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'from_match_id' => $fromMatchId ?: null,
                'to_match_id' => $toMatchId ?: null,
                'discipline' => $disciplineFilter,
                'processed_matches' => $processed,
                'skipped_matches' => $skipped,
                'drift_count' => count($drift),
                'message' => 'Dry-run completed.',
            ]);
        }

        return $response;
    }

    public function processQueuedJobs(array $filters = []): array
    {
        if (! $this->db->tableExists('rating_rebuild_jobs')) {
            return ['success' => false, 'message' => 'Bảng rating_rebuild_jobs chưa có trên DB.'];
        }
        $tenantId = isset($filters['tenant_id']) && (int) $filters['tenant_id'] > 0 ? (int) $filters['tenant_id'] : null;
        $limit = min(100, max(1, (int) ($filters['limit'] ?? 20)));

        $jobs = $this->db->table('rating_rebuild_jobs')
            ->whereIn('status', ['queued'])
            ->orderBy('id', 'ASC')
            ->limit($limit);

        if ($tenantId !== null) {
            $jobs->where('tenant_id', $tenantId);
        }
        $jobs = $jobs->get()->getResult();
        if (! $jobs) {
            return ['success' => true, 'processed_jobs' => 0];
        }

        $processed = 0;
        $failed = 0;
        $messages = [];
        foreach ($jobs as $job) {
            $payload = is_string($job->payload ?? null) ? (json_decode((string) $job->payload, true) ?: []) : [];
            $filtersFromPayload = [
                'tenant_id' => (int) ($job->tenant_id ?? 0),
                'player_id' => (int) ($job->player_id ?? 0) > 0 ? (int) $job->player_id : null,
                'discipline' => $payload['discipline'] ?? null,
                'provider' => $payload['provider'] ?? 'internal-v1',
                'from_match_id' => (int) ($payload['from_match_id'] ?? 0),
                'to_match_id' => (int) ($payload['to_match_id'] ?? 0),
                'from' => $payload['from'] ?? null,
                'to' => $payload['to'] ?? null,
                'queue_job_id' => (int) $job->id,
            ];
            $update = [
                'status' => 'running',
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($this->hasRebuildJobColumn('attempt_count')) {
                $update['attempt_count'] = (int) ($job->attempt_count ?? 0) + 1;
            }
            if ($this->hasRebuildJobColumn('started_at')) {
                $update['started_at'] = date('Y-m-d H:i:s');
            }
            $took = $this->db->table('rating_rebuild_jobs')
                ->where('id', (int) $job->id)
                ->where('status', 'queued')
                ->update($update);
            if (! $took) {
                continue;
            }

            $result = $this->run($filtersFromPayload, false);
            if (! empty($result['success'])) {
                $this->updateRebuildJobStatus((int) $job->id, 'completed', [
                    'processed_matches' => (int) $result['processed_matches'],
                    'skipped_matches' => $result['skipped_matches'],
                    'drift_count' => count($result['drift'] ?? []),
                    'message' => self::RUN_SUCCESS_MESSAGE,
                ]);
                $processed++;
            } else {
                $failed++;
                $this->updateRebuildJobStatus((int) $job->id, 'failed', [
                    'message' => (string) ($result['message'] ?? 'Rebuild failed.'),
                ], true);
                $messages[] = (string) ($result['message'] ?? 'Rebuild failed.');
            }
        }

        return ['success' => true, 'processed_jobs' => $processed, 'failed_jobs' => $failed, 'messages' => $messages];
    }

    private function persist(object $provider, array $states): void
    {
        if (empty($states)) {
            return;
        }
        $this->db->transStart();
        foreach ($states as $key => $state) {
            [$stateTenant, $statePlayer, $stateDiscipline] = explode(':', $key, 3);
            $disciplineRow = $this->db->table('rating_disciplines')->where('code', $stateDiscipline)->get()->getRow();
            if (! $disciplineRow) {
                continue;
            }
            $policy = $this->policy((int) $provider->id, (int) $disciplineRow->id);
            $configuration = $policy ? (json_decode((string) $policy->configuration, true) ?: []) : [];
            $band = service('skillBandResolver')->resolve($state['rating']);
            $status = $state['reliability'] >= (float) ($configuration['established_reliability'] ?? 70) ? 'established' : 'provisional';
            $existing = $this->db->table('player_rating_profiles')
                ->where('tenant_id', (int) $stateTenant)
                ->where('player_id', (int) $statePlayer)
                ->where('provider_id', $provider->id)
                ->where('discipline_id', $disciplineRow->id)
                ->get()
                ->getRow();
            $data = [
                'rating_value' => $state['rating'],
                'skill_band_id' => $band->id ?? null,
                'reliability_score' => $state['reliability'],
                'status' => $status,
                'rated_match_count' => $state['count'],
                'verified_match_count' => $state['verified'],
                'highest_rating' => $state['rating'],
                'lowest_rating' => $state['rating'],
                'calculated_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($existing) {
                $this->db->table('player_rating_profiles')->where('id', $existing->id)->update($data);
            } else {
                $this->db->table('player_rating_profiles')->insert(array_merge($data, [
                    'tenant_id' => (int) $stateTenant,
                    'player_id' => (int) $statePlayer,
                    'provider_id' => $provider->id,
                    'discipline_id' => $disciplineRow->id,
                    'created_at' => date('Y-m-d H:i:s'),
                ]));
            }
        }
        $this->db->transComplete();
    }

    private function initState(int $tenantId, int $playerId, string $discipline, int $providerId, array $configuration): array
    {
        $profile = $this->db->table('player_rating_profiles')
            ->where('tenant_id', $tenantId)
            ->where('player_id', $playerId)
            ->where('discipline_id', $this->disciplineId($providerId, $discipline))
            ->where('provider_id', $providerId)
            ->get()
            ->getRow();
        if (! $profile) {
            return [
                'rating' => (float) ($configuration['initial_rating'] ?? 3.000),
                'reliability' => 0.0,
                'count' => 0,
                'verified' => 0,
                'opponents' => [],
                'partners' => [],
                'before' => ['rating' => (float) ($configuration['initial_rating'] ?? 3.000), 'reliability' => 0.0],
            ];
        }
        return [
            'rating' => (float) ($profile->rating_value ?? ($configuration['initial_rating'] ?? 3.000)),
            'reliability' => (float) ($profile->reliability_score ?? 0),
            'count' => (int) ($profile->rated_match_count ?? 0),
            'verified' => (int) ($profile->verified_match_count ?? 0),
            'opponents' => [],
            'partners' => [],
            'before' => [
                'rating' => (float) ($profile->rating_value ?? ($configuration['initial_rating'] ?? 3.000)),
                'reliability' => (float) ($profile->reliability_score ?? 0),
            ],
        ];
    }

    private function disciplineId(int $providerId, string $discipline): int
    {
        $disciplineRow = $this->db->table('rating_disciplines')->where('code', $discipline)->get()->getRow();
        if (! $disciplineRow) {
            return 0;
        }
        $policy = $this->db->table('rating_policy_versions')
            ->where('provider_id', $providerId)
            ->where('discipline_id', (int) $disciplineRow->id)
            ->where('status', 'active')
            ->orderBy('effective_from', 'DESC')
            ->get()
            ->getRow();
        return $policy ? (int) $disciplineRow->id : (int) $disciplineRow->id;
    }

    private function createRebuildJob(int $providerId, ?int $tenantId, ?int $playerId, array $payload, string $status): ?int
    {
        if (! $this->db->tableExists('rating_rebuild_jobs')) {
            return null;
        }
        $payload['migration_version'] = $this->currentMigrationStamp();
        $insert = [
            'rating_provider_id' => $providerId,
            'tenant_id' => $tenantId,
            'player_id' => $playerId,
            'from_match_id' => ! empty($payload['from_match_id']) ? (int) $payload['from_match_id'] : null,
            'status' => $status,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($this->hasRebuildJobColumn('attempt_count')) {
            $insert['attempt_count'] = 0;
        }
        $this->db->table('rating_rebuild_jobs')->insert($insert);
        return (int) $this->db->insertID();
    }

    private function updateRebuildJobStatus(int $jobId, string $status, array $payload = [], bool $failed = false): void
    {
        if (! $this->db->tableExists('rating_rebuild_jobs')) {
            return;
        }
        $data = [
            'status' => $status,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($failed) {
            if ($this->hasRebuildJobColumn('error_message')) {
                $data['error_message'] = $payload['message'] ?? 'Rebuild failed';
            }
            if ($this->hasRebuildJobColumn('failed_at')) {
                $data['failed_at'] = date('Y-m-d H:i:s');
            }
        }
        if ($status === 'running') {
            if ($this->hasRebuildJobColumn('started_at')) {
                $data['started_at'] = date('Y-m-d H:i:s');
            }
        }
        if (in_array($status, ['completed', 'failed'], true)) {
            if ($this->hasRebuildJobColumn('completed_at')) {
                $data['completed_at'] = date('Y-m-d H:i:s');
            }
        }
        $this->db->table('rating_rebuild_jobs')->where('id', $jobId)->update($data);
    }

    private function nextAttempt(int $jobId): int
    {
        $row = $this->db->table('rating_rebuild_jobs')->where('id', $jobId)->get()->getRow();
        return (int) (($row->attempt_count ?? 0) + 1);
    }

    private function buildRebuildJobKey(int $tenantId, int $fromMatchId, ?int $playerId, string $reason, array $metadata): array
    {
        return [
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'from_match_id' => $fromMatchId,
            'player_id' => $playerId,
            'reason' => $reason,
            'discipline' => $metadata['discipline'] ?? null,
            'provider' => $metadata['provider'] ?? 'internal-v1',
        ];
    }

    private function buildRunFilters(array $filters, ?int $tenantId, ?int $playerId, int $fromMatchId, int $toMatchId, ?string $disciplineFilter, bool $dryRun): array
    {
        return [
            'mode' => $dryRun ? 'dry-run' : 'apply',
            'from' => $filters['from'] ?? null,
            'to' => $filters['to'] ?? null,
            'from_match_id' => $fromMatchId ?: null,
            'to_match_id' => $toMatchId ?: null,
            'discipline' => $disciplineFilter,
            'provider' => $filters['provider'] ?? 'internal-v1',
            'player_id' => $playerId,
            'tenant_id' => $tenantId,
        ];
    }

    private function hasRebuildJobColumn(string $column): bool
    {
        return array_key_exists($column, $this->rebuildJobColumns);
    }

    private function activeProvider(): ?object
    {
        return $this->db->table('rating_providers')->where('code', 'internal-v1')->where('status', 'active')->get()->getRow();
    }

    private function policy(int $providerId, int $disciplineId): ?object
    {
        return $this->db->table('rating_policy_versions')
            ->where('provider_id', $providerId)
            ->where('discipline_id', $disciplineId)
            ->where('status', 'active')
            ->orderBy('effective_from', 'DESC')
            ->get()
            ->getRow();
    }

    private function matchWeight(int $policyId): float
    {
        $row = $this->db->table('rating_match_type_weights')
            ->where('policy_version_id', $policyId)
            ->where('match_type', 'official')
            ->where('eligible', 1)
            ->get()
            ->getRow();
        return $row ? (float) $row->weight : 1.0;
    }

    private function stateKey(int $tenantId, int $playerId, string $discipline): string
    {
        return implode(':', [$tenantId, $playerId, $discipline]);
    }

    private function migrationGuard(): array
    {
        if (! $this->db->tableExists('migrations')) {
            return ['ready' => true, 'message' => null];
        }

        $rows = $this->db->table('migrations')->select('version')->get()->getResult();
        $versions = array_map(static fn ($row): string => (string) $row->version, $rows);
        $required = [
            '2026-08-09-330000_CreateRatingAndRankingLedgers',
            '2026-08-09-370000_CreateRatingEngineV1',
        ];
        $missing = array_values(array_filter($required, static fn ($migration) => ! in_array($migration, $versions, true)));
        if (! empty($missing)) {
            return ['ready' => false, 'message' => 'Migration rating chưa sẵn sàng: ' . implode(', ', $missing)];
        }

        return ['ready' => true, 'message' => null];
    }

    private function currentMigrationStamp(): string
    {
        if (! $this->db->tableExists('migrations')) {
            return 'legacy';
        }
        $rows = $this->db->table('migrations')->orderBy('id', 'DESC')->limit(1)->get()->getRow();
        return $rows->version ?? 'unknown';
    }
}
