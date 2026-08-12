<?php

namespace App\Services;

use App\Models\TeamMemberModel;
use App\Models\TeamModel;
use App\Models\TournamentMatchModel;
use Config\Database;

class TournamentMatchNetworkAdapter
{
    private TournamentMatchModel $tournamentMatchModel;
    private TeamModel $teamModel;
    private TeamMemberModel $teamMemberModel;
    private UnifiedMatchService $unifiedMatchService;

    public function __construct()
    {
        $this->tournamentMatchModel = model(TournamentMatchModel::class);
        $this->teamModel = model(TeamModel::class);
        $this->teamMemberModel = model(TeamMemberModel::class);
        $this->unifiedMatchService = new UnifiedMatchService();
    }

    public function sync(int $tournamentMatchId, int $tenantId, ?int $userId = null): array
    {
        $tournamentMatch = $this->tournamentMatchModel->findForTenant($tournamentMatchId, $tenantId);
        if (! $tournamentMatch) return ['success' => false, 'message' => 'Tournament match không tồn tại.'];
        if (! empty($tournamentMatch->unified_match_id)) {
            return ['success' => true, 'idempotent' => true, 'unified_match' => $this->unifiedMatchService->get((int) $tournamentMatch->unified_match_id, $tenantId)];
        }
        if (! $tournamentMatch->team_a_id || ! $tournamentMatch->team_b_id) {
            return ['success' => false, 'message' => 'Tournament match chưa có đủ hai team.'];
        }

        $membersA = $this->playersForTeam((int) $tournamentMatch->team_a_id, $tenantId);
        $membersB = $this->playersForTeam((int) $tournamentMatch->team_b_id, $tenantId);
        if (! $membersA || ! $membersB) return ['success' => false, 'message' => 'Không xác định được người chơi của hai team.'];

        $participants = [];
        foreach ($membersA as $index => $playerId) $participants[] = ['player_id' => $playerId, 'side' => 1, 'sort_order' => $index];
        foreach ($membersB as $index => $playerId) $participants[] = ['player_id' => $playerId, 'side' => 2, 'sort_order' => $index];
        $scheduledAt = $tournamentMatch->scheduled_date && $tournamentMatch->start_time
            ? $tournamentMatch->scheduled_date . ' ' . $tournamentMatch->start_time
            : null;
        $created = $this->unifiedMatchService->create([
            'source_type' => 'tournament',
            'source_id' => $tournamentMatchId,
            'scheduled_at' => $scheduledAt,
            'metadata' => [
                'tournament_id' => (int) $tournamentMatch->tournament_id,
                'category_id' => (int) $tournamentMatch->category_id,
                'round_name' => $tournamentMatch->round_name,
                'match_no' => (int) $tournamentMatch->match_no,
                'court_id' => $tournamentMatch->court_id ? (int) $tournamentMatch->court_id : null,
            ],
            'participants' => $participants,
        ], $tenantId, $userId);
        if (empty($created['success'])) return $created;

        $unifiedId = (int) $created['match']['match']->id;
        $this->tournamentMatchModel->update($tournamentMatchId, ['unified_match_id' => $unifiedId]);
        return ['success' => true, 'unified_match_id' => $unifiedId, 'unified_match' => $created['match']];
    }

    public function publishOfficial(int $tournamentMatchId, int $tenantId, ?int $userId = null): array
    {
        $tournamentMatch = $this->tournamentMatchModel->findForTenant($tournamentMatchId, $tenantId);
        if (! $tournamentMatch) return ['success' => false, 'message' => 'Tournament match không tồn tại.'];
        $synced = $this->sync($tournamentMatchId, $tenantId, $userId);
        if (empty($synced['success'])) return $synced;
        $unifiedId = (int) ($synced['unified_match_id'] ?? $tournamentMatch->unified_match_id);
        $unified = $this->unifiedMatchService->get($unifiedId, $tenantId);
        if (! empty($unified['match']->status) && $unified['match']->status === 'official') {
            return ['success' => true, 'idempotent' => true, 'unified_match' => $unified];
        }
        if (! $tournamentMatch->winner_team_id) return ['success' => false, 'message' => 'Chưa có winner team để publish official.'];
        $winnerSide = (int) $tournamentMatch->winner_team_id === (int) $tournamentMatch->team_a_id ? 1 : 2;
        $scores = Database::connect()->table('tournament_match_scores')
            ->where('tenant_id', $tenantId)->where('match_id', $tournamentMatchId)->orderBy('set_no', 'ASC')->get()->getResultArray();
        $games = array_map(static fn (array $score): array => [
            'game_no' => (int) $score['set_no'],
            'side_a_score' => (int) $score['team_a_score'],
            'side_b_score' => (int) $score['team_b_score'],
        ], $scores);
        $submitted = $this->unifiedMatchService->submitResult($unifiedId, [
            'winner_side' => $winnerSide,
            'games' => $games,
            'result_type' => 'normal',
            'change_reason' => 'tournament_official_result',
        ], $tenantId, $userId);
        if (empty($submitted['success'])) return $submitted;
        $confirmed = $this->unifiedMatchService->confirmResult($unifiedId, $tenantId, $userId);
        if (empty($confirmed['success'])) return $confirmed;
        $published = $this->unifiedMatchService->publishOfficial($unifiedId, $tenantId, $userId);
        if (! empty($published['success'])) {
            try {
                service('webhookService')->dispatch($tenantId, 'match.official', [
                    'match_id' => $unifiedId, 'tournament_match_id' => $tournamentMatchId,
                    'tenant_id' => $tenantId, 'occurred_at' => date('c'),
                ]);
            } catch (\Throwable $exception) {
                log_message('error', 'match.official webhook dispatch failed: ' . $exception->getMessage());
            }
        }
        return $published;
    }

    private function playersForTeam(int $teamId, int $tenantId): array
    {
        $team = $this->teamModel->findForTenant($teamId, $tenantId);
        if (! $team) return [];
        $members = $this->teamMemberModel->getByTeam($teamId, $tenantId);
        $players = array_values(array_unique(array_map(static fn (object $member): int => (int) $member->player_id, array_filter($members, static fn (object $member): bool => $member->status === 'accepted'))));
        if (! $players && $team->captain_player_id) $players[] = (int) $team->captain_player_id;
        return $players;
    }
}
