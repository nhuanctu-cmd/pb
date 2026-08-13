<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DateTime;

class LiveScoreService
{
    protected BaseConnection $db;

    private const TV_SEQUENCE = ['live', 'next', 'call', 'results'];
    private const LIVE_STATUSES = ['on_court', 'running', 'in_progress', 'live', 'playing'];
    private const CALLED_STATUSES = ['called', 'prepared', 'on_deck', 'calling'];
    private const NEXT_STATUSES = ['scheduled', 'pending', 'delayed', 'upcoming', 'queue'];
    private const RESULT_STATUSES = ['completed', 'finished', 'walkover', 'defaulted'];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function getLiveMatches(?int $tenantId = null, ?int $tournamentId = null, array $options = []): array
    {
        if (! $tenantId || ! $this->db->tableExists('tournament_matches')) {
            return [];
        }

        $builder = $this->db->table('tournament_matches m')->select('m.*');
        if ($tenantId) {
            $builder->where('m.tenant_id', $tenantId);
        }
        if ($tournamentId && $this->fieldExists('tournament_matches', 'tournament_id')) {
            $builder->where('m.tournament_id', $tournamentId);
        }
        if (! empty($options['date']) && $this->fieldExists('tournament_matches', 'scheduled_date')) {
            $builder->where('m.scheduled_date', $options['date']);
        }
        if (! empty($options['branch_id']) && $this->fieldExists('tournament_matches', 'branch_id')) {
            $builder->where('m.branch_id', (int) $options['branch_id']);
        }
        if ($this->fieldExists('tournament_matches', 'status')) {
            $builder->whereIn('m.status', array_unique(array_merge(self::LIVE_STATUSES, self::CALLED_STATUSES, self::NEXT_STATUSES, self::RESULT_STATUSES)));
        }

        if ($this->db->tableExists('teams')) {
            $builder->select('m.*, ta.team_name AS team_a_name, tb.team_name AS team_b_name, co.name_vi AS court_name, c.name_vi AS category_name')
                ->join('teams ta', 'ta.id = m.team_a_id AND ta.tenant_id = m.tenant_id', 'left')
                ->join('teams tb', 'tb.id = m.team_b_id AND tb.tenant_id = m.tenant_id', 'left')
                ->join('courts co', 'co.id = m.court_id AND co.tenant_id = m.tenant_id', 'left')
                ->join('tournament_categories c', 'c.id = m.category_id AND c.tenant_id = m.tenant_id', 'left');
        }
        $matches = $builder->orderBy('m.scheduled_date', 'ASC')
            ->orderBy('m.start_time', 'ASC')
            ->orderBy('m.id', 'ASC')
            ->limit(100)
            ->get()
            ->getResult();
        foreach ($matches as $match) {
            $match->scores = $this->getScores((int) $match->id, $tenantId);
            $match->score_text = $this->formatScore($match->scores);
            $match->team_a_label = $match->team_a_name ?: ($match->team_a_id ? 'Team #' . $match->team_a_id : 'BYE');
            $match->team_b_label = $match->team_b_name ?: ($match->team_b_id ? 'Team #' . $match->team_b_id : 'BYE');
        }

        return $matches;
    }

    public function getTvDisplayData(?int $tenantId = null, ?int $tournamentId = null, array $options = []): array
    {
        $date = $this->normalizeDate($options['date'] ?? null);
        $matches = $this->getLiveMatches($tenantId, $tournamentId, [
            'date' => $date,
            'branch_id' => $options['branch_id'] ?? null,
        ]);
        $isMatchStatus = static function (string $status, array $statuses): bool {
            return in_array($status, $statuses, true);
        };
        $live = array_values(array_filter($matches, static function (object $m) use ($isMatchStatus): bool {
            return $isMatchStatus((string) ($m->status ?? ''), self::LIVE_STATUSES);
        }));
        $called = array_values(array_filter($matches, static function (object $m) use ($isMatchStatus): bool {
            return $isMatchStatus((string) ($m->status ?? ''), self::CALLED_STATUSES);
        }));
        $next = array_values(array_filter($matches, static function (object $m) use ($isMatchStatus): bool {
            return $isMatchStatus((string) ($m->status ?? 'scheduled'), self::NEXT_STATUSES);
        }));
        $results = array_values(array_filter($matches, static function (object $m) use ($isMatchStatus): bool {
            return $isMatchStatus((string) ($m->status ?? ''), self::RESULT_STATUSES);
        }));
        $tournament = null;
        if ($tournamentId && $this->db->tableExists('tournaments')) {
            $tournament = $this->db->table('tournaments')->where('id', $tournamentId)->where('tenant_id', $tenantId)->where('deleted_at', null)->get()->getRow();
        }

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

        $sequence = $this->normalizeTvSequence($options['sequence'] ?? null);
        $refreshSeconds = (int) ($options['refresh_seconds'] ?? 5);
        if ($refreshSeconds <= 0) {
            $refreshSeconds = 5;
        }
        if ($config && $config->refresh_seconds > 0) {
            $refreshSeconds = (int) $config->refresh_seconds;
        }
        if (isset($options['refresh_seconds']) && is_numeric($options['refresh_seconds']) && (int) $options['refresh_seconds'] > 0) {
            $refreshSeconds = (int) $options['refresh_seconds'];
        }

        $sequence = $this->normalizeTvSequence($options['sequence'] ?? null);
        $refreshSeconds = (int) ($options['refresh_seconds'] ?? 10);
        if ($refreshSeconds <= 0) {
            $refreshSeconds = 10;
        }
        if ($refreshSeconds < 5) {
            $refreshSeconds = 5;
        }

        if ($refreshSeconds > 120) {
            $refreshSeconds = 120;
        }

        if ($config && is_numeric((string) ($config->refresh_seconds ?? null)) && (int) $config->refresh_seconds > 0) {
            $refreshSeconds = (int) $config->refresh_seconds;
            if ($refreshSeconds < 5) {
                $refreshSeconds = 5;
            }
            if ($refreshSeconds > 120) {
                $refreshSeconds = 120;
            }
        }

        $sequence = $this->normalizeTvSequence($options['sequence'] ?? null);
        $sequenceRequestedByUser = $sequence;
        if (empty($sequenceRequestedByUser)) {
            $sequenceRequestedByUser = self::TV_SEQUENCE;
        }
        if (isset($options['sequence']) && empty((string) $options['sequence'])) {
            $sequenceRequestedByUser = self::TV_SEQUENCE;
        }

        return [
            'config' => $config,
            'tournament' => $tournament,
            'live_matches' => $live,
            'called_matches' => $called,
            'next_matches' => $next,
            'result_matches' => array_slice(array_reverse($results), 0, 6),
            'slides' => $sequenceRequestedByUser,
            'sequence' => $sequenceRequestedByUser,
            'refresh_seconds' => $refreshSeconds,
            'date' => $date,
            'branch_id' => $options['branch_id'] ?? null,
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

    protected function getScores(int $matchId, int $tenantId): array
    {
        if (! $this->db->tableExists('tournament_match_scores')) {
            return [];
        }

        return $this->db->table('tournament_match_scores')
            ->where('match_id', $matchId)
            ->where('tenant_id', $tenantId)
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

    private function normalizeTvSequence($sequence): array
    {
        $allowed = self::TV_SEQUENCE;
        if (is_string($sequence)) {
            $sequence = preg_split('/\s*,\s*/', trim($sequence), -1, PREG_SPLIT_NO_EMPTY);
        }

        if (! is_array($sequence) || empty($sequence)) {
            return $allowed;
        }

        $normalized = [];
        foreach ($sequence as $item) {
            $value = strtolower(trim((string) $item));
            if (! in_array($value, $allowed, true) || in_array($value, $normalized, true)) {
                continue;
            }
            $normalized[] = $value;
        }

        return $normalized ?: $allowed;
    }

    public function tvQueryDefaults(array $request): array
    {
        return [
            'sequence' => $request['sequence'] ?? null,
            'refresh_seconds' => is_numeric($request['refresh'] ?? null) ? (int) $request['refresh'] : null,
            'branch_id' => is_numeric($request['branch_id'] ?? null) ? (int) $request['branch_id'] : null,
            'date' => $this->normalizeDate($request['date'] ?? null),
        ];
    }

    private function normalizeDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $parsed = DateTime::createFromFormat('Y-m-d', (string) $value);
        if (! $parsed || $parsed->format('Y-m-d') !== (string) $value) {
            $timestamp = strtotime((string) $value);
            if (! $timestamp) {
                return null;
            }
            $parsed = (new DateTime())->setTimestamp($timestamp);
        }

        return $parsed->format('Y-m-d');
    }
}
