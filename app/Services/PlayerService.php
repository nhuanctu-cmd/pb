<?php

namespace App\Services;

use App\Models\PlayerModel;
use App\Models\PlayerStatisticModel;
use App\Models\PlayerBadgeModel;
use App\Models\PlayerAchievementModel;
use App\Models\PlayerMatchHistoryModel;
use App\Models\PlayerRatingModel;

class PlayerService
{
    protected PlayerModel $playerModel;
    protected PlayerStatisticModel $playerStatisticModel;
    protected PlayerBadgeModel $badgeModel;
    protected PlayerAchievementModel $achievementModel;
    protected PlayerMatchHistoryModel $matchHistoryModel;
    protected PlayerRatingModel $ratingModel;

    public function __construct()
    {
        $this->playerModel          = new PlayerModel();
        $this->playerStatisticModel = new PlayerStatisticModel();
        $this->badgeModel           = new PlayerBadgeModel();
        $this->achievementModel     = new PlayerAchievementModel();
        $this->matchHistoryModel    = new PlayerMatchHistoryModel();
        $this->ratingModel          = new PlayerRatingModel();
    }

    public function createPlayer(array $data): ?int
    {
        $this->playerModel->db->transStart();

        $playerId = $this->playerModel->insert($data);
        if (!$playerId) {
            $this->playerModel->db->transRollback();
            return null;
        }

        // Create wallet and statistics
        $walletService = new WalletService();
        $walletService->getWallet((int) $playerId, (int) $data['tenant_id']);

        // Create statistics record
        $stats = $this->playerStatisticModel->findOrCreate((int) $playerId, (int) $data['tenant_id']);
        if (!$stats) {
            $this->playerModel->db->transRollback();
            return null;
        }

        $this->ratingModel->findOrCreate((int) $data['tenant_id'], (int) $playerId);

        $this->playerModel->db->transComplete();
        if ($this->playerModel->db->transStatus() === false) {
            $this->playerModel->db->transRollback();
            return null;
        }

        return (int) $playerId;
    }

    public function updatePlayer(int $id, array $data): bool
    {
        return $this->playerModel->update($id, $data);
    }

    public function getPlayerById(int $id, ?int $tenantId = null)
    {
        $builder = $this->playerModel->where('id', $id);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        return $builder->first();
    }

    public function getProfile(int $playerId)
    {
        $player = $this->playerModel->find($playerId);
        if (!$player) return null;

        $membershipModel = new \App\Models\MembershipModel();
        $walletService   = new WalletService();

        $player->active_membership = $membershipModel->getActiveByPlayer($playerId, (int) $player->tenant_id);
        $player->wallet            = $walletService->getWallet($playerId, $player->tenant_id);
        $player->statistics        = $this->playerStatisticModel->getByPlayer($playerId, $player->tenant_id);
        $player->badges            = $this->badgeModel->getByPlayer($playerId);
        $player->achievements      = $this->achievementModel->getByPlayer($playerId);
        $player->match_history     = $this->matchHistoryModel->getByPlayer($playerId, 10);
        $player->rating            = $this->ratingModel->findOrCreate($player->tenant_id, $playerId);

        return $player;
    }

    public function updateRating(int $playerId, float $ratingScore): bool
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('player_rating_profiles') && $db->tableExists('rating_transactions')) {
            // Canonical rating is ledger-owned; direct legacy writes are intentionally blocked.
            return false;
        }
        return $this->playerModel->update($playerId, ['rating_score' => $ratingScore]);
    }

    public function updateStatistics(int $playerId, array $data): bool
    {
        $player = $this->playerModel->find($playerId);
        if (!$player) return false;

        $stats = $this->playerStatisticModel->findOrCreate($playerId, $player->tenant_id);
        if (!$stats) return false;

        // Recalculate win rate
        $totalWins   = $data['total_wins'] ?? $stats->total_wins;
        $totalLosses = $data['total_losses'] ?? $stats->total_losses;
        $total       = $totalWins + $totalLosses;
        $winRate     = $total > 0 ? round(($totalWins / $total) * 100, 2) : 0;

        $data['win_rate']    = $winRate;
        $data['total_matches'] = $total;

        return $this->playerStatisticModel->update($stats->id, $data);
    }

    public function getPlayers(int $tenantId, array $filters = [])
    {
        return $this->playerModel->getByTenant($tenantId, $filters);
    }

    public function getRanking(int $tenantId, string $orderBy = 'rating_score', int $limit = 50)
    {
        return $this->playerModel->getRanking($tenantId, $orderBy, 'DESC', $limit);
    }

    public function getPlayerByUser(int $userId, int $tenantId)
    {
        return $this->playerModel->findPlayerByUser($userId, $tenantId);
    }

    public function getDashboard(int $tenantId): array
    {
        $playerModel = new PlayerModel();
        $statModel = new PlayerStatisticModel();

        $totalPlayers = $playerModel->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults();
        $activePlayers = $playerModel->where('tenant_id', $tenantId)->where('status', 'active')->where('deleted_at', null)->countAllResults();
        $members = model(\App\Models\MembershipModel::class)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('start_date <=', date('Y-m-d'))
            ->where('end_date >=', date('Y-m-d'))
            ->where('deleted_at', null)
            ->countAllResults();

        $topPlayers = $playerModel->getRanking($tenantId, 'elo_rating', 'DESC', 10);
        $topStreaks = $playerModel->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->orderBy('checkin_streak', 'DESC')
            ->orderBy('best_checkin_streak', 'DESC')
            ->limit(8)
            ->findAll();

        $recentAchievements = $this->achievementModel
            ->select('player_achievements.*, players.full_name')
            ->join('players', 'players.id = player_achievements.player_id')
            ->where('player_achievements.tenant_id', $tenantId)
            ->orderBy('player_achievements.achieved_at', 'DESC')
            ->limit(8)
            ->findAll();

        $stats = $statModel->select('SUM(total_matches) as matches, SUM(checkin_count) as checkins, SUM(mvp_count) as mvps')
            ->where('tenant_id', $tenantId)
            ->first();

        return [
            'total_players' => $totalPlayers,
            'active_players' => $activePlayers,
            'active_members' => $members,
            'total_matches' => (int) ($stats->matches ?? 0),
            'total_checkins' => (int) ($stats->checkins ?? 0),
            'total_mvps' => (int) ($stats->mvps ?? 0),
            'top_players' => $topPlayers,
            'top_streaks' => $topStreaks,
            'recent_achievements' => $recentAchievements,
            'regions' => $playerModel->getRegions($tenantId),
        ];
    }

    public function recordCheckIn(int $playerId, int $tenantId): bool
    {
        $player = $this->playerModel->find($playerId);
        if (!$player || (int) $player->tenant_id !== $tenantId) {
            return false;
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lastCheckIn = $player->last_checkin_date ? (string) $player->last_checkin_date : null;

        if ($lastCheckIn === $today) {
            return true;
        }

        $currentStreak = $lastCheckIn === $yesterday ? ((int) $player->checkin_streak) + 1 : 1;
        $bestStreak = max((int) $player->best_checkin_streak, $currentStreak);

        $this->playerModel->skipValidation(true)->update($playerId, [
            'checkin_streak' => $currentStreak,
            'best_checkin_streak' => $bestStreak,
            'last_checkin_date' => $today,
        ]);
        $this->playerModel->skipValidation(false);

        $stats = $this->playerStatisticModel->findOrCreate($playerId, $tenantId);
        if ($stats) {
            $this->playerStatisticModel->skipValidation(true)->update($stats->id, [
                'checkin_count' => ((int) ($stats->checkin_count ?? 0)) + 1,
                'current_streak' => $currentStreak,
                'best_streak' => max((int) ($stats->best_streak ?? 0), $currentStreak),
            ]);
            $this->playerStatisticModel->skipValidation(false);
        }

        $this->awardCheckInMilestones($tenantId, $playerId, $currentStreak);

        return true;
    }

    private function awardCheckInMilestones(int $tenantId, int $playerId, int $streak): void
    {
        if ($streak >= 3) {
            $this->badgeModel->award($tenantId, $playerId, 'streak_3', '3-Day Streak', [
                'description' => 'Checked in 3 days in a row.',
                'icon' => 'bi-lightning-charge',
            ]);
        }

        if ($streak >= 7) {
            $this->badgeModel->award($tenantId, $playerId, 'streak_7', 'Weekly Streak', [
                'description' => 'Checked in 7 days in a row.',
                'rarity' => 'rare',
                'icon' => 'bi-fire',
            ]);
            $this->achievementModel->award($tenantId, $playerId, 'weekly_streak', 'Weekly Streak', [
                'description' => 'Built a 7-day check-in streak.',
                'points' => 50,
            ]);
        }

        if ($streak >= 30) {
            $this->badgeModel->award($tenantId, $playerId, 'streak_30', 'Monthly Streak', [
                'description' => 'Checked in 30 days in a row.',
                'rarity' => 'legendary',
                'icon' => 'bi-gem',
            ]);
            $this->achievementModel->award($tenantId, $playerId, 'monthly_streak', 'Monthly Streak', [
                'description' => 'Built a 30-day check-in streak.',
                'points' => 250,
            ]);
        }
    }
}
