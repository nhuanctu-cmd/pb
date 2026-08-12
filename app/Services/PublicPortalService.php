<?php

namespace App\Services;

use Config\Database;

/** Aggregates public, privacy-safe data for the national portal. */
class PublicPortalService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function home(?int $tenantId = null, string $discipline = 'singles'): array
    {
        $tenantId = $tenantId ?: (int) (current_tenant_id() ?: 1);
        $discipline = in_array($discipline, ['singles', 'doubles', 'mixed_doubles'], true) ? $discipline : 'singles';

        return [
            'stats' => $this->stats($tenantId),
            'venue_overview' => $this->venueOverview($tenantId),
            'top_rankings' => $this->topRankings($tenantId, $discipline),
            'ranking_discipline' => $discipline,
            'top_movers' => $this->topMovers($tenantId),
            'live_events' => $this->liveEvents($tenantId),
            'upcoming_events' => $this->upcomingEvents($tenantId),
            'featured_players' => $this->featuredPlayers($tenantId),
            'province_ranking' => $this->provinceRanking($tenantId),
            'top_clubs' => $this->topClubs($tenantId),
            'latest_results' => $this->latestResults($tenantId),
            'last_updated' => date('Y-m-d H:i:s'),
        ];
    }

    public function search(string $query, ?int $tenantId = null): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) return ['players' => [], 'clubs' => [], 'tournaments' => []];

        $tenantId = $tenantId ?: (int) (current_tenant_id() ?: 1);
        $like = '%' . $this->db->escapeLikeString($query) . '%';
        $players = $clubs = $tournaments = [];

        try {
            if ($this->db->tableExists('players')) {
                if ($this->db->tableExists('player_rating_profiles') && $this->db->tableExists('player_competitive_profiles')) {
                    $players = $this->db->query("SELECT p.id, p.full_name, p.player_code, p.region, p.avatar, cp.national_player_id, r.rating_value AS rating, b.code AS skill_band, r.reliability_score AS reliability FROM players p LEFT JOIN player_competitive_profiles cp ON cp.player_id = p.id AND cp.deleted_at IS NULL LEFT JOIN rating_disciplines d ON d.code = 'singles' LEFT JOIN player_rating_profiles r ON r.player_id = p.id AND r.tenant_id = p.tenant_id AND r.discipline_id = d.id LEFT JOIN skill_level_bands b ON b.id = r.skill_band_id WHERE p.tenant_id = ? AND p.status = 'active' AND p.deleted_at IS NULL AND (p.full_name LIKE ? ESCAPE '!' OR p.player_code LIKE ? ESCAPE '!' OR cp.national_player_id LIKE ? ESCAPE '!') ORDER BY r.rating_value DESC, p.full_name ASC LIMIT 6", [$tenantId, $like, $like, $like])->getResult();
                } else {
                    $players = $this->db->query("SELECT id, full_name, player_code, region, rating_score, avatar FROM players WHERE tenant_id = ? AND status = 'active' AND deleted_at IS NULL AND (full_name LIKE ? ESCAPE '!' OR player_code LIKE ? ESCAPE '!') ORDER BY full_name ASC LIMIT 6", [$tenantId, $like, $like])->getResult();
                }
            }
        } catch (\Throwable $e) { $players = []; }

        try {
            if ($this->db->tableExists('platform_clubs')) {
                $clubs = $this->db->table('platform_clubs')
                    ->select('id, name, slug, province, logo_url, verification_status')
                    ->where('status', 'active')->like('name', $query)
                    ->orderBy('name', 'ASC')->limit(4)->get()->getResult();
            } elseif ($this->db->tableExists('clubs')) {
                $clubs = $this->db->table('clubs')
                    ->select('id, name_vi AS name, name_vi AS slug, NULL AS province, logo AS logo_url, status')
                    ->where('tenant_id', $tenantId)->where('status', 'active')->where('deleted_at', null)
                    ->like('name_vi', $query)->orderBy('name_vi', 'ASC')->limit(4)->get()->getResult();
            }
        } catch (\Throwable $e) { $clubs = []; }

        try {
            if ($this->db->tableExists('tournaments')) {
                $tournaments = $this->db->table('tournaments')
                    ->select('id, name_vi AS name, slug_vi AS slug, start_date, verification_level')
                    ->where('tenant_id', $tenantId)->where('deleted_at', null)
                    ->groupStart()->like('name_vi', $query)->orLike('name_en', $query)->groupEnd()
                    ->orderBy('start_date', 'DESC')->limit(4)->get()->getResult();
            }
        } catch (\Throwable $e) { $tournaments = []; }

        return ['players' => $players, 'clubs' => $clubs, 'tournaments' => $tournaments];
    }

    public function topRankingsForPublic(int $tenantId, string $discipline = 'singles'): array
    {
        return $this->topRankings($tenantId, $discipline);
    }

    /** Build one privacy-safe athlete page from canonical rating and public activity. */
    public function playerProfile(string $identifier, int $tenantId): ?array
    {
        try {
            $query = $this->db->table('players p')
                ->select('p.*, cp.national_player_id, cp.display_name, cp.slug, cp.avatar_url, cp.club_id, cp.privacy_level, cp.status AS verification_status, cp.verified_at')
                ->join('player_competitive_profiles cp', 'cp.player_id = p.id AND cp.deleted_at IS NULL', 'left')
                ->where('p.tenant_id', $tenantId)->where('p.status', 'active')->where('p.deleted_at', null);
            $query->groupStart()->where('p.player_code', $identifier)->orWhere('cp.national_player_id', $identifier)->orWhere('cp.slug', $identifier)->groupEnd();
            $player = $query->get()->getRow();
            if (! $player || (($player->privacy_level ?? 'public') === 'private')) return null;

            $ratings = [];
            if ($this->db->tableExists('player_rating_profiles')) {
                $ratings = $this->db->table('player_rating_profiles r')
            ->select('r.*, d.code AS discipline, d.name AS discipline_name, b.code AS skill_band')
                    ->join('rating_disciplines d', 'd.id = r.discipline_id', 'left')
                    ->join('skill_level_bands b', 'b.id = r.skill_band_id', 'left')
                    ->where('r.tenant_id', $tenantId)->where('r.player_id', $player->id)
                    ->whereIn('r.status', ['provisional', 'established', 'inactive', 'under_review'])
                    ->orderBy('r.rating_value', 'DESC')->get()->getResult();
            }

            $history = [];
            if ($this->db->tableExists('rating_transactions')) {
                $history = $this->db->table('rating_transactions rt')
                    ->select('rt.*, d.code AS discipline')
                    ->join('rating_disciplines d', 'd.id = rt.discipline_id', 'left')
                    ->where('rt.tenant_id', $tenantId)->where('rt.player_id', $player->id)->where('rt.status', 'applied')
                    ->whereIn('rt.transaction_type', ['impact', 'replacement', 'adjustment', 'seed'])
                    ->orderBy('rt.created_at', 'DESC')->limit(30)->get()->getResult();
            }

            $matches = $this->publicPlayerMatches((int) $player->id, $tenantId);
            $posts = [];
            if ($this->db->tableExists('community_posts')) {
                $posts = $this->db->table('community_posts cp')
                    ->select('cp.id, cp.type, cp.title, cp.body, cp.created_at')
                    ->where('cp.tenant_id', $tenantId)->where('cp.player_id', $player->id)
                    ->where('cp.status', 'published')->where('cp.deleted_at', null)
                    ->orderBy('cp.created_at', 'DESC')->limit(6)->get()->getResult();
            }

            $wins = count(array_filter($matches, static fn ($row) => ($row->result ?? '') === 'won'));
            $losses = count(array_filter($matches, static fn ($row) => ($row->result ?? '') === 'lost'));
            $primaryRating = $ratings[0] ?? null;
            return [
                'player' => $player,
                'ratings' => $ratings,
                'ratingHistory' => $history,
                'matches' => $matches,
                'posts' => $posts,
                'stats' => ['matches' => count($matches), 'wins' => $wins, 'losses' => $losses, 'winRate' => count($matches) ? round($wins / count($matches) * 100) : 0, 'rating' => $primaryRating?->rating_value ?? $player->rating_score ?? null, 'reliability' => $primaryRating?->reliability_score ?? 0],
                'clubName' => $this->publicClubName((int) ($player->club_id ?? 0), $tenantId),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    public function publicArticle(int $postId, int $tenantId): ?array
    {
        if (! $this->db->tableExists('community_posts')) return null;

        $post = $this->db->table('community_posts p')
            ->select('p.*, pl.full_name AS player_name, pl.player_code')
            ->join('players pl', 'pl.id = p.player_id', 'left')
            ->where('p.id', $postId)->where('p.tenant_id', $tenantId)->where('p.status', 'published')
            ->get()->getRow();
        if (! $post) return null;

        return ['post' => $post];
    }

    private function publicPlayerMatches(int $playerId, int $tenantId): array
    {
        try {
            if ($this->db->tableExists('player_match_history')) {
                $legacy = $this->db->table('player_match_history h')
                    ->select('h.match_date, h.result, h.score, h.rating_before, h.rating_after, h.rating_delta, h.is_mvp, o.full_name AS opponent_name, t.name_vi AS tournament_name')
                    ->join('players o', 'o.id = h.opponent_player_id', 'left')->join('tournaments t', 't.id = h.tournament_id', 'left')
                    ->where('h.tenant_id', $tenantId)->where('h.player_id', $playerId)->orderBy('h.match_date', 'DESC')->limit(20)->get()->getResult();
                if ($legacy) return $legacy;
            }
            if (! $this->db->tableExists('matches') || ! $this->db->tableExists('match_participants')) return [];
            return $this->db->query("SELECT m.completed_at AS match_date, mp.result, mp.score, o.full_name AS opponent_name, t.name_vi AS tournament_name, m.source_type
                FROM matches m JOIN match_participants mp ON mp.match_id = m.id AND mp.player_id = ?
                LEFT JOIN match_participants op ON op.match_id = m.id AND op.side <> mp.side
                LEFT JOIN players o ON o.id = op.player_id LEFT JOIN tournaments t ON t.id = m.source_id AND m.source_type = 'tournament'
                WHERE m.tenant_id = ? AND m.status = 'official' AND m.verification_status IN ('verified','official')
                ORDER BY m.completed_at DESC LIMIT 20", [$playerId, $tenantId])->getResult();
        } catch (\Throwable) { return []; }
    }

    protected function stats(int $tenantId): array
    {
        $clubTable = $this->db->tableExists('platform_clubs') ? 'platform_clubs' : 'clubs';
        $clubConditions = $clubTable === 'platform_clubs' ? ['status' => 'active'] : ['status' => 'active', 'deleted_at' => null];
        $ratedPlayers = $this->db->tableExists('player_rating_profiles') ? $this->countCanonicalPlayers($tenantId) : 0;
        $officialMatches = $this->db->tableExists('matches') ? $this->count('matches', $tenantId, ['status' => 'official', 'verification_status' => 'official']) : $this->count('tournament_matches', $tenantId, []);
        return [
            ['key' => 'players', 'value' => $ratedPlayers ?: $this->count('players', $tenantId, ['status' => 'active', 'deleted_at' => null]), 'label' => 'Vận động viên có Rating'],
            ['key' => 'clubs', 'value' => $this->count($clubTable, $tenantId, $clubConditions), 'label' => 'CLB'],
            ['key' => 'tournaments', 'value' => $this->count('tournaments', $tenantId, ['deleted_at' => null, 'status !=' => 'draft']), 'label' => 'Giải đấu'],
            ['key' => 'matches', 'value' => $officialMatches, 'label' => 'Trận đấu chính thức'],
            ['key' => 'provinces', 'value' => $this->distinctCount('players', 'region', $tenantId), 'label' => 'Tỉnh / thành'],
        ];
    }

    /** Public, privacy-safe venue coverage summary for the portal home. */
    protected function venueOverview(int $tenantId): array
    {
        return [
            'facilities' => $this->count('facilities', $tenantId, ['deleted_at' => null]),
            'branches' => $this->count('branches', $tenantId, ['deleted_at' => null]),
            'courts' => $this->count('courts', $tenantId, ['deleted_at' => null]),
            'clubs' => $this->count('platform_clubs', $tenantId, ['status' => 'active']) ?: $this->count('clubs', $tenantId, ['status' => 'active', 'deleted_at' => null]),
        ];
    }

    protected function topRankings(int $tenantId, string $discipline = 'singles'): array
    {
        $canonical = $this->canonicalRankings($tenantId, $discipline);
        if ($canonical) return $canonical;
        try {
            $rows = service('rankingNetworkService')->leaderboard('national-pickleball', $tenantId, 10);
            if (! empty($rows)) {
                return array_map(fn ($row, $index) => $this->rankingRow($row, $index + 1, $tenantId), $rows, array_keys($rows));
            }
        } catch (\Throwable $e) { /* optional national ledger */ }

        try {
            if ($this->db->tableExists('player_statistics')) {
                $rows = $this->db->table('players p')
                    ->select('p.id AS player_id, p.full_name, p.player_code, p.region, p.avatar, ps.ranking_points AS points, ps.elo_rating AS rating, ps.total_matches AS match_count')
                    ->join('player_statistics ps', 'ps.player_id = p.id AND ps.tenant_id = p.tenant_id', 'left')
                    ->where('p.tenant_id', $tenantId)->where('p.status', 'active')->where('p.deleted_at', null)
                    ->orderBy('ps.ranking_points', 'DESC')->orderBy('p.rating_score', 'DESC')->limit(10)->get()->getResult();
                return array_map(fn ($row, $index) => $this->rankingRow($row, $index + 1, $tenantId), $rows, array_keys($rows));
            }
        } catch (\Throwable $e) { /* explicit empty state below */ }

        return [];
    }

    protected function rankingRow(object $row, int $rank, int $tenantId): array
    {
        return [
            'rank' => $rank,
            'player_id' => (int) ($row->player_id ?? 0),
            'name' => (string) ($row->full_name ?? 'VĐV chưa xác định'),
            'player_code' => (string) ($row->player_code ?? ''),
            'province' => (string) ($row->region ?? '—'),
            'club' => '—',
            'rating' => $this->playerRating((int) ($row->player_id ?? 0), $tenantId, $row->rating ?? null),
            'points' => (float) ($row->points ?? 0),
            'discipline' => 'singles',
            'skill_band' => (string) ($row->skill_band ?? '—'),
            'reliability' => (float) ($row->reliability ?? 0),
            'status' => (string) ($row->rating_status ?? 'legacy'),
            'match_count' => (int) ($row->match_count ?? 0),
            'verified_match_count' => 0,
            'national_player_id' => (string) ($row->national_player_id ?? ''),
            'trend' => null,
            'avatar' => (string) ($row->avatar ?? ''),
        ];
    }

    /** Canonical public leaderboard. Legacy values are used only when no V1 profile exists. */
    protected function canonicalRankings(int $tenantId, string $discipline): array
    {
        try {
            if (! $this->db->tableExists('player_rating_profiles')) return [];
            $rows = $this->db->table('player_rating_profiles r')
                ->select('r.player_id, r.rating_value, r.reliability_score, r.status AS rating_status, r.rated_match_count, r.verified_match_count, r.highest_rating, r.last_rated_match_at, p.full_name, p.player_code, p.region, p.avatar, b.code AS skill_band, cp.national_player_id, cp.club_id')
                ->join('players p', 'p.id = r.player_id AND p.tenant_id = r.tenant_id AND p.deleted_at IS NULL', 'inner')
                ->join('skill_level_bands b', 'b.id = r.skill_band_id', 'left')
                ->join('player_competitive_profiles cp', 'cp.player_id = r.player_id AND cp.deleted_at IS NULL', 'left')
                ->where('r.tenant_id', $tenantId)->where('r.discipline_id', $this->disciplineId($discipline))
                ->whereIn('r.status', ['provisional', 'established', 'inactive', 'under_review'])
                ->where('p.status', 'active')->where('r.rating_value IS NOT NULL')
                ->orderBy('r.rating_value', 'DESC')->orderBy('r.reliability_score', 'DESC')->orderBy('r.rated_match_count', 'DESC')->limit(20)->get()->getResult();
            $output = [];
            foreach ($rows as $index => $row) {
                $club = $this->publicClubName((int) ($row->club_id ?? 0), $tenantId);
                $output[] = ['rank' => $index + 1, 'player_id' => (int) $row->player_id, 'name' => (string) $row->full_name, 'player_code' => (string) ($row->player_code ?? ''), 'national_player_id' => (string) ($row->national_player_id ?? ''), 'province' => (string) ($row->region ?: '—'), 'club' => $club, 'discipline' => $discipline, 'rating' => (float) $row->rating_value, 'skill_band' => (string) ($row->skill_band ?: 'NR'), 'reliability' => (float) $row->reliability_score, 'status' => (string) $row->rating_status, 'match_count' => (int) $row->rated_match_count, 'verified_match_count' => (int) $row->verified_match_count, 'career_high' => (float) ($row->highest_rating ?? $row->rating_value), 'last_match_at' => $row->last_rated_match_at, 'points' => $this->rankingPoints($tenantId, (int) $row->player_id), 'trend' => null, 'avatar' => (string) ($row->avatar ?? '')];
            }
            return $output;
        } catch (\Throwable $e) { return []; }
    }

    protected function disciplineId(string $discipline): int
    {
        $row = $this->db->table('rating_disciplines')->where('code', $discipline)->where('active', 1)->get()->getRow();
        return (int) ($row->id ?? 0);
    }

    protected function publicClubName(int $clubId, int $tenantId): string
    {
        if ($clubId <= 0) return '—';
        try {
            if ($this->db->tableExists('platform_clubs')) return (string) ($this->db->table('platform_clubs')->select('name')->where('id', $clubId)->whereIn('status', ['active', 'verified'])->get()->getRow()->name ?? '—');
            if ($this->db->tableExists('clubs')) return (string) ($this->db->table('clubs')->select('name_vi')->where('id', $clubId)->where('tenant_id', $tenantId)->where('deleted_at', null)->get()->getRow()->name_vi ?? '—');
        } catch (\Throwable $e) { return '—'; }
        return '—';
    }

    protected function rankingPoints(int $tenantId, int $playerId): float
    {
        try {
            if (! $this->db->tableExists('ranking_snapshots')) return 0;
            $row = $this->db->table('ranking_snapshots')->select('points')->where('tenant_id', $tenantId)->where('player_id', $playerId)->orderBy('snapshot_date', 'DESC')->get()->getRow();
            return (float) ($row->points ?? 0);
        } catch (\Throwable $e) { return 0; }
    }

    protected function playerRating(int $playerId, int $tenantId, $fallback = null): ?float
    {
        try {
            if ($this->db->tableExists('player_ratings')) {
                $builder = $this->db->table('player_ratings')->where('player_id', $playerId)->where('tenant_id', $tenantId);
                if ($this->db->fieldExists('scope_type', 'player_ratings')) $builder->where('scope_type', 'global');
                $row = $builder->orderBy('updated_at', 'DESC')->get()->getRow();
                if ($row && isset($row->rating)) return (float) $row->rating;
            }
        } catch (\Throwable $e) { /* fallback is still public */ }
        return $fallback !== null ? (float) $fallback : null;
    }

    protected function topMovers(int $tenantId): array
    {
        try {
            if (! $this->db->tableExists('ranking_snapshots')) return [];
            $latest = $this->db->table('ranking_snapshots')->where('tenant_id', $tenantId)->orderBy('snapshot_date', 'DESC')->get()->getRow();
            if (! $latest) return [];
            $previous = $this->db->table('ranking_snapshots')->where('tenant_id', $tenantId)->where('snapshot_date <', $latest->snapshot_date)->orderBy('snapshot_date', 'DESC')->get()->getRow();
            if (! $previous) return [];
            $rows = $this->db->query(
                "SELECT latest.player_id, p.full_name, latest.rank_position AS current_rank, previous.rank_position AS previous_rank,
                        (previous.rank_position - latest.rank_position) AS movement
                 FROM ranking_snapshots latest
                 JOIN ranking_snapshots previous ON previous.player_id = latest.player_id AND previous.tenant_id = latest.tenant_id
                 JOIN players p ON p.id = latest.player_id AND p.deleted_at IS NULL
                 WHERE latest.tenant_id = ? AND latest.snapshot_date = ? AND previous.snapshot_date = ?
                 ORDER BY movement DESC LIMIT 4",
                [$tenantId, $latest->snapshot_date, $previous->snapshot_date]
            )->getResult();
            return array_values(array_filter(array_map(fn ($row) => (array) $row, $rows), fn ($row) => (int) ($row['movement'] ?? 0) > 0));
        } catch (\Throwable $e) { return []; }
    }

    protected function liveEvents(int $tenantId): array
    {
        try { return array_slice(service('liveScoreService')->getPublicBracketData($tenantId)['matches'] ?? [], 0, 4); }
        catch (\Throwable $e) { return []; }
    }

    protected function upcomingEvents(int $tenantId): array
    {
        try {
            if (! $this->db->tableExists('tournaments')) return [];
            return $this->db->table('tournaments')
                ->select('id, name_vi AS name, slug_vi AS slug, start_date, end_date, status, verification_level, branch_id')
                ->where('tenant_id', $tenantId)->where('deleted_at', null)
                ->whereIn('status', ['open', 'closed', 'running'])->where('start_date >=', date('Y-m-d'))
                ->orderBy('start_date', 'ASC')->limit(4)->get()->getResult();
        } catch (\Throwable $e) { return []; }
    }

    protected function featuredPlayers(int $tenantId): array { return array_slice($this->topRankings($tenantId), 0, 3); }

    protected function provinceRanking(int $tenantId): array
    {
        try {
            if (! $this->db->tableExists('players')) return [];
            return $this->db->table('players')->select('region AS province, COUNT(*) AS player_count')
                ->where('tenant_id', $tenantId)->where('status', 'active')->where('deleted_at', null)
                ->where('region IS NOT NULL')->where('region !=', '')->groupBy('region')
                ->orderBy('player_count', 'DESC')->limit(6)->get()->getResult();
        } catch (\Throwable $e) { return []; }
    }

    protected function topClubs(int $tenantId): array
    {
        try {
            if ($this->db->tableExists('platform_clubs')) {
                return $this->db->table('platform_clubs')->select('id, name, slug, province, logo_url, verification_status')
                    ->where('status', 'active')->whereIn('verification_status', ['verified', 'official'])
                    ->orderBy('verification_status', 'DESC')->orderBy('name', 'ASC')->limit(5)->get()->getResult();
            }
            if ($this->db->tableExists('clubs')) {
                return $this->db->table('clubs')->select('id, name_vi AS name, name_vi AS slug, NULL AS province, logo AS logo_url, status AS verification_status')
                    ->where('tenant_id', $tenantId)->where('status', 'active')->where('deleted_at', null)
                    ->orderBy('name_vi', 'ASC')->limit(5)->get()->getResult();
            }
        } catch (\Throwable $e) { return []; }
        return [];
    }

    protected function latestResults(int $tenantId): array
    {
        try {
            if (! $this->db->tableExists('tournaments')) return [];
            return $this->db->table('tournaments')->select('id, name_vi AS name, slug_vi AS slug, end_date, verification_level')
                ->where('tenant_id', $tenantId)->where('deleted_at', null)->where('status', 'completed')
                ->orderBy('end_date', 'DESC')->limit(4)->get()->getResult();
        } catch (\Throwable $e) { return []; }
    }

    protected function count(string $table, int $tenantId, array $conditions): int
    {
        try {
            if (! $this->db->tableExists($table)) return 0;
            $builder = $this->db->table($table);
            if ($this->db->fieldExists('tenant_id', $table)) $builder->where('tenant_id', $tenantId);
            foreach ($conditions as $field => $value) $builder->where($field, $value);
            return (int) $builder->countAllResults();
        } catch (\Throwable $e) { return 0; }
    }

    protected function distinctCount(string $table, string $field, int $tenantId): int
    {
        try {
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists($field, $table)) return 0;
            $row = $this->db->table($table)->select("COUNT(DISTINCT {$field}) AS total", false)
                ->where('tenant_id', $tenantId)->where($field . ' IS NOT NULL')->where($field . ' !=', '')->get()->getRow();
            return (int) ($row->total ?? 0);
        } catch (\Throwable $e) { return 0; }
    }

    protected function countCanonicalPlayers(int $tenantId): int
    {
        try {
            $row = $this->db->table('player_rating_profiles')->select('COUNT(DISTINCT player_id) AS total', false)->where('tenant_id', $tenantId)->where('rating_value IS NOT NULL')->get()->getRow();
            return (int) ($row->total ?? 0);
        } catch (\Throwable $e) { return 0; }
    }
}
