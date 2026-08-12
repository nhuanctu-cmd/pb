<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PlayerCompetitiveProfileModel;
use App\Models\PlayerModel;
use App\Services\PlayerService;

/** Protected rating/skill API plus a deliberately small public rating lookup. */
class RatingApi extends BaseController
{
    private PlayerService $playerService;

    public function __construct()
    {
        $this->playerService = new PlayerService();
    }

    public function profile(int $playerId)
    {
        $tenantId = $this->tenantId();
        if ($tenantId === null) return $this->error('Tenant không hợp lệ.');
        $player = model(PlayerModel::class)->where('id', $playerId)->where('tenant_id', $tenantId)->first();
        if (! $player) return $this->notFound('Player không tồn tại trong tenant hiện tại.');
        $discipline = $this->discipline($this->request->getGet('discipline'));
        return $this->success(['player_id' => $playerId, 'discipline' => $discipline, 'rating' => service('ratingEngine')->getPublicRating($tenantId, $playerId, $discipline)]);
    }

    public function history(int $playerId, string $discipline)
    {
        $tenantId = $this->tenantId();
        $discipline = $this->discipline($discipline);
        if ($tenantId === null) return $this->error('Tenant không hợp lệ.');
        $player = model(PlayerModel::class)->where('id', $playerId)->where('tenant_id', $tenantId)->first();
        if (! $player) return $this->notFound('Player không tồn tại trong tenant hiện tại.');
        $rows = service('ratingEngine')->history($tenantId, $playerId, $discipline, (int) ($this->request->getGet('limit') ?: 50));
        $items = array_map(static function ($row): array {
            $metadata = is_string($row->metadata ?? null) ? (json_decode($row->metadata, true) ?: []) : (array) ($row->metadata ?? []);
            return [
                'id' => (int) $row->id,
                'match_id' => $row->match_id ? (int) $row->match_id : null,
                'transaction_type' => (string) $row->transaction_type,
                'before_rating' => $row->before_rating !== null ? (float) $row->before_rating : null,
                'after_rating' => $row->after_rating !== null ? (float) $row->after_rating : null,
                'delta' => (float) $row->rating_delta,
                'expected_performance' => $row->expected_performance !== null ? (float) $row->expected_performance : null,
                'actual_performance' => $row->actual_performance !== null ? (float) $row->actual_performance : null,
                'reliability_after' => (float) $row->reliability_after,
                'reason' => $row->reason,
                'status' => $row->status ?? 'applied',
                'processed_at' => $row->processed_at,
                'match' => ['games_count' => $metadata['games_count'] ?? null, 'score_margin' => $metadata['score_margin'] ?? null],
            ];
        }, $rows);
        return $this->success(['player_id' => $playerId, 'discipline' => $discipline, 'items' => $items]);
    }

    public function assessment()
    {
        [$player, $tenantId] = $this->currentPlayer();
        if (! $player || $tenantId === null) return $this->error('Không xác định được player hiện tại.');
        $input = $this->payload();
        $result = service('skillAssessmentService')->assess($tenantId, (int) $player->id, $this->discipline($input['discipline'] ?? null), (array) ($input['answers'] ?? []), (array) ($input['weights'] ?? []));
        return ! empty($result['success']) ? $this->success($result) : $this->error($result['message'] ?? 'Không thể lưu assessment.');
    }

    public function claim()
    {
        [$player, $tenantId] = $this->currentPlayer();
        if (! $player || $tenantId === null) return $this->error('Không xác định được player hiện tại.');
        $input = $this->payload();
        $result = service('playerSkillClaimService')->submit($tenantId, (int) $player->id, $this->discipline($input['discipline'] ?? null), $input, (int) ($this->request->api_user_id ?? 0) ?: null);
        return ! empty($result['success']) ? $this->created($result) : $this->error($result['message'] ?? 'Không thể gửi skill claim.');
    }

    public function eligibility()
    {
        $tenantId = $this->tenantId();
        $input = $this->payload();
        $playerIds = array_values(array_filter(array_map('intval', (array) ($input['player_ids'] ?? []))));
        if ($tenantId === null || ! $playerIds) return $this->error('Cần tenant và player_ids.');
        return $this->success(service('tournamentEligibilityService')->evaluate($tenantId, $playerIds, $this->discipline($input['discipline'] ?? null), (array) ($input['rules'] ?? [])));
    }

    public function importUpload()
    {
        $input = $this->payload(); $tenantId = $this->tenantId();
        if ($tenantId === null) return $this->error('Tenant không hợp lệ.');
        $result = service('ratingImportService')->upload($tenantId, (string) ($input['source_type'] ?? ''), (array) ($input['rows'] ?? []), (int) ($this->request->api_user_id ?? 0) ?: null, $input['source_name'] ?? null);
        return ! empty($result['success']) ? $this->created($result) : $this->error($result['message'] ?? 'Không thể tạo import job.');
    }

    public function importStep(int $jobId, string $step)
    {
        $tenantId = $this->tenantId(); if ($tenantId === null) return $this->error('Tenant không hợp lệ.');
        $service = service('ratingImportService');
        $result = match ($step) {
            'preview' => $service->preview($tenantId, $jobId),
            'matching' => $service->matchIdentities($tenantId, $jobId),
            'validate' => $service->validate($tenantId, $jobId),
            'verify' => $service->verifySource($tenantId, $jobId, ! empty(($this->payload()['verified'] ?? false))),
            'import' => $service->importClaims($tenantId, $jobId),
            default => ['success' => false, 'message' => 'Bước import không hợp lệ.'],
        };
        return ! empty($result['success']) ? $this->success($result) : $this->error($result['message'] ?? 'Import step failed.');
    }

    public function publicRatings(string $nationalId)
    {
        $passport = model(PlayerCompetitiveProfileModel::class)->findByNationalId($nationalId);
        if (! $passport) return $this->notFound('Không tìm thấy vận động viên.');
        $player = model(PlayerModel::class)->find((int) $passport->player_id);
        if (! $player) return $this->notFound('Không tìm thấy hồ sơ vận động viên.');
        $discipline = $this->discipline($this->request->getGet('discipline'));
        return $this->success(['national_player_id' => $passport->national_player_id, 'display_name' => $passport->display_name, 'slug' => $passport->slug, 'discipline' => $discipline, 'rating' => service('ratingEngine')->getPublicRating((int) $player->tenant_id, (int) $player->id, $discipline)]);
    }

    private function currentPlayer(): array
    {
        $userId = (int) ($this->request->api_user_id ?? 0);
        $tenantId = $this->tenantId();
        return [$userId > 0 && $tenantId !== null ? $this->playerService->getPlayerByUser($userId, $tenantId) : null, $tenantId];
    }

    private function tenantId(): ?int
    {
        $id = (int) ($this->request->api_tenant_id ?? current_tenant_id());
        return $id > 0 ? $id : null;
    }

    private function discipline(?string $discipline): string
    {
        $discipline = strtolower(trim((string) ($discipline ?: 'singles')));
        if ($discipline === 'mixed') $discipline = 'mixed_doubles';
        return in_array($discipline, ['singles', 'doubles', 'mixed_doubles'], true) ? $discipline : 'singles';
    }

    private function payload(): array { return $this->request->getJSON(true) ?: $this->request->getPost(); }
    private function success(array $data) { return service('apiResponseService')->success($data); }
    private function created(array $data) { return service('apiResponseService')->created($data); }
    private function error(string $message) { return service('apiResponseService')->error($message); }
    private function notFound(string $message) { return service('apiResponseService')->notFound($message); }
}
