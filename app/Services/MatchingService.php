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

    public function __construct()
    {
        $this->matchRequestModel = model(MatchRequestModel::class);
        $this->socialMatchModel = model(SocialMatchModel::class);
        $this->socialMatchPlayerModel = model(SocialMatchPlayerModel::class);
        $this->playerModel = model(PlayerModel::class);
        $this->bookingModel = model(BookingModel::class);
    }

    public function createMatchRequest(array $data): array
    {
        if ($this->hasPlayerConflict(
            (int) $data['player_id'],
            $data['preferred_date'],
            $data['preferred_start_time'],
            $data['preferred_end_time']
        )) {
            return ['success' => false, 'message' => 'Bạn đã có booking hoặc trận trong khung giờ này.'];
        }

        $id = $this->matchRequestModel->insert([
            'tenant_id' => $data['tenant_id'],
            'player_id' => $data['player_id'],
            'branch_id' => $data['branch_id'],
            'preferred_date' => $data['preferred_date'],
            'preferred_start_time' => $data['preferred_start_time'],
            'preferred_end_time' => $data['preferred_end_time'],
            'level_from' => $data['level_from'] ?? 0,
            'level_to' => $data['level_to'] ?? 9999,
            'match_type' => $data['match_type'] ?? 'double',
            'need_players' => $data['need_players'] ?? 1,
            'status' => 'open',
        ]);

        return [
            'success' => (bool) $id,
            'message' => $id ? 'Đã tạo kèo mở.' : (implode(' ', $this->matchRequestModel->errors()) ?: 'Không thể tạo kèo.'),
            'match_request' => $id ? $this->matchRequestModel->find($id) : null,
        ];
    }

    public function findCompatiblePlayers(int $matchRequestId, int $limit = 12): array
    {
        $request = $this->matchRequestModel->find($matchRequestId);
        if (! $request || $request->status !== 'open') {
            return [];
        }

        $owner = $this->playerModel->find($request->player_id);
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

            if ($this->hasPlayerConflict((int) $player->id, $request->preferred_date, $request->preferred_start_time, $request->preferred_end_time)) {
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

    public function autoMatch(int $matchRequestId): array
    {
        $request = $this->matchRequestModel->find($matchRequestId);
        if (! $request || $request->status !== 'open') {
            return ['success' => false, 'message' => 'Kèo không còn mở.'];
        }

        $players = $this->findCompatiblePlayers($matchRequestId, (int) $request->need_players);
        if (count($players) < (int) $request->need_players) {
            return ['success' => false, 'message' => 'Chưa đủ người phù hợp.', 'players' => $players];
        }

        return $this->confirmSocialMatch($matchRequestId, array_map(fn ($p) => (int) $p->id, $players));
    }

    public function confirmSocialMatch(int $matchRequestId, array $playerIds): array
    {
        $request = $this->matchRequestModel->find($matchRequestId);
        if (! $request || $request->status !== 'open') {
            return ['success' => false, 'message' => 'Kèo không còn mở.'];
        }

        $playerIds = array_values(array_unique(array_map('intval', $playerIds)));
        if (count($playerIds) < (int) $request->need_players) {
            return ['success' => false, 'message' => 'Chưa đủ người để xác nhận trận.'];
        }

        $allPlayers = array_values(array_unique(array_merge([(int) $request->player_id], $playerIds)));
        foreach ($allPlayers as $playerId) {
            if ($this->hasPlayerConflict($playerId, $request->preferred_date, $request->preferred_start_time, $request->preferred_end_time)) {
                return ['success' => false, 'message' => 'Có người chơi bị trùng lịch.'];
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $socialMatchId = $this->socialMatchModel->insert([
            'tenant_id' => $request->tenant_id,
            'branch_id' => $request->branch_id,
            'match_request_id' => $request->id,
            'match_date' => $request->preferred_date,
            'start_time' => $request->preferred_start_time,
            'end_time' => $request->preferred_end_time,
            'status' => 'confirmed',
        ]);

        foreach ($allPlayers as $index => $playerId) {
            $this->socialMatchPlayerModel->insert([
                'tenant_id' => $request->tenant_id,
                'social_match_id' => $socialMatchId,
                'player_id' => $playerId,
                'team_side' => $index % 2 === 0 ? 'A' : 'B',
                'status' => 'confirmed',
            ]);
        }

        $this->matchRequestModel->update($request->id, ['status' => 'matched']);
        $db->transComplete();

        return [
            'success' => $db->transStatus(),
            'message' => $db->transStatus() ? 'Đã xác nhận trận.' : 'Không thể xác nhận trận.',
            'social_match' => $socialMatchId ? $this->socialMatchModel->find($socialMatchId) : null,
        ];
    }

    public function convertToBooking(int $socialMatchId, ?int $createdBy = null): array
    {
        $match = $this->socialMatchModel->find($socialMatchId);
        if (! $match) {
            return ['success' => false, 'message' => 'Không tìm thấy trận.'];
        }

        if ($match->booking_id) {
            return ['success' => true, 'message' => 'Trận đã có booking.', 'booking' => $this->bookingModel->find($match->booking_id)];
        }

        $court = $this->findAvailableCourt((int) $match->branch_id, $match->match_date, $match->start_time, $match->end_time);
        if (! $court) {
            return ['success' => false, 'message' => 'Không tìm thấy sân trống để chuyển thành booking.'];
        }

        $players = $this->socialMatchPlayerModel->getByMatch($socialMatchId);
        $host = $players[0] ?? null;
        $hostPlayer = $host ? $this->playerModel->find($host->player_id) : null;

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

    public function hasPlayerConflict(int $playerId, string $date, string $startTime, string $endTime): bool
    {
        $bookingConflict = $this->bookingModel
            ->where('player_id', $playerId)
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
            ->where('social_match_players.status !=', 'declined')
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

    protected function findAvailableCourt(int $branchId, string $date, string $startTime, string $endTime)
    {
        $courts = model(CourtModel::class)->getByBranch($branchId, ['status' => 'available']);
        foreach ($courts as $court) {
            if ($this->bookingModel->isCourtAvailable((int) $court->id, $date, $startTime, $endTime)) {
                return $court;
            }
        }

        return null;
    }
}
