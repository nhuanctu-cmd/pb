<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

class LiveScoreService
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function getLiveMatches(?int $tenantId = null, ?int $tournamentId = null): array
    {
        if (! $this->db->tableExists('tournament_matches')) {
            return [];
        }

        $builder = $this->db->table('tournament_matches m')->select('m.*');
        if ($tenantId) {
            $builder->where('m.tenant_id', $tenantId);
        }
        if ($tournamentId && $this->fieldExists('tournament_matches', 'tournament_id')) {
            $builder->where('m.tournament_id', $tournamentId);
        }
        if ($this->fieldExists('tournament_matches', 'status')) {
            $builder->whereIn('m.status', ['scheduled', 'pending', 'running', 'in_progress', 'completed']);
        }

        $matches = $builder->orderBy('m.id', 'ASC')->limit(100)->get()->getResult();
        foreach ($matches as $match) {
            $match->scores = $this->getScores((int) $match->id);
            $match->score_text = $this->formatScore($match->scores);
        }

        return $matches;
    }

    public function getTvDisplayData(?int $tenantId = null, ?int $tournamentId = null): array
    {
        $matches = $this->getLiveMatches($tenantId, $tournamentId);
        $live = array_values(array_filter($matches, static fn ($m) => in_array(($m->status ?? ''), ['running', 'in_progress'], true)));
        $next = array_values(array_filter($matches, static fn ($m) => in_array(($m->status ?? 'scheduled'), ['scheduled', 'pending'], true)));

        $config = null;
        if ($this->db->tableExists('live_display_configs')) {
            $builder = $this->db->table('live_display_configs')->where('mode', 'tv')->where('status', 'active');
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
            if ($tournamentId) {
                $builder->where('tournament_id', $tournamentId);
            }
            $config = $builder->orderBy('id', 'DESC')->get()->getRow();
        }

        return [
            'config' => $config,
            'live_matches' => $live,
            'next_matches' => $next,
            'refresh_seconds' => (int) ($config->refresh_seconds ?? 5),
            'show_sponsor' => (bool) ($config->show_sponsor ?? true),
            'show_next_matches' => (bool) ($config->show_next_matches ?? true),
        ];
    }

    public function getPublicBracketData(?int $tenantId = null, ?int $tournamentId = null): array
    {
        return [
            'matches' => $this->getLiveMatches($tenantId, $tournamentId),
            'standings' => $this->getStandings($tenantId, $tournamentId),
        ];
    }

    protected function getScores(int $matchId): array
    {
        if (! $this->db->tableExists('tournament_match_scores')) {
            return [];
        }

        return $this->db->table('tournament_match_scores')
            ->where('match_id', $matchId)
            ->orderBy('set_no', 'ASC')
            ->get()
            ->getResult();
    }

    protected function getStandings(?int $tenantId, ?int $tournamentId): array
    {
        if (! $this->db->tableExists('tournament_group_standings')) {
            return [];
        }

        $builder = $this->db->table('tournament_group_standings')->orderBy('wins', 'DESC')->orderBy('played', 'ASC');
        if ($tenantId) {
            $builder->where('tenant_id', $tenantId);
        }
        if ($tournamentId && $this->fieldExists('tournament_group_standings', 'tournament_id')) {
            $builder->where('tournament_id', $tournamentId);
        }

        return $builder->get()->getResult();
    }

    protected function formatScore(array $scores): string
    {
        if (empty($scores)) {
            return '-';
        }

        return implode(' | ', array_map(static fn ($s): string => (int) $s->team_a_score . '-' . (int) $s->team_b_score, $scores));
    }

    protected function fieldExists(string $table, string $field): bool
    {
        return $this->db->tableExists($table) && $this->db->fieldExists($field, $table);
    }
}
