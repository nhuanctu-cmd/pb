<?php

namespace App\Services;

use App\Models\PlayerBadgeModel;
use App\Models\PlayerLevelModel;
use App\Models\PlayerMatchHistoryModel;
use App\Models\PlayerModel;
use App\Models\PlayerRatingModel;
use App\Models\PlayerStatisticModel;

class PlayerRatingService
{
    protected PlayerModel $playerModel;
    protected PlayerRatingModel $ratingModel;
    protected PlayerMatchHistoryModel $matchModel;
    protected PlayerStatisticModel $statModel;
    protected PlayerLevelModel $levelModel;
    protected PlayerBadgeModel $badgeModel;

    public function __construct()
    {
        $this->playerModel = new PlayerModel();
        $this->ratingModel = new PlayerRatingModel();
        $this->matchModel = new PlayerMatchHistoryModel();
        $this->statModel = new PlayerStatisticModel();
        $this->levelModel = new PlayerLevelModel();
        $this->badgeModel = new PlayerBadgeModel();
    }

    public function recordMatch(int $tenantId, int $playerId, ?int $opponentId, string $result, array $context = []): ?int
    {
        $playerRating = $this->ratingModel->findOrCreate($tenantId, $playerId);
        $opponentRating = $opponentId ? $this->ratingModel->findOrCreate($tenantId, $opponentId) : null;

        $before = (int) $playerRating->rating;
        $opponentScore = $opponentRating ? (int) $opponentRating->rating : 1000;
        $actual = match ($result) {
            'win' => 1.0,
            'draw' => 0.5,
            default => 0.0,
        };
        $expected = 1 / (1 + pow(10, ($opponentScore - $before) / 400));
        $after = (int) round($before + (24 * ($actual - $expected)));
        $delta = $after - $before;

        $matchId = $this->matchModel->insert([
            'tenant_id'          => $tenantId,
            'player_id'          => $playerId,
            'opponent_player_id' => $opponentId,
            'branch_id'          => $context['branch_id'] ?? null,
            'facility_id'        => $context['facility_id'] ?? null,
            'tournament_id'      => $context['tournament_id'] ?? null,
            'match_date'         => $context['match_date'] ?? date('Y-m-d H:i:s'),
            'result'             => $result,
            'score'              => $context['score'] ?? null,
            'rating_before'      => $before,
            'rating_after'       => $after,
            'rating_delta'       => $delta,
            'is_mvp'             => !empty($context['is_mvp']) ? 1 : 0,
            'notes'              => $context['notes'] ?? null,
            'created_by'         => $context['created_by'] ?? null,
        ]);

        if (! $matchId) {
            return null;
        }

        $this->ratingModel->update($playerRating->id, [
            'rating'        => $after,
            'games_played'  => ((int) $playerRating->games_played) + 1,
            'wins'          => ((int) $playerRating->wins) + ($result === 'win' ? 1 : 0),
            'losses'        => ((int) $playerRating->losses) + ($result === 'loss' ? 1 : 0),
            'last_match_at' => date('Y-m-d H:i:s'),
        ]);

        $this->syncStatistics($tenantId, $playerId, $after, $result, !empty($context['is_mvp']));
        $this->syncLevel($tenantId, $playerId, $after);
        $this->awardMilestoneBadges($tenantId, $playerId);

        return (int) $matchId;
    }

    public function getRankings(int $tenantId, string $scopeType = 'global', ?int $scopeId = null, ?string $region = null): array
    {
        return $this->ratingModel->getRanking($tenantId, $scopeType, $scopeId, $region);
    }

    private function syncStatistics(int $tenantId, int $playerId, int $rating, string $result, bool $isMvp): void
    {
        $stats = $this->statModel->findOrCreate($playerId, $tenantId);
        $wins = ((int) ($stats->total_wins ?? 0)) + ($result === 'win' ? 1 : 0);
        $losses = ((int) ($stats->total_losses ?? 0)) + ($result === 'loss' ? 1 : 0);
        $total = ((int) ($stats->total_matches ?? 0)) + 1;

        $this->statModel->update($stats->id, [
            'elo_rating' => $rating,
            'ranking_points' => max(0, $rating - 800),
            'total_matches' => $total,
            'total_wins' => $wins,
            'total_losses' => $losses,
            'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0,
            'mvp_count' => ((int) ($stats->mvp_count ?? 0)) + ($isMvp ? 1 : 0),
        ]);

        if ($isMvp) {
            $this->playerModel->where('id', $playerId)->set('mvp_count', 'mvp_count + 1', false)->update();
        }
    }

    private function syncLevel(int $tenantId, int $playerId, int $rating): void
    {
        $level = $this->levelModel->findByRating($tenantId, $rating);
        if (! $level) {
            return;
        }

        $this->playerModel->update($playerId, [
            'rating_score' => $rating,
            'level' => $level->code,
            'level_id' => $level->id,
        ]);
    }

    private function awardMilestoneBadges(int $tenantId, int $playerId): void
    {
        $stats = $this->statModel->getByPlayer($playerId, $tenantId);
        if (! $stats) {
            return;
        }

        if ((int) $stats->total_matches >= 1) {
            $this->badgeModel->award($tenantId, $playerId, 'first_match', 'First Match', [
                'description' => 'Completed the first recorded match.',
                'icon' => 'bi-flag',
            ]);
        }

        if ((int) $stats->total_wins >= 10) {
            $this->badgeModel->award($tenantId, $playerId, 'ten_wins', '10 Wins', [
                'description' => 'Reached 10 recorded wins.',
                'rarity' => 'rare',
                'icon' => 'bi-trophy',
            ]);
        }

        if ((int) ($stats->mvp_count ?? 0) >= 1) {
            $this->badgeModel->award($tenantId, $playerId, 'mvp', 'MVP', [
                'description' => 'Earned MVP in a match.',
                'rarity' => 'epic',
                'icon' => 'bi-star-fill',
            ]);
        }
    }
}
