<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\CompetitionEventModel;
use App\Models\CompetitionFixtureModel;
use App\Models\CompetitionParticipantModel;
use App\Models\CompetitionStandingModel;
use App\Models\CompetitionCheckinModel;
use App\Models\CompetitionLadderChallengeModel;
use App\Models\PlayerModel;
use App\Models\TeamModel;
use App\Models\TournamentModel;

class CompetitionService
{
    private CompetitionEventModel $eventModel;
    private CompetitionParticipantModel $participantModel;
    private CompetitionFixtureModel $fixtureModel;
    private CompetitionStandingModel $standingModel;
    private BranchModel $branchModel;
    private TeamModel $teamModel;
    private PlayerModel $playerModel;
    private TournamentModel $tournamentModel;
    private CompetitionCheckinModel $checkinModel;
    private CompetitionLadderChallengeModel $ladderChallengeModel;

    public function __construct()
    {
        $this->eventModel = new CompetitionEventModel();
        $this->participantModel = new CompetitionParticipantModel();
        $this->fixtureModel = new CompetitionFixtureModel();
        $this->standingModel = new CompetitionStandingModel();
        $this->branchModel = new BranchModel();
        $this->teamModel = new TeamModel();
        $this->playerModel = new PlayerModel();
        $this->tournamentModel = new TournamentModel();
        $this->checkinModel = new CompetitionCheckinModel();
        $this->ladderChallengeModel = new CompetitionLadderChallengeModel();
    }

    public function events(int $tenantId): array { return $this->eventModel->getByTenant($tenantId); }
    public function participants(int $eventId, int $tenantId): array { return $this->participantModel->getByEvent($eventId, $tenantId); }
    public function fixtures(int $eventId, int $tenantId): array { return $this->fixtureModel->getByEvent($eventId, $tenantId); }
    public function standings(int $eventId, int $tenantId): array { return $this->standingModel->getByEvent($eventId, $tenantId); }
    public function checkins(int $eventId, int $tenantId): array { return $this->checkinModel->getByEvent($eventId, $tenantId); }
    public function ladderChallenges(int $eventId, int $tenantId): array { return $this->ladderChallengeModel->getByEvent($eventId, $tenantId); }
    public function participantForPlayer(int $eventId, int $playerId, int $tenantId): ?object { return $this->participantModel->where('event_id', $eventId)->where('player_id', $playerId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first(); }

    public function createEvent(array $data, int $tenantId, ?int $userId = null): array
    {
        $branchId = (int) ($data['branch_id'] ?? 0);
        $format = (string) ($data['format'] ?? 'round_robin');
        $entryFee = round((float) ($data['entry_fee'] ?? 0), 2);
        if (!$tenantId || !$this->branchModel->findForTenant($branchId, $tenantId) || !in_array($format, ['round_robin', 'league', 'ladder'], true) || trim((string) ($data['name'] ?? '')) === '' || !self::isValidEntryFee($entryFee)) {
            return ['success' => false, 'message' => 'Competition không hợp lệ.'];
        }
        if (!self::isValidDateRange((string) ($data['start_date'] ?? ''), (string) ($data['end_date'] ?? ''))) {
            return ['success' => false, 'message' => 'Khoảng ngày competition không hợp lệ.'];
        }
        if (!empty($data['tournament_id']) && !$this->tournamentModel->findForTenant((int) $data['tournament_id'], $tenantId)) {
            return ['success' => false, 'message' => 'Tournament không thuộc tenant hiện tại.'];
        }
        $id = $this->eventModel->insert(['tenant_id' => $tenantId, 'branch_id' => $branchId, 'tournament_id' => !empty($data['tournament_id']) ? (int) $data['tournament_id'] : null, 'name' => trim((string) $data['name']), 'format' => $format, 'entry_fee' => $entryFee, 'scoring_rules' => !empty($data['scoring_rules']) ? (is_array($data['scoring_rules']) ? json_encode($data['scoring_rules']) : $data['scoring_rules']) : json_encode(['win' => 3, 'draw' => 1, 'loss' => 0]), 'start_date' => $data['start_date'] ?: null, 'end_date' => $data['end_date'] ?: null, 'status' => 'open', 'created_by' => $userId, 'updated_by' => $userId]);
        if (!$id) return ['success' => false, 'message' => 'Không thể tạo competition.'];
        $this->audit('event_created', (int) $id, $tenantId, ['format' => $format]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã tạo competition.'];
    }

    public function updateEntryFee(int $eventId, float $entryFee, int $tenantId, ?int $userId = null): array
    {
        $entryFee = round($entryFee, 2);
        $db = \Config\Database::connect(); $db->transStart();
        $event = $this->eventModel->findForUpdate($eventId, $tenantId);
        if (!$event || !in_array($event->status, ['open', 'running'], true) || !self::isValidEntryFee($entryFee)) { $db->transRollback(); return ['success' => false, 'message' => 'Competition không thể cập nhật phí.']; }
        if (!$this->eventModel->update($eventId, ['entry_fee' => $entryFee, 'updated_by' => $userId])) { $db->transRollback(); return ['success' => false, 'message' => 'Không thể cập nhật phí.']; }
        $db->transComplete(); if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể cập nhật phí.'];
        $this->audit('entry_fee_updated', $eventId, $tenantId, ['entry_fee' => $entryFee, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã cập nhật phí tham dự.'];
    }

    public function addParticipant(int $eventId, array $data, int $tenantId): array
    {
        $db = \Config\Database::connect(); $db->transStart();
        $event = $this->eventModel->findForUpdate($eventId, $tenantId);
        $teamId = !empty($data['team_id']) ? (int) $data['team_id'] : null;
        $playerId = !empty($data['player_id']) ? (int) $data['player_id'] : null;
        if (!$event || !in_array($event->status, ['open', 'running'], true) || ($teamId && $playerId) || (!$teamId && !$playerId)) { $db->transRollback(); return ['success' => false, 'message' => 'Participant không hợp lệ.']; }
        $name = '';
        if ($teamId) {
            $team = $this->teamModel->findForTenant($teamId, $tenantId);
            if (!$team) { $db->transRollback(); return ['success' => false, 'message' => 'Team không thuộc tenant hiện tại.']; }
            $name = (string) $team->team_name;
        } else {
            $player = $this->playerModel->findForTenant($playerId, $tenantId);
            if (!$player) { $db->transRollback(); return ['success' => false, 'message' => 'Player không thuộc tenant hiện tại.']; }
            $name = (string) $player->full_name;
        }
        $duplicate = $this->participantModel->where('event_id', $eventId)->where('tenant_id', $tenantId)->where('deleted_at', null)->groupStart()->where('team_id', $teamId)->where('player_id', $playerId)->groupEnd()->first();
        if ($duplicate) { $db->transComplete(); return ['success' => true, 'duplicate' => true, 'message' => 'Participant đã tồn tại.']; }
        $id = $this->participantModel->insert(['tenant_id' => $tenantId, 'event_id' => $eventId, 'team_id' => $teamId, 'player_id' => $playerId, 'display_name' => $name, 'seed' => !empty($data['seed']) ? (int) $data['seed'] : null, 'status' => 'active']);
        if (!$id) { $db->transRollback(); return ['success' => false, 'message' => 'Không thể thêm participant.']; }
        if ($playerId && (float) $event->entry_fee > 0) {
            $invoice = (new InvoiceService())->createInvoice($tenantId, (int) $event->branch_id, 'COMP-' . $tenantId . '-' . $eventId . '-' . $id, (float) $event->entry_fee, ['customer_type' => 'player', 'player_id' => $playerId, 'ref_type' => 'competition_participant', 'ref_id' => $id, 'note' => 'Competition: ' . $event->name]);
            if (!$invoice || empty($invoice->id) || !$this->participantModel->update((int) $id, ['invoice_id' => (int) $invoice->id])) { $db->transRollback(); return ['success' => false, 'message' => 'Không thể tạo hóa đơn competition.']; }
        }
        $db->transComplete(); if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể thêm participant.'];
        $this->audit('participant_added', (int) $id, $tenantId, ['event_id' => $eventId, 'invoice_created' => $playerId && (float) $event->entry_fee > 0]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã thêm participant.'];
    }

    public function payEntryFee(int $participantId, int $playerId, int $tenantId, ?int $userId = null): array
    {
        $participant = $this->participantModel->where('id', $participantId)->where('player_id', $playerId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
        if (!$participant || empty($participant->invoice_id)) return ['success' => false, 'message' => 'Participant chưa có hóa đơn.'];
        $invoice = (new \App\Models\InvoiceModel())->findForTenant((int) $participant->invoice_id, $tenantId);
        if (!$invoice || in_array($invoice->status, ['paid', 'cancelled', 'refunded'], true)) return ['success' => false, 'message' => 'Hóa đơn competition không thể thanh toán.'];
        $remaining = round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2);
        if ($remaining <= 0) return ['success' => true, 'message' => 'Hóa đơn đã thanh toán.'];
        try { $result = (new PaymentService())->payByWallet((int) $invoice->id, $remaining, $playerId, ['created_by' => $userId, 'idempotency_key' => 'competition-entry-' . $participantId], $tenantId); } catch (\Throwable $exception) { return ['success' => false, 'message' => $exception->getMessage()]; }
        if (!empty($result['success'])) { $this->audit('entry_fee_paid', $participantId, $tenantId, ['player_id' => $playerId]); return ['success' => true, 'message' => 'Đã thanh toán phí competition bằng ví.']; }
        return ['success' => false, 'message' => $result['message'] ?? 'Không thể thanh toán phí competition.'];
    }

    public function generateRoundRobin(int $eventId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $event = $this->eventModel->findForUpdate($eventId, $tenantId);
        if (!$event || !in_array($event->format, ['round_robin', 'league'], true) || !in_array($event->status, ['open', 'running'], true)) { $db->transRollback(); return ['success' => false, 'message' => 'Competition không thể tạo lịch.']; }
        if ($this->fixtureModel->where('event_id', $eventId)->where('tenant_id', $tenantId)->countAllResults() > 0) { $db->transComplete(); return ['success' => true, 'duplicate' => true, 'message' => 'Lịch thi đấu đã được tạo.']; }
        $participants = $this->participantModel->getByEvent($eventId, $tenantId);
        if (count($participants) < 2) { $db->transRollback(); return ['success' => false, 'message' => 'Cần ít nhất 2 participant.']; }
        $ids = array_map(static fn (object $p): int => (int) $p->id, $participants);
        foreach (self::buildRoundRobinPairs($ids) as $roundNo => $pairs) {
            foreach ($pairs as $matchNo => [$a, $b]) {
                $this->fixtureModel->insert(['tenant_id' => $tenantId, 'event_id' => $eventId, 'round_no' => $roundNo + 1, 'match_no' => $matchNo + 1, 'participant_a_id' => $a, 'participant_b_id' => $b, 'scheduled_date' => $event->start_date, 'status' => 'scheduled']);
            }
        }
        foreach ($ids as $id) $this->standingModel->insert(['tenant_id' => $tenantId, 'event_id' => $eventId, 'participant_id' => $id, 'updated_at' => date('Y-m-d H:i:s')]);
        $this->eventModel->update($eventId, ['status' => 'running', 'updated_by' => $userId]);
        $db->transComplete();
        if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể tạo lịch thi đấu.'];
        $this->audit('round_robin_generated', $eventId, $tenantId, ['participant_count' => count($ids)]);
        return ['success' => true, 'message' => 'Đã tạo lịch round-robin.'];
    }

    public function recordResult(int $fixtureId, int $scoreA, int $scoreB, int $tenantId, ?int $userId = null): array
    {
        if ($scoreA < 0 || $scoreB < 0 || $scoreA > 99 || $scoreB > 99) return ['success' => false, 'message' => 'Tỷ số không hợp lệ.'];
        $db = \Config\Database::connect();
        $db->transStart();
        $fixture = $this->fixtureModel->findForUpdate($fixtureId, $tenantId);
        if (!$fixture || $fixture->status === 'cancelled' || $fixture->status === 'completed') { $db->transRollback(); return ['success' => false, 'message' => 'Fixture không thể ghi kết quả.']; }
        $winner = $scoreA === $scoreB ? null : ($scoreA > $scoreB ? (int) $fixture->participant_a_id : (int) $fixture->participant_b_id);
        $this->fixtureModel->update($fixtureId, ['score_a' => $scoreA, 'score_b' => $scoreB, 'winner_id' => $winner, 'status' => 'completed']);
        $this->rebuildStandings((int) $fixture->event_id, $tenantId);
        $db->transComplete();
        if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể lưu kết quả.'];
        $this->audit('fixture_result_recorded', $fixtureId, $tenantId, ['score_a' => $scoreA, 'score_b' => $scoreB, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã lưu kết quả.'];
    }

    public function checkIn(int $participantId, string $status, int $tenantId, ?int $userId = null): array
    {
        if (!in_array($status, ['pending', 'checked_in', 'no_show'], true)) return ['success' => false, 'message' => 'Trạng thái check-in không hợp lệ.'];
        $participant = $this->participantModel->findForTenant($participantId, $tenantId);
        if (!$participant) return ['success' => false, 'message' => 'Participant không thuộc tenant.'];
        $existing = $this->checkinModel->findForParticipant((int) $participant->event_id, $participantId, $tenantId);
        $payload = ['tenant_id' => $tenantId, 'event_id' => (int) $participant->event_id, 'participant_id' => $participantId, 'status' => $status, 'checkin_at' => $status === 'checked_in' ? date('Y-m-d H:i:s') : ($existing->checkin_at ?? null)];
        $ok = $existing ? $this->checkinModel->update((int) $existing->id, $payload) : $this->checkinModel->insert($payload);
        if (!$ok) return ['success' => false, 'message' => 'Không thể lưu check-in.'];
        $this->audit('participant_' . $status, $participantId, $tenantId, ['event_id' => $participant->event_id, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã cập nhật check-in.'];
    }

    public function createLadderChallenge(int $eventId, int $challengerId, int $opponentId, array $data, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect(); $db->transStart();
        $event = $this->eventModel->findForUpdate($eventId, $tenantId);
        $challenger = $this->participantModel->findForTenant($challengerId, $tenantId);
        $opponent = $this->participantModel->findForTenant($opponentId, $tenantId);
        if (!$event || $event->format !== 'ladder' || !in_array($event->status, ['open', 'running'], true) || !$challenger || !$opponent || $challengerId === $opponentId || (int) $challenger->event_id !== $eventId || (int) $opponent->event_id !== $eventId) {
            $db->transRollback(); return ['success' => false, 'message' => 'Ladder challenge không hợp lệ.'];
        }
        $challengerRank = $this->ladderRank($eventId, $challengerId, $tenantId); $opponentRank = $this->ladderRank($eventId, $opponentId, $tenantId);
        if (!self::canChallengeRank($challengerRank, $opponentRank)) { $db->transRollback(); return ['success' => false, 'message' => 'Chỉ được thách đấu người ở trên tối đa 2 bậc.']; }
        if ($this->ladderChallengeModel->where('event_id', $eventId)->where('tenant_id', $tenantId)->where('challenger_id', $challengerId)->whereIn('status', ['requested', 'accepted'])->countAllResults() > 0) { $db->transRollback(); return ['success' => false, 'message' => 'Bạn đang có challenge chưa kết thúc.']; }
        $date = (string) ($data['scheduled_date'] ?? ''); $time = (string) ($data['start_time'] ?? '');
        if (!self::isValidDateRange($date, $date) || $date < date('Y-m-d') || ($event->start_date && $date < $event->start_date) || ($event->end_date && $date > $event->end_date) || ($time !== '' && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time) !== 1)) { $db->transRollback(); return ['success' => false, 'message' => 'Lịch challenge không hợp lệ.']; }
        $id = $this->ladderChallengeModel->insert(['tenant_id' => $tenantId, 'event_id' => $eventId, 'challenger_id' => $challengerId, 'opponent_id' => $opponentId, 'scheduled_date' => $date, 'start_time' => $time ?: null, 'status' => 'requested', 'expires_at' => date('Y-m-d H:i:s', strtotime('+48 hours')), 'created_by' => $userId]);
        $db->transComplete(); if (!$id || !$db->transStatus()) return ['success' => false, 'message' => 'Không thể tạo ladder challenge.'];
        $this->audit('ladder_challenge_created', (int) $id, $tenantId, ['event_id' => $eventId, 'challenger_id' => $challengerId, 'opponent_id' => $opponentId]);
        if (!empty($opponent->player_id)) {
            $opponentPlayer = $this->playerModel->findForTenant((int) $opponent->player_id, $tenantId);
            if (!empty($opponentPlayer->user_id)) (new NotificationService())->notifyUser((int) $opponentPlayer->user_id, 'ladder_challenge_received', ['challenger_name' => $challenger->display_name, 'event_name' => $event->name], $tenantId, '/player/competitions?event_id=' . $eventId);
        }
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã gửi ladder challenge.'];
    }

    public function respondLadderChallenge(int $challengeId, bool $accept, int $tenantId, ?int $userId = null, ?int $actorParticipantId = null): array
    {
        $db = \Config\Database::connect(); $db->transStart(); $challenge = $this->ladderChallengeModel->findForUpdate($challengeId, $tenantId);
        if (!$challenge || ($actorParticipantId !== null && (int) $challenge->opponent_id !== $actorParticipantId) || $challenge->status !== 'requested' || ($challenge->expires_at && $challenge->expires_at < date('Y-m-d H:i:s'))) { $db->transRollback(); return ['success' => false, 'message' => 'Challenge đã hết hạn hoặc bạn không có quyền xử lý.']; }
        $this->ladderChallengeModel->update($challengeId, ['status' => $accept ? 'accepted' : 'rejected']); $db->transComplete();
        if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể phản hồi challenge.'];
        $this->audit('ladder_challenge_' . ($accept ? 'accepted' : 'rejected'), $challengeId, $tenantId, ['user_id' => $userId]);
        $challenger = $this->participantModel->findForTenant((int) $challenge->challenger_id, $tenantId);
        $opponent = $this->participantModel->findForTenant((int) $challenge->opponent_id, $tenantId);
        if ($challenger && !empty($challenger->player_id)) {
            $challengerPlayer = $this->playerModel->findForTenant((int) $challenger->player_id, $tenantId);
            if (!empty($challengerPlayer->user_id)) (new NotificationService())->notifyUser((int) $challengerPlayer->user_id, 'ladder_challenge_' . ($accept ? 'accepted' : 'rejected'), ['opponent_name' => $opponent->display_name ?? ('#' . $challenge->opponent_id)], $tenantId, '/player/competitions?event_id=' . $challenge->event_id);
        }
        return ['success' => true, 'message' => $accept ? 'Đã chấp nhận challenge.' : 'Đã từ chối challenge.'];
    }

    public function recordLadderResult(int $challengeId, int $scoreA, int $scoreB, int $tenantId, ?int $userId = null): array
    {
        if ($scoreA < 0 || $scoreB < 0 || $scoreA > 99 || $scoreB > 99) return ['success' => false, 'message' => 'Tỷ số không hợp lệ.'];
        $db = \Config\Database::connect(); $db->transStart(); $challenge = $this->ladderChallengeModel->findForUpdate($challengeId, $tenantId);
        if (!$challenge || $challenge->status !== 'accepted') { $db->transRollback(); return ['success' => false, 'message' => 'Challenge chưa được chấp nhận.']; }
        $next = (int) ($db->query('SELECT COALESCE(MAX(match_no), 0) + 1 AS next_no FROM competition_fixtures WHERE event_id = ? AND round_no = 0', [(int) $challenge->event_id])->getRow()->next_no ?? 1);
        $winner = $scoreA === $scoreB ? null : ($scoreA > $scoreB ? (int) $challenge->challenger_id : (int) $challenge->opponent_id);
        $fixtureId = $this->fixtureModel->insert(['tenant_id' => $tenantId, 'event_id' => (int) $challenge->event_id, 'round_no' => 0, 'match_no' => $next, 'participant_a_id' => (int) $challenge->challenger_id, 'participant_b_id' => (int) $challenge->opponent_id, 'scheduled_date' => $challenge->scheduled_date, 'start_time' => $challenge->start_time, 'score_a' => $scoreA, 'score_b' => $scoreB, 'winner_id' => $winner, 'status' => 'completed']);
        if (!$fixtureId || !$this->ladderChallengeModel->update($challengeId, ['fixture_id' => $fixtureId, 'score_challenger' => $scoreA, 'score_opponent' => $scoreB, 'winner_id' => $winner, 'status' => 'completed'])) { $db->transRollback(); return ['success' => false, 'message' => 'Không thể lưu kết quả ladder.']; }
        $this->rebuildStandings((int) $challenge->event_id, $tenantId);
        if ($winner === (int) $challenge->challenger_id && $scoreA > $scoreB) $this->promoteLadder((int) $challenge->event_id, (int) $challenge->challenger_id, (int) $challenge->opponent_id, $tenantId);
        $db->transComplete(); if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể hoàn tất ladder result.'];
        $this->audit('ladder_result_recorded', $challengeId, $tenantId, ['fixture_id' => $fixtureId, 'score_a' => $scoreA, 'score_b' => $scoreB, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã lưu kết quả ladder.'];
    }

    private function ladderRank(int $eventId, int $participantId, int $tenantId): int
    {
        $standing = $this->standingModel->where('event_id', $eventId)->where('participant_id', $participantId)->where('tenant_id', $tenantId)->first();
        if ($standing && $standing->rank_no) return (int) $standing->rank_no;
        $participants = $this->participantModel->getByEvent($eventId, $tenantId); foreach ($participants as $index => $participant) if ((int) $participant->id === $participantId) return $index + 1;
        return PHP_INT_MAX;
    }

    private function promoteLadder(int $eventId, int $challengerId, int $opponentId, int $tenantId): void
    {
        $challenger = $this->standingModel->where('event_id', $eventId)->where('participant_id', $challengerId)->where('tenant_id', $tenantId)->first(); $opponent = $this->standingModel->where('event_id', $eventId)->where('participant_id', $opponentId)->where('tenant_id', $tenantId)->first();
        if (!$challenger || !$opponent || (int) $challenger->rank_no <= (int) $opponent->rank_no) return;
        $this->standingModel->update($challenger->id, ['rank_no' => $opponent->rank_no]); $this->standingModel->update($opponent->id, ['rank_no' => $challenger->rank_no]);
    }

    public static function buildRoundRobinPairs(array $participantIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $participantIds)));
        if (count($ids) < 2) return [];
        if (count($ids) % 2 !== 0) $ids[] = null;
        $rounds = [];
        $size = count($ids);
        for ($round = 0; $round < $size - 1; $round++) {
            $pairs = [];
            for ($i = 0; $i < $size / 2; $i++) {
                $a = $ids[$i]; $b = $ids[$size - 1 - $i];
                if ($a !== null && $b !== null) $pairs[] = [$a, $b];
            }
            $rounds[] = $pairs;
            $fixed = array_shift($ids); $last = array_pop($ids); array_unshift($ids, $last); array_unshift($ids, $fixed);
        }
        return $rounds;
    }

    public static function sortStandingRows(array $rows): array
    {
        usort($rows, static function (object $a, object $b): int {
            $points = (int) $b->points <=> (int) $a->points;
            if ($points !== 0) return $points;
            $diffA = (int) $a->points_for - (int) $a->points_against;
            $diffB = (int) $b->points_for - (int) $b->points_against;
            return ($diffB <=> $diffA) ?: ((int) $b->wins <=> (int) $a->wins);
        });
        return $rows;
    }

    public static function canChallengeRank(int $challengerRank, int $opponentRank, int $maxGap = 2): bool
    {
        return $maxGap > 0
            && $challengerRank > 0
            && $opponentRank > 0
            && $opponentRank < $challengerRank
            && ($challengerRank - $opponentRank) <= $maxGap;
    }

    public static function isValidEntryFee(float $entryFee): bool
    {
        return is_finite($entryFee) && $entryFee >= 0 && $entryFee <= 1000000000;
    }

    public static function isValidDateRange(string $start, string $end): bool
    {
        $a = \DateTimeImmutable::createFromFormat('!Y-m-d', $start); $b = \DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        return $a && $b && $a->format('Y-m-d') === $start && $b->format('Y-m-d') === $end && $a <= $b;
    }

    private function rebuildStandings(int $eventId, int $tenantId): void
    {
        $participants = $this->participantModel->getByEvent($eventId, $tenantId);
        $stats = [];
        foreach ($participants as $p) $stats[(int) $p->id] = ['played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'points_for' => 0, 'points_against' => 0, 'points' => 0];
        $rules = ['win' => 3, 'draw' => 1, 'loss' => 0];
        $storedRules = $this->eventModel->findForTenant($eventId, $tenantId)->scoring_rules ?? null;
        $storedRules = is_string($storedRules) ? (json_decode($storedRules, true) ?: []) : (array) $storedRules;
        foreach ($storedRules as $key => $value) { if (is_numeric($value)) $rules[$key] = (int) $value; }
        foreach ($this->fixtureModel->where('event_id', $eventId)->where('tenant_id', $tenantId)->where('status', 'completed')->findAll() as $f) {
            $a = (int) $f->participant_a_id; $b = (int) $f->participant_b_id; $sa = (int) $f->score_a; $sb = (int) $f->score_b;
            if (!isset($stats[$a], $stats[$b])) continue;
            $stats[$a]['played']++; $stats[$b]['played']++; $stats[$a]['points_for'] += $sa; $stats[$a]['points_against'] += $sb; $stats[$b]['points_for'] += $sb; $stats[$b]['points_against'] += $sa;
            if ($sa === $sb) { $stats[$a]['draws']++; $stats[$b]['draws']++; $stats[$a]['points'] += $rules['draw']; $stats[$b]['points'] += $rules['draw']; } else { $winner = $sa > $sb ? $a : $b; $loser = $winner === $a ? $b : $a; $stats[$winner]['wins']++; $stats[$loser]['losses']++; $stats[$winner]['points'] += $rules['win']; $stats[$loser]['points'] += $rules['loss']; }
        }
        $rows = []; foreach ($stats as $id => $data) { $row = (object) array_merge(['participant_id' => $id], $data); $rows[] = $row; }
        $rows = self::sortStandingRows($rows);
        $this->standingModel->where('event_id', $eventId)->where('tenant_id', $tenantId)->delete();
        foreach ($rows as $rank => $row) $this->standingModel->insert(['tenant_id' => $tenantId, 'event_id' => $eventId, 'participant_id' => $row->participant_id, 'played' => $row->played, 'wins' => $row->wins, 'draws' => $row->draws, 'losses' => $row->losses, 'points_for' => $row->points_for, 'points_against' => $row->points_against, 'points' => $row->points, 'rank_no' => $rank + 1, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    private function audit(string $action, int $id, int $tenantId, array $data): void
    {
        if (function_exists('log_audit')) log_audit(['action' => 'competition_' . $action, 'entity_type' => 'competition', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => $data]);
    }
}
