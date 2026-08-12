<?php

namespace App\Services;

use App\Models\MatchParticipantModel;
use App\Models\MatchResultModel;
use App\Models\MatchResultVersionModel;
use App\Models\UnifiedMatchModel;
use Config\Database;
use RuntimeException;

class UnifiedMatchService
{
    private UnifiedMatchModel $matchModel;
    private MatchParticipantModel $participantModel;
    private MatchResultModel $resultModel;
    private MatchResultVersionModel $versionModel;

    public function __construct()
    {
        $this->matchModel = model(UnifiedMatchModel::class);
        $this->participantModel = model(MatchParticipantModel::class);
        $this->resultModel = model(MatchResultModel::class);
        $this->versionModel = model(MatchResultVersionModel::class);
    }

    public function create(array $data, ?int $tenantId, ?int $userId = null): array
    {
        $participants = array_values($data['participants'] ?? []);
        if (count($participants) < 2) {
            return ['success' => false, 'message' => 'Trận đấu cần tối thiểu 2 người chơi.'];
        }

        $playerIds = [];
        foreach ($participants as $participant) {
            $playerId = (int) ($participant['player_id'] ?? 0);
            $side = (int) ($participant['side'] ?? 0);
            if ($playerId <= 0 || ! in_array($side, [1, 2], true)) {
                return ['success' => false, 'message' => 'Participant hoặc side không hợp lệ.'];
            }
            if (in_array($playerId, $playerIds, true)) {
                return ['success' => false, 'message' => 'Một người chơi không thể xuất hiện hai lần trong trận.'];
            }
            $playerIds[] = $playerId;
        }

        $sides = array_count_values(array_map(static fn (array $item): int => (int) $item['side'], $participants));
        if (empty($sides[1]) || empty($sides[2])) {
            return ['success' => false, 'message' => 'Trận đấu phải có người chơi ở cả hai bên.'];
        }

        $db = Database::connect();
        $requestedSource = strtolower((string) ($data['source_type'] ?? 'manual'));
        $legacySources = ['tournament', 'league', 'open_play', 'club_match', 'challenge', 'friendly', 'manual'];
        $sourceId = ! empty($data['source_id']) ? (int) $data['source_id'] : null;
        $normalizedSource = in_array($requestedSource, $legacySources, true) ? $requestedSource : 'manual';
        if ($sourceId && $db->tableExists('matches')) {
            $existingMatches = $db->table('matches')
                ->where('tenant_id', $tenantId)
                ->where('source_type', $normalizedSource)
                ->where('source_id', $sourceId)
                ->orderBy('id', 'ASC')
                ->get()->getResult();
            if (count($existingMatches) > 1) {
                return ['success' => false, 'code' => 'MATCH_IDENTITY_REVIEW', 'message' => 'Source match identity đang bị trùng; cần governance review trước khi ghi thêm.'];
            }
            if (count($existingMatches) === 1) {
                return ['success' => true, 'idempotent' => true, 'match' => $this->get((int) $existingMatches[0]->id, $tenantId)];
            }
        }
        $db->transStart();
        $matchData = [
            'public_id' => $this->uuid(),
            'tenant_id' => $tenantId,
            'source_type' => $normalizedSource,
            'source_id' => $sourceId,
            'source_code' => $requestedSource,
            'discipline' => $data['discipline'] ?? 'pickleball',
            'competition_type' => $data['competition_type'] ?? null,
            'source_organization_id' => $data['source_organization_id'] ?? null,
            'venue_id' => $data['venue_id'] ?? null,
            'court_id' => $data['court_id'] ?? null,
            'status' => 'draft',
            'verification_status' => 'unverified',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'created_by' => $userId,
            'metadata' => ! empty($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null,
        ];
        foreach (array_keys($matchData) as $field) if (! $db->fieldExists($field, 'matches')) unset($matchData[$field]);
        $matchId = $this->matchModel->insert($matchData);

        foreach ($participants as $index => $participant) {
            $this->participantModel->insert([
                'match_id' => $matchId,
                'player_id' => (int) $participant['player_id'],
                'side' => (int) $participant['side'],
                'participant_role' => $participant['participant_role'] ?? 'player',
                'sort_order' => $index,
                'metadata' => ! empty($participant['metadata']) ? json_encode($participant['metadata'], JSON_UNESCAPED_UNICODE) : null,
            ]);
        }
        if ($db->tableExists('match_sides')) {
            $db->table('match_sides')->insertBatch([
                ['match_id' => $matchId, 'side_code' => 'A', 'side_order' => 1, 'result' => 'pending', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
                ['match_id' => $matchId, 'side_code' => 'B', 'side_order' => 2, 'result' => 'pending', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            ]);
        }
        $db->transComplete();

        if (! $db->transStatus() || ! $matchId) {
            return ['success' => false, 'message' => 'Không tạo được trận đấu.'];
        }

        if ($db->tableExists('data_provenance_records')) {
            $provenanceId = service('provenanceService')->record([
                'entity_type' => 'MATCH', 'entity_id' => (int) $matchId,
                'source_type' => $data['provenance_source_type'] ?? strtoupper((string) ($data['source_type'] ?? 'MANUAL')),
                'source_id' => $data['source_id'] ?? null,
                'source_organization_id' => $data['source_organization_id'] ?? $tenantId,
                'created_by' => $userId,
                'verification_level' => 'UNVERIFIED',
                'metadata' => $data['provenance_metadata'] ?? null,
            ]);
            if ($provenanceId) $this->matchModel->update($matchId, ['provenance_id' => $provenanceId]);
        }

        return ['success' => true, 'match' => $this->get((int) $matchId, $tenantId)];
    }

    public function submitResult(int $matchId, array $data, ?int $tenantId, ?int $userId = null): array
    {
        $match = $this->findForTenant($matchId, $tenantId);
        if (! $match) return ['success' => false, 'message' => 'Không tìm thấy trận đấu.'];
        if (in_array($match->status, ['official', 'cancelled'], true)) {
            return ['success' => false, 'message' => 'Trận đã chốt hoặc đã hủy, hãy tạo phiên bản điều chỉnh.'];
        }

        $games = array_values($data['games'] ?? []);
        $winnerSide = isset($data['winner_side']) ? (int) $data['winner_side'] : null;
        if ($winnerSide !== null && ! in_array($winnerSide, [1, 2], true)) {
            return ['success' => false, 'message' => 'winner_side phải là 1 hoặc 2.'];
        }

        $result = $this->resultModel->where('match_id', $matchId)->first();
        $versionNo = $result ? ((int) $result->version_no + 1) : 1;
        $payload = [
            'games' => $games,
            'winner_side' => $winnerSide,
            'notes' => $data['notes'] ?? null,
            'participants' => $data['participants'] ?? [],
        ];

        $db = Database::connect();
        $db->transStart();
        $versionId = $this->versionModel->insert([
            'match_id' => $matchId,
            'version_no' => $versionNo,
            'status' => 'submitted',
            'result_type' => $data['result_type'] ?? 'normal',
            'winner_side' => $winnerSide,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'submitted_by' => $userId,
            'change_reason' => $data['change_reason'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($db->tableExists('match_games')) {
            foreach ($games as $index => $game) {
                $gameNo = (int) ($game['game_no'] ?? $game['game_number'] ?? ($index + 1));
                $gameData = ['match_id' => $matchId, 'game_no' => $gameNo, 'side_a_score' => (int) ($game['side_a_score'] ?? $game['a'] ?? 0), 'side_b_score' => (int) ($game['side_b_score'] ?? $game['b'] ?? 0), 'raw_score' => $game['raw_score'] ?? null, 'updated_at' => date('Y-m-d H:i:s')];
                $existingGame = $db->table('match_games')->where('match_id', $matchId)->where('game_no', $gameNo)->get()->getRow();
                if ($existingGame) $db->table('match_games')->where('id', $existingGame->id)->update($gameData); else { $gameData['created_at'] = date('Y-m-d H:i:s'); $db->table('match_games')->insert($gameData); }
            }
        }

        if ($result) {
            $this->resultModel->update($result->id, [
                'current_version_id' => $versionId,
                'version_no' => $versionNo,
                'status' => 'submitted',
                'result_type' => $data['result_type'] ?? 'normal',
                'winner_side' => $winnerSide,
            ]);
        } else {
            $this->resultModel->insert([
                'match_id' => $matchId,
                'current_version_id' => $versionId,
                'version_no' => $versionNo,
                'status' => 'submitted',
                'result_type' => $data['result_type'] ?? 'normal',
                'winner_side' => $winnerSide,
            ]);
        }
        $this->matchModel->update($matchId, [
            'status' => 'submitted',
            'result_type' => $data['result_type'] ?? 'normal',
            'verification_status' => 'pending',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        if (! $db->transStatus() || ! $versionId) {
            return ['success' => false, 'message' => 'Không lưu được kết quả.'];
        }
        return ['success' => true, 'match' => $this->get($matchId, $tenantId)];
    }

    public function confirmResult(int $matchId, ?int $tenantId, ?int $userId = null): array
    {
        return $this->changeResultStatus($matchId, $tenantId, 'confirmed', $userId);
    }

    public function publishOfficial(int $matchId, ?int $tenantId, ?int $userId = null, ?int $authorityId = null, ?int $rulesetVersionId = null): array
    {
        return $this->changeResultStatus($matchId, $tenantId, 'official', $userId, $authorityId, $rulesetVersionId);
    }

    public function get(int $matchId, ?int $tenantId = null): ?array
    {
        $match = $this->findForTenant($matchId, $tenantId);
        if (! $match) return null;
        $result = $this->resultModel->where('match_id', $matchId)->first();
        $participants = $this->participantModel->where('match_id', $matchId)->orderBy('side')->orderBy('sort_order')->findAll();
        $version = $result && $result->current_version_id ? $this->versionModel->find($result->current_version_id) : null;
        if ($version && is_string($version->payload)) $version->payload = json_decode($version->payload, true);
        return ['match' => $match, 'participants' => $participants, 'result' => $result, 'current_version' => $version];
    }

    private function changeResultStatus(int $matchId, ?int $tenantId, string $status, ?int $userId, ?int $authorityId = null, ?int $rulesetVersionId = null): array
    {
        $match = $this->findForTenant($matchId, $tenantId);
        $result = $this->resultModel->where('match_id', $matchId)->first();
        if (! $match || ! $result) return ['success' => false, 'message' => 'Chưa có kết quả để xác nhận.'];
        if ($status === 'confirmed' && ! in_array($result->status, ['submitted', 'disputed'], true)) {
            return ['success' => false, 'message' => 'Kết quả không ở trạng thái chờ xác nhận.'];
        }
        if ($status === 'official' && $result->status !== 'confirmed') {
            return ['success' => false, 'message' => 'Chỉ kết quả đã xác nhận mới được công bố chính thức.'];
        }

        $version = $result->current_version_id ? $this->versionModel->find($result->current_version_id) : null;
        if (! $version) return ['success' => false, 'message' => 'Không tìm thấy phiên bản kết quả hiện tại.'];
        $db = Database::connect();
        $db->transStart();
        $versionData = ['status' => $status];
        if ($rulesetVersionId === null && ! empty($match->ruleset_version_id)) $rulesetVersionId = (int) $match->ruleset_version_id;
        if ($status === 'confirmed') $versionData['confirmed_by'] = $userId;
        if ($authorityId !== null) $versionData['authority_id'] = $authorityId;
        if ($rulesetVersionId !== null) $versionData['ruleset_version_id'] = $rulesetVersionId;
        if ($status === 'official') {
            $versionData['verified_by'] = $userId;
            $versionData['verified_at'] = date('Y-m-d H:i:s');
            $versionData['source'] = 'GOVERNANCE_CERTIFIED';
        }
        foreach (array_keys($versionData) as $field) if (! $this->hasColumn($db, 'match_result_versions', $field)) unset($versionData[$field]);
        $this->versionModel->update($version->id, $versionData);
        $resultData = ['status' => $status];
        if ($status === 'official') $resultData['published_at'] = date('Y-m-d H:i:s');
        $this->resultModel->update($result->id, $resultData);
        $matchStatusData = [
            'status' => $status,
            'verification_status' => $status === 'official' ? 'official' : 'verified',
            'verified_by' => $userId,
        ];
        foreach (array_keys($matchStatusData) as $field) if (! $this->hasColumn($db, 'matches', $field)) unset($matchStatusData[$field]);
        $this->matchModel->update($matchId, $matchStatusData);
        $db->transComplete();
        if (! $db->transStatus()) return ['success' => false, 'message' => 'Không cập nhật được trạng thái kết quả.'];

        if ($status === 'official' && $db->tableExists('data_provenance_records')) {
            $provenanceId = service('provenanceService')->record(['entity_type' => 'MATCH_RESULT_VERSION', 'entity_id' => (int) $version->id, 'source_type' => 'OFFICIAL_REFEREE', 'source_id' => (string) $matchId, 'source_organization_id' => $match->tenant_id ?: $tenantId, 'created_by' => $userId, 'verified_by' => $userId, 'verification_level' => 'OFFICIAL', 'policy_version_id' => $rulesetVersionId, 'metadata' => ['authority_id' => $authorityId], 'verified_at' => date('Y-m-d H:i:s')]);
            if ($provenanceId) $this->versionModel->update($version->id, ['provenance_id' => $provenanceId]);
        }

        $network = null;
        if ($status === 'official') {
            try {
                $canonical = service('ratingEngine')->processOfficialMatch($matchId, $tenantId);
                if (! empty($canonical['success']) && empty($canonical['skipped'])) {
                    $ranking = service('rankingNetworkService')->applyOfficialMatch($matchId, $tenantId);
                    // Keep the stable top-level success flag for existing API
                    // consumers while exposing the canonical engine result.
                    $network = ['success' => true, 'rating_engine_v1' => $canonical, 'ranking' => $ranking];
                } elseif (! empty($canonical['skipped'])) {
                    // Keep old installations operational until the V1 migration is applied.
                    $legacy = service('ratingNetworkService')->applyOfficialMatch($matchId, $tenantId);
                    $network = ['success' => ! empty($legacy['success']), 'rating_engine_v1' => $canonical, 'legacy' => $legacy];
                } else {
                    // A V1 business rejection must never be silently converted into a
                    // legacy rating write. Keep the official result auditable and queue
                    // the derived rating for review/rebuild.
                    $network = ['success' => false, 'rating_engine_v1' => $canonical, 'message' => 'Rating V1 từ chối kết quả; cần review/rebuild.'];
                }
            } catch (\Throwable $e) {
                // Official result remains auditable even if a derived ledger needs retry/rebuild.
                log_message('error', 'official_match_network_sync_failed match_id={match_id} error={error}', [
                    'match_id' => $matchId,
                    'error' => $e->getMessage(),
                ]);
                $network = ['success' => false, 'message' => 'Rating/ranking đang chờ đồng bộ lại.'];
            }
        }
        return ['success' => true, 'match' => $this->get($matchId, $tenantId), 'network' => $network];
    }

    private function findForTenant(int $matchId, ?int $tenantId): ?object
    {
        $builder = $this->matchModel->where('id', $matchId);
        // Operational match reads are tenant-scoped. Platform-global matches
        // must be addressed without a tenant context by an explicit platform
        // service, never by silently widening this query.
        if ($tenantId !== null && $tenantId > 0) $builder->where('tenant_id', $tenantId);
        return $builder->first();
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    private function hasColumn($db, string $table, string $column): bool
    {
        $row = $db->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?', [$column])->getRow();
        return $row !== null;
    }
}
