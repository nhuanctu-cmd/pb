<?php

namespace App\Services;

use App\Models\OpenPlayRotationPlayerModel;
use App\Models\OpenPlayRotationRoundModel;
use App\Models\OpenPlaySessionModel;
use App\Models\OpenPlaySessionPlayerModel;

class OpenPlayRotationService
{
    private OpenPlaySessionModel $sessionModel;
    private OpenPlaySessionPlayerModel $sessionPlayerModel;
    private OpenPlayRotationRoundModel $roundModel;
    private OpenPlayRotationPlayerModel $roundPlayerModel;

    public function __construct()
    {
        $this->sessionModel = new OpenPlaySessionModel();
        $this->sessionPlayerModel = new OpenPlaySessionPlayerModel();
        $this->roundModel = new OpenPlayRotationRoundModel();
        $this->roundPlayerModel = new OpenPlayRotationPlayerModel();
    }

    public function generate(int $sessionId, int $tenantId, int $roundMinutes = 20, ?int $userId = null): array
    {
        $roundMinutes = max(10, min(180, $roundMinutes));
        $db = \Config\Database::connect();
        $db->transStart();
        $session = $this->sessionModel->findForUpdate($sessionId, $tenantId);
        if (!$session || in_array($session->status, ['cancelled', 'completed'], true)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Open Play không còn hoạt động.'];
        }
        if ($this->roundModel->where('session_id', $sessionId)->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults() > 0) {
            $db->transComplete();
            return ['success' => true, 'duplicate' => true, 'message' => 'Rotation đã được tạo trước đó.', 'rounds' => $this->roundModel->getBySession($sessionId, $tenantId)];
        }

        $approved = $this->sessionPlayerModel->where('session_id', $sessionId)->where('tenant_id', $tenantId)->where('status', 'approved')->where('deleted_at', null)->orderBy('player_id', 'ASC')->findAll();
        $playerIds = array_map(static fn ($entry) => (int) $entry->player_id, $approved);
        if (count($playerIds) < 4) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Cần ít nhất 4 người đã được duyệt để tạo rotation.'];
        }

        $start = strtotime($session->session_date . ' ' . $session->start_time);
        $end = strtotime($session->session_date . ' ' . $session->end_time);
        $pairingRounds = self::buildPairings($playerIds);
        $createdRounds = [];
        foreach ($pairingRounds as $roundIndex => $pairs) {
            $roundStart = $start + ($roundIndex * $roundMinutes * 60);
            if ($roundStart >= $end) {
                break;
            }
            $roundEnd = min($roundStart + ($roundMinutes * 60), $end);
            $roundId = $this->roundModel->insert([
                'tenant_id' => $tenantId, 'session_id' => $sessionId, 'round_no' => $roundIndex + 1,
                'start_time' => date('H:i:s', $roundStart), 'end_time' => date('H:i:s', $roundEnd), 'status' => 'planned',
                'created_by' => $userId, 'updated_by' => $userId,
            ]);
            if (!$roundId) {
                $db->transRollback();
                return ['success' => false, 'message' => 'Không thể tạo rotation round.'];
            }
            foreach ($pairs as $pairIndex => $pair) {
                [$first, $second] = $pair;
                $teamSide = $pairIndex % 2 === 0 ? 'A' : 'B';
                $opponents = [];
                foreach ($pairs as $otherIndex => $otherPair) {
                    if ($otherIndex !== $pairIndex) {
                        $opponents = array_merge($opponents, $otherPair);
                    }
                }
                foreach ([[$first, $second], [$second, $first]] as $members) {
                    if (!$this->roundPlayerModel->insert([
                        'tenant_id' => $tenantId, 'round_id' => (int) $roundId, 'player_id' => (int) $members[0],
                        'team_side' => $teamSide, 'partner_player_id' => (int) $members[1], 'opponent_player_ids' => json_encode(array_values($opponents)),
                    ])) {
                        $db->transRollback();
                        return ['success' => false, 'message' => 'Không thể lưu player rotation.'];
                    }
                }
            }
            $createdRounds[] = (int) $roundId;
        }
        if (!$createdRounds) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Thời lượng session không đủ cho rotation.'];
        }
        $db->transComplete();
        if (!$db->transStatus()) {
            return ['success' => false, 'message' => 'Không thể hoàn tất rotation.'];
        }
        $this->audit('generated', $sessionId, $tenantId, ['rounds' => count($createdRounds), 'round_minutes' => $roundMinutes]);
        return ['success' => true, 'message' => 'Đã tạo rotation.', 'rounds' => $this->roundModel->getBySession($sessionId, $tenantId)];
    }

    public function schedule(int $sessionId, int $tenantId): array
    {
        $rounds = $this->roundModel->getBySession($sessionId, $tenantId);
        $result = [];
        foreach ($rounds as $round) {
            $result[(int) $round->id] = $this->roundPlayerModel->getByRound((int) $round->id, $tenantId);
        }
        return $result;
    }

    public function history(int $playerId, int $tenantId, int $limit = 100): array
    {
        return $this->roundPlayerModel
            ->select('open_play_rotation_players.*, open_play_rotation_rounds.round_no, open_play_rotation_rounds.start_time, open_play_rotation_rounds.end_time, open_play_sessions.title, open_play_sessions.session_date')
            ->join('open_play_rotation_rounds', 'open_play_rotation_rounds.id = open_play_rotation_players.round_id')
            ->join('open_play_sessions', 'open_play_sessions.id = open_play_rotation_rounds.session_id')
            ->where('open_play_rotation_players.player_id', $playerId)
            ->where('open_play_rotation_players.tenant_id', $tenantId)
            ->where('open_play_rotation_players.deleted_at', null)
            ->where('open_play_rotation_rounds.deleted_at', null)
            ->where('open_play_sessions.deleted_at', null)
            ->orderBy('open_play_sessions.session_date', 'DESC')
            ->orderBy('open_play_rotation_rounds.round_no', 'DESC')
            ->findAll(max(1, min(500, $limit)));
    }

    public static function waitingSeconds(string $requestedAt, ?string $now = null): int
    {
        $start = strtotime($requestedAt);
        $end = strtotime($now ?: date('Y-m-d H:i:s'));
        return $start && $end > $start ? $end - $start : 0;
    }

    public static function buildPairings(array $playerIds): array
    {
        $players = array_values(array_unique(array_map('intval', $playerIds)));
        sort($players, SORT_NUMERIC);
        if (count($players) < 4) {
            return [];
        }
        if (count($players) % 2 === 1) {
            $players[] = 0;
        }
        $rounds = [];
        $count = count($players);
        for ($round = 0; $round < $count - 1; $round++) {
            $pairs = [];
            $half = intdiv($count, 2);
            $left = array_slice($players, 0, $half);
            $right = array_reverse(array_slice($players, $half));
            for ($index = 0; $index < $half; $index++) {
                if ($left[$index] && $right[$index]) {
                    $pairs[] = [(int) $left[$index], (int) $right[$index]];
                }
            }
            $rounds[] = $pairs;
            $tail = array_slice($players, 1);
            $last = array_pop($tail);
            $players = array_merge([$players[0], $last], $tail);
        }
        return $rounds;
    }

    private function audit(string $action, int $sessionId, int $tenantId, array $data): void
    {
        if (function_exists('log_audit')) {
            log_audit(['action' => 'open_play_rotation_' . $action, 'entity_type' => 'open_play_session', 'entity_id' => $sessionId, 'tenant_id' => $tenantId, 'metadata' => $data]);
        }
    }
}
