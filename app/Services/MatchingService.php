<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\MatchRequestModel;
use App\Models\MembershipModel;
use App\Models\PlayerModel;
use App\Models\SocialMatchModel;
use App\Models\SocialMatchPlayerModel;

class MatchingService
{
    protected MatchRequestModel $matchRequestModel;
    protected SocialMatchModel $socialMatchModel;
    protected SocialMatchPlayerModel $socialMatchPlayerModel;
    protected PlayerModel $playerModel;
    protected BookingModel $bookingModel;
    protected BranchModel $branchModel;

    public function __construct()
    {
        $this->matchRequestModel = model(MatchRequestModel::class);
        $this->socialMatchModel = model(SocialMatchModel::class);
        $this->socialMatchPlayerModel = model(SocialMatchPlayerModel::class);
        $this->playerModel = model(PlayerModel::class);
        $this->bookingModel = model(BookingModel::class);
        $this->branchModel = model(BranchModel::class);
    }

    public function createMatchRequest(array $data): array
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $validationError = $this->validateRequestData($data);
        if ($validationError !== true) {
            return ['success' => false, 'message' => $validationError];
        }
        $branch = $tenantId > 0
            ? $this->branchModel->findForTenant((int) ($data['branch_id'] ?? 0), $tenantId)
            : null;
        $player = $tenantId > 0
            ? $this->playerModel->findForTenant((int) ($data['player_id'] ?? 0), $tenantId)
            : null;
        if (!$branch || !$player) {
            return ['success' => false, 'message' => 'Tenant, chi nhánh hoặc người chơi không hợp lệ.'];
        }

        if ($this->hasPlayerConflict(
            (int) $data['player_id'],
            $data['preferred_date'],
            $data['preferred_start_time'],
            $data['preferred_end_time'],
            $tenantId
        )) {
            return ['success' => false, 'message' => 'Bạn đã có booking hoặc trận trong khung giờ này.'];
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $id = $this->matchRequestModel->insert([
            'tenant_id' => $tenantId, 'player_id' => (int) $data['player_id'], 'branch_id' => (int) $data['branch_id'],
            'preferred_date' => $data['preferred_date'], 'preferred_start_time' => $data['preferred_start_time'], 'preferred_end_time' => $data['preferred_end_time'],
            'level_from' => (int) ($data['level_from'] ?? 0), 'level_to' => (int) ($data['level_to'] ?? 9999),
            'match_type' => $data['match_type'] ?? 'double', 'need_players' => (int) ($data['need_players'] ?? 1), 'status' => 'open',
        ]);
        $db->transComplete();
        if (!$id || !$db->transStatus()) {
            return ['success' => false, 'message' => implode(' ', $this->matchRequestModel->errors()) ?: 'Không thể tạo kèo.'];
        }
        $this->audit('created', (int) $id, $tenantId, ['player_id' => (int) $data['player_id']]);

        return [
            'success' => (bool) $id,
            'message' => $id ? 'Đã tạo kèo mở.' : (implode(' ', $this->matchRequestModel->errors()) ?: 'Không thể tạo kèo.'),
            'match_request' => $this->matchRequestModel->findForTenant((int) $id, $tenantId),
        ];
    }

    public function findCompatiblePlayers(int $matchRequestId, int $limit = 12, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?: (int) (current_tenant_id() ?? 0);
        $request = $tenantId ? $this->matchRequestModel->findForTenant($matchRequestId, $tenantId) : null;
        if (! $request || $request->status !== 'open') {
            return [];
        }

        $owner = $this->playerModel->findForTenant((int) $request->player_id, (int) $request->tenant_id);
        $players = $this->playerModel
            ->where('tenant_id', $request->tenant_id)
            ->where('status', 'active')
            ->where('id !=', $request->player_id)
            ->where('deleted_at', null)
            ->findAll();

        $compatible = [];
        foreach ($players as $player) {
            $rating = (int) ($player->rating_score ?? 0);
            if ($rating < (int) $request->level_from || $rating > (int) $request->level_to) {
                continue;
            }

            if ($this->hasPlayerConflict((int) $player->id, $request->preferred_date, $request->preferred_start_time, $request->preferred_end_time, (int) $request->tenant_id)) {
                continue;
            }

            $score = 0;
            $target = (((int) $request->level_from + (int) $request->level_to) / 2);
            $score += max(0, 1000 - abs($rating - $target));

            if ((int) ($player->home_branch_id ?? 0) === (int) $request->branch_id) {
                $score += 300;
            }

            if ($owner && $owner->region && $owner->region === ($player->region ?? null)) {
                $score += 150;
            }

            if ($this->isActiveMember((int) $request->tenant_id, (int) $player->id)) {
                $score += 250;
                $player->is_member = true;
            } else {
                $player->is_member = false;
            }

            $player->match_score = $score;
            $compatible[] = $player;
        }

        usort($compatible, fn ($a, $b) => $b->match_score <=> $a->match_score);

        return array_slice($compatible, 0, $limit);
    }

    public function autoMatch(int $matchRequestId, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?: (int) (current_tenant_id() ?? 0);
        $request = $tenantId ? $this->matchRequestModel->findForTenant($matchRequestId, $tenantId) : null;
        if (! $request || $request->status !== 'open') {
            return ['success' => false, 'message' => 'Kèo không còn mở.'];
        }

        $players = $this->findCompatiblePlayers($matchRequestId, (int) $request->need_players, $tenantId);
        if (count($players) < (int) $request->need_players) {
            return ['success' => false, 'message' => 'Chưa đủ người phù hợp.', 'players' => $players];
        }

        return $this->confirmSocialMatch($matchRequestId, array_map(fn ($p) => (int) $p->id, $players), $tenantId);
    }

    public function cancelMatchRequest(int $matchRequestId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $request = $this->matchRequestModel->findForUpdate($matchRequestId, $tenantId);
        if (!$request) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Không tìm thấy kèo.'];
        }
        if ($request->status !== 'open') {
            $db->transComplete();
            return ['success' => true, 'duplicate' => true, 'message' => 'Kèo đã được xử lý.'];
        }
        $ok = $this->matchRequestModel->update($matchRequestId, ['status' => 'cancelled']);
        $db->transComplete();
        if (!$ok || !$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể hủy kèo.'];
        }
        $this->audit('cancelled', $matchRequestId, $tenantId, ['user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã cancel kèo.'];
    }

    public function confirmSocialMatch(int $matchRequestId, array $playerIds, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?: (int) (current_tenant_id() ?? 0);
        $request = $tenantId ? $this->matchRequestModel->findForTenant($matchRequestId, $tenantId) : null;
        if (! $request || $request->status !== 'open') {
            return ['success' => false, 'message' => 'Kèo không còn mở.'];
        }

        $playerIds = array_values(array_unique(array_map('intval', $playerIds)));
        if (count($playerIds) < (int) $request->need_players) {
            return ['success' => false, 'message' => 'Chưa đủ người để xác nhận trận.'];
        }

        $allPlayers = array_values(array_unique(array_merge([(int) $request->player_id], $playerIds)));
        foreach ($allPlayers as $playerId) {
            if (!$this->playerModel->findForTenant($playerId, (int) $request->tenant_id)
                || $this->hasPlayerConflict($playerId, $request->preferred_date, $request->preferred_start_time, $request->preferred_end_time, (int) $request->tenant_id)) {
                return ['success' => false, 'message' => 'Có người chơi bị trùng lịch.'];
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $lockedRequest = $this->matchRequestModel->findForUpdate($matchRequestId, (int) $request->tenant_id);
        if (!$lockedRequest || $lockedRequest->status !== 'open') {
            $db->transRollback();
            return ['success' => false, 'message' => 'Kèo đã được xử lý bởi người khác.'];
        }
        $request = $lockedRequest;

        sort($allPlayers, SORT_NUMERIC);
        $placeholders = implode(',', array_fill(0, count($allPlayers), '?'));
        $lockParams = array_merge($allPlayers, [(int) $request->tenant_id]);
        $lockedPlayers = $db->query("SELECT id, rating_score FROM players WHERE id IN ({$placeholders}) AND tenant_id = ? AND deleted_at IS NULL FOR UPDATE", $lockParams)->getResult();
        if (count($lockedPlayers) !== count($allPlayers)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Có người chơi không thuộc tenant.'];
        }
        foreach ($lockedPlayers as $lockedPlayer) {
            if ((int) $lockedPlayer->rating_score < (int) $request->level_from || (int) $lockedPlayer->rating_score > (int) $request->level_to || $this->hasPlayerConflict((int) $lockedPlayer->id, $request->preferred_date, $request->preferred_start_time, $request->preferred_end_time, (int) $request->tenant_id)) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Có người chơi không còn phù hợp hoặc bị trùng lịch.'];
            }
        }

        $socialMatchId = $this->socialMatchModel->insert([
            'tenant_id' => $request->tenant_id,
            'branch_id' => $request->branch_id,
            'match_request_id' => $request->id,
            'match_date' => $request->preferred_date,
            'start_time' => $request->preferred_start_time,
            'end_time' => $request->preferred_end_time,
            'status' => 'confirmed',
        ]);

        if (!$socialMatchId) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Không thể tạo trận.'];
        }

        foreach ($allPlayers as $index => $playerId) {
            if (!$this->socialMatchPlayerModel->insert([
                'tenant_id' => $request->tenant_id,
                'social_match_id' => $socialMatchId,
                'player_id' => $playerId,
                'team_side' => $index % 2 === 0 ? 'A' : 'B',
                'status' => 'confirmed',
            ])) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Không thể thêm người chơi vào trận.'];
            }
        }

        $this->matchRequestModel->update($request->id, ['status' => 'matched']);
        $db->transComplete();

        $success = $db->transStatus();
        if ($success) {
            $this->audit('confirmed', (int) $socialMatchId, (int) $request->tenant_id, ['request_id' => (int) $request->id, 'players' => $allPlayers]);
        }
        return [
            'success' => $success,
            'message' => $success ? 'Đã xác nhận trận.' : 'Không thể xác nhận trận.',
            'social_match' => $socialMatchId ? $this->socialMatchModel->find($socialMatchId) : null,
        ];
    }

    public function convertToBooking(int $socialMatchId, ?int $createdBy = null, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?: (int) (current_tenant_id() ?? 0);
        $match = $tenantId ? $this->socialMatchModel->findForTenant($socialMatchId, $tenantId) : null;
        if (! $match) {
            return ['success' => false, 'message' => 'Không tìm thấy trận.'];
        }

        if ($match->booking_id) {
            return ['success' => true, 'message' => 'Trận đã có booking.', 'booking' => $this->bookingModel->findForTenant((int) $match->booking_id, (int) $match->tenant_id)];
        }

        $court = $this->findAvailableCourt((int) $match->branch_id, $match->match_date, $match->start_time, $match->end_time, (int) $match->tenant_id);
        if (! $court) {
            return ['success' => false, 'message' => 'Không tìm thấy sân trống để chuyển thành booking.'];
        }

        $players = $this->socialMatchPlayerModel->getByMatch($socialMatchId, (int) $match->tenant_id);
        $host = $players[0] ?? null;
        $hostPlayer = $host ? $this->playerModel->findForTenant((int) $host->player_id, (int) $match->tenant_id) : null;

        $bookingService = new BookingService();
        $result = $bookingService->createBooking([
            'tenant_id' => $match->tenant_id,
            'branch_id' => $match->branch_id,
            'player_id' => $host->player_id ?? null,
            'customer_name' => $hostPlayer->full_name ?? 'Social Match',
            'customer_phone' => $hostPlayer->phone ?? '0000000000',
            'customer_email' => $hostPlayer->email ?? null,
            'booking_date' => $match->match_date,
            'start_time' => $match->start_time,
            'end_time' => $match->end_time,
            'source' => 'admin',
            'note' => 'Tạo từ kèo Pickleball online #' . $socialMatchId,
            'status' => 'reserved',
            'created_by' => $createdBy,
            'items' => [[
                'court_id' => $court->id,
                'start_time' => $match->start_time,
                'end_time' => $match->end_time,
                'price' => 0,
            ]],
        ]);

        if ($result['success']) {
            $this->socialMatchModel->update($socialMatchId, [
                'booking_id' => $result['booking']->id,
                'status' => 'booked',
            ]);
        }

        return $result;
    }

    public function hasPlayerConflict(int $playerId, string $date, string $startTime, string $endTime, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?: (int) (current_tenant_id() ?? 0);
        $bookingConflict = $this->bookingModel
            ->where('player_id', $playerId)
            ->where('tenant_id', $tenantId)
            ->where('booking_date', $date)
            ->whereNotIn('status', ['cancelled', 'refunded', 'expired'])
            ->where('deleted_at', null)
            ->groupStart()
                ->where('start_time <', $endTime)
                ->where('end_time >', $startTime)
            ->groupEnd()
            ->countAllResults() > 0;

        if ($bookingConflict) {
            return true;
        }

        return $this->socialMatchPlayerModel
            ->join('social_matches', 'social_matches.id = social_match_players.social_match_id')
            ->where('social_match_players.player_id', $playerId)
            ->where('social_matches.tenant_id', $tenantId)
            ->whereIn('social_match_players.status', ['confirmed', 'invited'])
            ->where('social_matches.match_date', $date)
            ->whereNotIn('social_matches.status', ['cancelled', 'completed'])
            ->where('social_matches.deleted_at', null)
            ->groupStart()
                ->where('social_matches.start_time <', $endTime)
                ->where('social_matches.end_time >', $startTime)
            ->groupEnd()
            ->countAllResults() > 0;
    }

    protected function isActiveMember(int $tenantId, int $playerId): bool
    {
        return model(MembershipModel::class)
            ->where('tenant_id', $tenantId)
            ->where('player_id', $playerId)
            ->where('status', 'active')
            ->where('start_date <=', date('Y-m-d'))
            ->where('end_date >=', date('Y-m-d'))
            ->where('deleted_at', null)
            ->countAllResults() > 0;
    }

    protected function findAvailableCourt(int $branchId, string $date, string $startTime, string $endTime, int $tenantId)
    {
        $courts = model(CourtModel::class)->where('branch_id', $branchId)
            ->where('tenant_id', $tenantId)->where('status', 'available')
            ->where('deleted_at', null)->findAll();
        foreach ($courts as $court) {
            if ($this->bookingModel->isCourtAvailable((int) $court->id, $date, $startTime, $endTime)) {
                return $court;
            }
        }

        return null;
    }

    protected function validateRequestData(array $data): true|string
    {
        if ((int) ($data['tenant_id'] ?? 0) < 1 || (int) ($data['player_id'] ?? 0) < 1 || (int) ($data['branch_id'] ?? 0) < 1) {
            return 'Tenant, chi nhánh hoặc người chơi không hợp lệ.';
        }
        $date = (string) ($data['preferred_date'] ?? '');
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < date('Y-m-d')) {
            return 'Ngày chơi phải hợp lệ và không ở quá khứ.';
        }
        if (!self::isValidTimeRange((string) ($data['preferred_start_time'] ?? ''), (string) ($data['preferred_end_time'] ?? ''))) {
            return 'Khung giờ không hợp lệ.';
        }
        $from = (int) ($data['level_from'] ?? 0);
        $to = (int) ($data['level_to'] ?? 9999);
        if ($from < 0 || $to < $from || $to > 9999) {
            return 'Khoảng rating không hợp lệ.';
        }
        $need = (int) ($data['need_players'] ?? 1);
        if ($need < 1 || $need > 3 || !in_array($data['match_type'] ?? 'double', ['single', 'double', 'mixed'], true)) {
            return 'Thông tin loại trận không hợp lệ.';
        }
        return true;
    }

    public static function isValidTimeRange(string $start, string $end): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $start) === 1 && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $end) === 1 && $start < $end;
    }

    protected function audit(string $action, int $id, int $tenantId, array $data = []): void
    {
        if (function_exists('log_audit')) {
            log_audit(['action' => 'matching_' . $action, 'entity_type' => 'social_match', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => $data]);
        }
    }
}
