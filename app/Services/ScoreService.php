<?php

namespace App\Services;

use App\Models\TournamentMatchScoreModel;
use App\Models\TournamentScoreLogModel;
use CodeIgniter\Database\BaseConnection;

class ScoreService
{
    protected BaseConnection $db;
    protected TournamentMatchScoreModel $scoreModel;
    protected TournamentScoreLogModel $logModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->scoreModel = model(TournamentMatchScoreModel::class);
        $this->logModel = model(TournamentScoreLogModel::class);
    }

    public function startMatch($matchId): array
    {
        $match = $this->getMatch((int) $matchId);
        if (! $match) {
            return ['success' => false, 'message' => 'Không tìm thấy trận đấu.'];
        }

        $data = [];
        if ($this->fieldExists('tournament_matches', 'started_at')) {
            $data['started_at'] = date('Y-m-d H:i:s');
        }
        if ($this->fieldExists('tournament_matches', 'status')) {
            $data['status'] = $this->matchStatus('running');
        }

        $this->updateMatch((int) $matchId, $data);

        return ['success' => true, 'message' => 'Trận đấu đã bắt đầu.', 'match' => $this->getMatch((int) $matchId)];
    }

    public function updateScore($matchId, $sets): array
    {
        $matchId = (int) $matchId;
        $match = $this->getMatch($matchId);
        if (! $match) {
            return ['success' => false, 'message' => 'Không tìm thấy trận đấu.'];
        }

        $tenantId = (int) ($match->tenant_id ?? current_tenant_id() ?? 0);
        if ($tenantId <= 0) {
            return ['success' => false, 'message' => 'Thiếu tenant_id cho trận đấu.'];
        }

        $oldScores = $this->scoresToArray($this->scoreModel->getByMatch($matchId));
        $reason = $this->scoreReason();
        if (! empty($oldScores) && trim((string) $reason) === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập lý do khi sửa điểm.'];
        }

        $bestOf = $this->resolveBestOf((array) $sets, $match);
        $normalized = $this->normalizeSets((array) $sets, $match, $bestOf);
        if (empty($normalized)) {
            return ['success' => false, 'message' => 'Vui lòng nhập ít nhất một set.'];
        }

        $winner = $this->determineWinner($normalized);
        $this->db->transStart();

        $this->scoreModel->where('match_id', $matchId)->delete();
        foreach ($normalized as $set) {
            $this->scoreModel->insert([
                'tenant_id' => $tenantId,
                'match_id' => $matchId,
                'set_no' => $set['set_no'],
                'team_a_score' => $set['team_a_score'],
                'team_b_score' => $set['team_b_score'],
                'winner_team_id' => $set['winner_team_id'],
            ]);
        }

        $matchData = [];
        if ($this->fieldExists('tournament_matches', 'status')) {
            $matchData['status'] = $winner['winner_team_id'] ? 'completed' : $this->matchStatus('running');
        }
        if ($winner['winner_team_id'] && $this->fieldExists('tournament_matches', 'winner_team_id')) {
            $matchData['winner_team_id'] = $winner['winner_team_id'];
        }
        if ($winner['winner_team_id'] && $this->fieldExists('tournament_matches', 'finished_at')) {
            $matchData['finished_at'] = date('Y-m-d H:i:s');
        }
        if (! empty($matchData)) {
            $this->updateMatch($matchId, $matchData);
        }

        $newScores = $this->scoresToArray($normalized);
        $this->logScoreChange($tenantId, $matchId, $oldScores, $newScores, $this->currentUserId(), $reason ?: 'Nhập điểm');
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['success' => false, 'message' => 'Không thể lưu điểm.'];
        }

        $standing = $winner['winner_team_id'] ? $this->updateGroupStanding($matchId) : ['skipped' => true];
        $bracket = $winner['winner_team_id'] ? $this->advanceBracket($matchId) : ['skipped' => true];

        $this->publishScoreUpdated($matchId, $newScores);

        return [
            'success' => true,
            'message' => 'Đã cập nhật điểm.',
            'winner' => $winner,
            'scores' => $newScores,
            'standing' => $standing,
            'bracket' => $bracket,
        ];
    }

    public function finishMatch($matchId): array
    {
        $matchId = (int) $matchId;
        $match = $this->getMatch($matchId);
        $scores = $this->scoresToArray($this->scoreModel->getByMatch($matchId));
        $bestOf = $match ? $this->resolveBestOf($scores, $match) : count($scores);
        foreach ($scores as &$score) {
            $score['best_of'] = $bestOf;
        }
        unset($score);
        $winner = $this->determineWinner($scores);

        if (! $winner['winner_team_id']) {
            return ['success' => false, 'message' => 'Chưa đủ điểm để xác định đội thắng.'];
        }

        $data = [];
        if ($this->fieldExists('tournament_matches', 'status')) {
            $data['status'] = 'completed';
        }
        if ($this->fieldExists('tournament_matches', 'winner_team_id')) {
            $data['winner_team_id'] = $winner['winner_team_id'];
        }
        if ($this->fieldExists('tournament_matches', 'finished_at')) {
            $data['finished_at'] = date('Y-m-d H:i:s');
        }
        $this->updateMatch($matchId, $data);

        return [
            'success' => true,
            'message' => 'Đã xác nhận kết quả trận.',
            'winner' => $winner,
            'standing' => $this->updateGroupStanding($matchId),
            'bracket' => $this->advanceBracket($matchId),
        ];
    }

    public function determineWinner($sets): array
    {
        $wins = [];
        $played = 0;

        foreach ((array) $sets as $set) {
            $winnerTeamId = (int) ($set['winner_team_id'] ?? 0);
            if ($winnerTeamId <= 0) {
                continue;
            }
            $played++;
            $wins[$winnerTeamId] = ($wins[$winnerTeamId] ?? 0) + 1;
        }

        $firstSet = array_values((array) $sets)[0] ?? [];
        $bestOf = (int) ($firstSet['best_of'] ?? count((array) $sets) ?: 1);
        $bestOf = $bestOf >= 5 ? 5 : ($bestOf >= 3 ? 3 : 1);
        $requiredWins = (int) floor($bestOf / 2) + 1;

        foreach ($wins as $teamId => $count) {
            if ($count >= $requiredWins || ($bestOf === 1 && $played >= 1)) {
                return ['winner_team_id' => (int) $teamId, 'sets_won' => (int) $count, 'required_sets' => $requiredWins];
            }
        }

        return ['winner_team_id' => null, 'sets_won' => 0, 'required_sets' => $requiredWins];
    }

    public function updateGroupStanding($matchId): array
    {
        if (! $this->db->tableExists('tournament_group_standings')) {
            return ['skipped' => true, 'reason' => 'missing tournament_group_standings'];
        }

        $match = $this->getMatch((int) $matchId);
        if (! $match || empty($match->group_id) || empty($match->team_a_id) || empty($match->team_b_id) || empty($match->winner_team_id)) {
            return ['skipped' => true, 'reason' => 'match is not a group match or has no winner'];
        }

        foreach ([(int) $match->team_a_id, (int) $match->team_b_id] as $teamId) {
            $row = $this->db->table('tournament_group_standings')
                ->where('tenant_id', (int) $match->tenant_id)
                ->where('group_id', (int) $match->group_id)
                ->where('team_id', $teamId)
                ->get()
                ->getRow();

            $isWinner = $teamId === (int) $match->winner_team_id;
            $payload = [
                'tenant_id' => (int) $match->tenant_id,
                'group_id' => (int) $match->group_id,
                'team_id' => $teamId,
                'played' => (int) ($row->played ?? 0) + 1,
                'wins' => (int) ($row->wins ?? 0) + ($isWinner ? 1 : 0),
                'losses' => (int) ($row->losses ?? 0) + ($isWinner ? 0 : 1),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($row) {
                $this->db->table('tournament_group_standings')->where('id', (int) $row->id)->update($payload);
            } else {
                $payload['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('tournament_group_standings')->insert($payload);
            }
        }

        return ['updated' => true];
    }

    public function advanceBracket($matchId): array
    {
        $match = $this->getMatch((int) $matchId);
        if (! $match || empty($match->winner_team_id) || ! $this->db->tableExists('tournament_matches')) {
            return ['skipped' => true, 'reason' => 'no next bracket match'];
        }

        $nextMatchId = (int) ($match->next_match_id ?? 0);
        $slot = strtolower((string) ($match->next_match_slot ?? ''));

        if ($nextMatchId <= 0 && $this->db->tableExists('tournament_brackets')) {
            $bracket = $this->db->table('tournament_brackets')->where('match_id', (int) $matchId)->get()->getRow();
            $nextMatchId = (int) ($bracket->next_match_id ?? 0);
        }

        if ($nextMatchId <= 0) {
            return ['skipped' => true, 'reason' => 'no next bracket match'];
        }

        $nextMatch = $this->getMatch($nextMatchId);
        if (! $slot && $nextMatch) {
            $slot = empty($nextMatch->team_a_id) ? 'a' : 'b';
        }

        $field = in_array($slot, ['b', 'team_b', '2'], true) ? 'team_b_id' : 'team_a_id';
        if (! $this->fieldExists('tournament_matches', $field)) {
            return ['skipped' => true, 'reason' => 'missing bracket target field'];
        }

        $this->updateMatch($nextMatchId, [$field => (int) $match->winner_team_id]);
        return ['advanced' => true, 'next_match_id' => $nextMatchId, 'slot' => $field];
    }

    public function logScoreChange(?int $tenantId = null, ?int $matchId = null, array $oldScore = [], array $newScore = [], ?int $changedBy = null, ?string $reason = null): int
    {
        return $this->logModel->addLog((int) $tenantId, (int) $matchId, $oldScore, $newScore, $changedBy, $reason);
    }

    public function getMatch(int $matchId): ?object
    {
        if (! $this->db->tableExists('tournament_matches')) {
            return null;
        }

        return $this->db->table('tournament_matches')->where('id', $matchId)->get()->getRow();
    }

    protected function normalizeSets(array $sets, object $match, int $bestOf): array
    {
        $normalized = [];
        $teamAId = (int) ($match->team_a_id ?? 0);
        $teamBId = (int) ($match->team_b_id ?? 0);

        foreach ($sets as $i => $set) {
            $a = max(0, (int) ($set['team_a_score'] ?? $set['a'] ?? 0));
            $b = max(0, (int) ($set['team_b_score'] ?? $set['b'] ?? 0));
            if ($a === 0 && $b === 0) {
                continue;
            }

            $winnerTeamId = null;
            if ($a !== $b) {
                $winnerTeamId = $a > $b ? ($teamAId ?: null) : ($teamBId ?: null);
            }

            $normalized[] = [
                'set_no' => (int) ($set['set_no'] ?? $i + 1),
                'team_a_score' => $a,
                'team_b_score' => $b,
                'winner_team_id' => $winnerTeamId,
                'best_of' => $bestOf,
            ];
        }

        return $normalized;
    }

    protected function scoresToArray(array $scores): array
    {
        return array_map(static function ($score): array {
            $row = is_array($score) ? $score : (array) $score;
            return [
                'set_no' => (int) ($row['set_no'] ?? 0),
                'team_a_score' => (int) ($row['team_a_score'] ?? 0),
                'team_b_score' => (int) ($row['team_b_score'] ?? 0),
                'winner_team_id' => isset($row['winner_team_id']) ? (int) $row['winner_team_id'] : null,
                'best_of' => isset($row['best_of']) ? (int) $row['best_of'] : null,
            ];
        }, $scores);
    }

    protected function resolveBestOf(array $sets, object $match): int
    {
        $raw = (int) ($match->best_of ?? $match->number_of_sets ?? $match->sets_count ?? 0);
        if ($raw <= 0 && ! empty($match->match_format) && preg_match('/BO([135])/', strtoupper((string) $match->match_format), $matches)) {
            $raw = (int) $matches[1];
        }
        if ($raw <= 0) {
            $postedBestOf = service('request')->getPost('best_of');
            $raw = $postedBestOf ? (int) $postedBestOf : 0;
        }
        if ($raw <= 0 && ! empty($sets)) {
            $first = array_values($sets)[0] ?? [];
            $raw = (int) ($first['best_of'] ?? 0);
        }
        if ($raw <= 0) {
            $raw = count($sets);
        }

        return $raw >= 5 ? 5 : ($raw >= 3 ? 3 : 1);
    }

    protected function updateMatch(int $matchId, array $data): void
    {
        if (! empty($data) && $this->db->tableExists('tournament_matches')) {
            $this->db->table('tournament_matches')->where('id', $matchId)->update($data);
        }
    }

    protected function fieldExists(string $table, string $field): bool
    {
        return $this->db->tableExists($table) && $this->db->fieldExists($field, $table);
    }

    protected function matchStatus(string $wanted): string
    {
        if ($wanted !== 'running') {
            return $wanted;
        }

        return 'running';
    }

    protected function currentUserId(): ?int
    {
        return session()->get('user_id') ?? session()->get('userId') ?? null;
    }

    protected function scoreReason(): ?string
    {
        return service('request')->getPost('reason') ?: 'Cập nhật điểm';
    }

    protected function publishScoreUpdated(int $matchId, array $scores): void
    {
        log_message('info', 'score.updated match_id=' . $matchId . ' payload=' . json_encode($scores));
    }
}
