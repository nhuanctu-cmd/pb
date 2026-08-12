<?php

namespace App\Services;

use App\Models\PlayerBadgeModel;
use App\Models\PlayerLevelModel;
use App\Models\PlayerMatchHistoryModel;
use App\Models\PlayerModel;
use App\Models\PlayerRatingModel;
use App\Models\PlayerStatisticModel;
use App\Services\UnifiedMatchService;
use Config\Database;

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
        if ($this->canonicalReady()) {
            if (! $opponentId || ! in_array($result, ['win', 'loss', 'draw'], true)) return null;
            $canonical = new UnifiedMatchService();
            $created = $canonical->create([
                'tenant_id' => $tenantId,
                'source_type' => 'friendly',
                'discipline' => 'pickleball',
                'venue_id' => $context['facility_id'] ?? null,
                'court_id' => $context['court_id'] ?? null,
                'scheduled_at' => $context['match_date'] ?? null,
                'metadata' => ['created_from' => 'player_match_history_compatibility_form'],
                'participants' => [
                    ['player_id' => $playerId, 'side' => 1],
                    ['player_id' => $opponentId, 'side' => 2],
                ],
            ], $tenantId, $context['created_by'] ?? null);
            if (empty($created['success'])) return null;
            $matchId = (int) ($created['match']['match']->id ?? 0);
            if (! $matchId) return null;
            $winnerSide = $result === 'win' ? 1 : ($result === 'loss' ? 2 : null);
            $score = trim((string) ($context['score'] ?? ''));
            $games = [];
            if (preg_match('/^(\d+)\s*[-:]\s*(\d+)$/', $score, $matches)) {
                $games[] = ['game_no' => 1, 'side_a_score' => (int) $matches[1], 'side_b_score' => (int) $matches[2]];
            }
            $submitted = $canonical->submitResult($matchId, ['winner_side' => $winnerSide, 'games' => $games, 'notes' => $context['notes'] ?? null], $tenantId, $context['created_by'] ?? null);
            if (empty($submitted['success'])) return null;
            $confirmed = $canonical->confirmResult($matchId, $tenantId, $context['created_by'] ?? null);
            if (empty($confirmed['success'])) return null;
            $official = $canonical->publishOfficial($matchId, $tenantId, $context['created_by'] ?? null);
            return ! empty($official['success']) ? $matchId : null;
        }

        // Compatibility path for installations that have not migrated to Rating Engine V1.
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
        if ($this->canonicalReady()) {
            $db = Database::connect();
            $provider = $db->table('rating_providers')->where('code', 'internal-v1')->where('status', 'active')->get()->getRow();
            $discipline = $db->table('rating_disciplines')->where('code', 'singles')->where('active', 1)->get()->getRow();
            if ($provider && $discipline) {
                $builder = $db->table('player_rating_profiles r')
                    ->select('r.*, r.rating_value AS rating, r.rated_match_count AS games_played, 0 AS wins, 0 AS losses, "global" AS scope_type, NULL AS scope_id, p.full_name, p.player_code, p.level, p.region, p.home_branch_id', false)
                    ->join('players p', 'p.id = r.player_id', 'inner')
                    ->where('r.tenant_id', $tenantId)
                    ->where('r.provider_id', $provider->id)
                    ->where('r.discipline_id', $discipline->id)
                    ->where('p.deleted_at', null);
                if ($region) $builder->where('p.region', $region);
                return $builder->orderBy('r.rating_value', 'DESC')->orderBy('r.reliability_score', 'DESC')->limit(100)->get()->getResult();
            }
        }
        return $this->ratingModel->getRanking($tenantId, $scopeType, $scopeId, $region);
    }

    private function canonicalReady(): bool
    {
        $db = Database::connect();
        return $db->tableExists('player_rating_profiles') && $db->tableExists('rating_transactions');
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
