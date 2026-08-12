<?php

namespace App\Services;

use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Models\TournamentRegistrationModel;
use App\Models\TournamentRuleModel;
use App\Models\TournamentSponsorModel;
use Config\Database;

/**
 * Public tournament read model. It deliberately exposes counts and public
 * labels only; private registration contact data never crosses this boundary.
 */
class PublicTournamentService
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function list(int $tenantId, array $filters = []): array
    {
        $model = model(TournamentModel::class);
        $all = $model->getByTenant($tenantId);
        $status = (string) ($filters['status'] ?? 'all');
        $search = trim((string) ($filters['search'] ?? ''));
        $allowedStatuses = ['open', 'running', 'closed', 'completed', 'cancelled'];

        $rows = array_values(array_filter($all, static function (object $tournament) use ($status, $search, $allowedStatuses): bool {
            if ($status !== 'all' && in_array($status, $allowedStatuses, true) && (string) $tournament->status !== $status) {
                return false;
            }
            if ($search !== '' && stripos((string) ($tournament->name_vi ?? ''), $search) === false && stripos((string) ($tournament->name_en ?? ''), $search) === false) {
                return false;
            }
            return true;
        }));

        foreach ($rows as $tournament) {
            $this->attachRegistrationStats($tournament, $tenantId);
        }

        $counts = array_fill_keys(['all', 'open', 'running', 'closed', 'completed'], 0);
        foreach ($all as $tournament) {
            $counts['all']++;
            if (isset($counts[$tournament->status])) $counts[$tournament->status]++;
        }

        return [
            'tournaments' => $rows,
            'counts' => $counts,
            'featured' => $this->featured($all),
            'total' => count($all),
        ];
    }

    public function detail(object $tournament, int $tenantId): array
    {
        $categoryModel = model(TournamentCategoryModel::class);
        $registrationModel = model(TournamentRegistrationModel::class);
        $categories = $categoryModel->where('tenant_id', $tenantId)->getByTournament((int) $tournament->id);
        $registrations = $registrationModel->getByTournament((int) $tournament->id, $tenantId);

        foreach ($categories as $category) {
            $categoryRows = array_values(array_filter($registrations, static fn (object $row): bool => (int) $row->category_id === (int) $category->id));
            $category->registration_count = count($categoryRows);
            $category->confirmed_count = count(array_filter($categoryRows, static fn (object $row): bool => ($row->approval_status ?? '') === 'approved' || ($row->registration_status ?? '') === 'confirmed'));
            $category->pending_count = count(array_filter($categoryRows, static fn (object $row): bool => ($row->approval_status ?? '') === 'pending'));
            $category->waitlist_count = count(array_filter($categoryRows, static fn (object $row): bool => ($row->registration_status ?? '') === 'waitlisted'));
            $category->capacity = (int) ($category->entry_capacity ?: $category->max_teams ?: 0);
            $category->fill_percent = $category->capacity > 0 ? min(100, round(((int) $category->confirmed_count / $category->capacity) * 100)) : 0;
        }

        $matches = $this->matches((int) $tournament->id, $tenantId);
        $matchStatus = array_fill_keys(['scheduled', 'running', 'completed', 'delayed', 'cancelled'], 0);
        foreach ($matches as $match) {
            if (isset($matchStatus[$match->status])) $matchStatus[$match->status]++;
        }

        return [
            'categories' => $categories,
            'rule' => model(TournamentRuleModel::class)->where('tenant_id', $tenantId)->where('tournament_id', $tournament->id)->first(),
            'sponsors' => model(TournamentSponsorModel::class)->where('tenant_id', $tenantId)->getByTournament((int) $tournament->id),
            'registrations' => $registrations,
            'matches' => $matches,
            'match_status' => $matchStatus,
            'teams' => $this->teams($matches, $tenantId),
            'related' => $this->related((int) $tournament->id, $tenantId),
            'registration_total' => count($registrations),
            'confirmed_total' => count(array_filter($registrations, static fn (object $row): bool => ($row->approval_status ?? '') === 'approved' || ($row->registration_status ?? '') === 'confirmed')),
            'live_total' => count(array_filter($matches, static fn (object $row): bool => in_array($row->status, ['called', 'on_court', 'running', 'in_progress'], true))),
        ];
    }

    private function attachRegistrationStats(object $tournament, int $tenantId): void
    {
        $rows = model(TournamentRegistrationModel::class)->getByTournament((int) $tournament->id, $tenantId);
        $tournament->registration_count = count($rows);
        $tournament->confirmed_count = count(array_filter($rows, static fn (object $row): bool => ($row->approval_status ?? '') === 'approved' || ($row->registration_status ?? '') === 'confirmed'));
        $tournament->category_count = model(TournamentCategoryModel::class)->where('tenant_id', $tenantId)->where('tournament_id', $tournament->id)->where('deleted_at', null)->countAllResults();
        $tournament->match_count = $this->db->tableExists('tournament_matches') ? $this->db->table('tournament_matches')->where('tenant_id', $tenantId)->where('tournament_id', $tournament->id)->countAllResults() : 0;
    }

    private function featured(array $tournaments): ?object
    {
        usort($tournaments, static function (object $a, object $b): int {
            $priority = ['running' => 0, 'open' => 1, 'closed' => 2, 'completed' => 3, 'cancelled' => 4];
            return ($priority[$a->status] ?? 9) <=> ($priority[$b->status] ?? 9) ?: strcmp((string) ($a->start_date ?? ''), (string) ($b->start_date ?? ''));
        });
        return $tournaments[0] ?? null;
    }

    private function matches(int $tournamentId, int $tenantId): array
    {
        if (! $this->db->tableExists('tournament_matches')) return [];
        try {
            return $this->db->table('tournament_matches tm')
                ->select('tm.*, cat.name_vi AS category_name, ta.team_name AS team_a_name, tb.team_name AS team_b_name, c.code AS court_code, c.name_vi AS court_name, mr.status AS result_status, mr.winner_side')
                ->join('tournament_categories cat', 'cat.id = tm.category_id AND cat.tenant_id = tm.tenant_id', 'left')
                ->join('teams ta', 'ta.id = tm.team_a_id AND ta.tenant_id = tm.tenant_id', 'left')
                ->join('teams tb', 'tb.id = tm.team_b_id AND tb.tenant_id = tm.tenant_id', 'left')
                ->join('courts c', 'c.id = tm.court_id AND c.tenant_id = tm.tenant_id', 'left')
                ->join('match_results mr', 'mr.match_id = tm.unified_match_id', 'left')
                ->where('tm.tenant_id', $tenantId)->where('tm.tournament_id', $tournamentId)
                ->orderBy('tm.scheduled_date', 'ASC')->orderBy('tm.start_time', 'ASC')->orderBy('tm.match_no', 'ASC')->limit(60)->get()->getResult();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function teams(array $matches, int $tenantId): array
    {
        if (! $this->db->tableExists('teams')) return [];
        $ids = [];
        foreach ($matches as $match) {
            foreach (['team_a_id', 'team_b_id'] as $field) if (! empty($match->{$field})) $ids[] = (int) $match->{$field};
        }
        $ids = array_values(array_unique($ids));
        if (! $ids) return [];
        try {
            return $this->db->table('teams t')->select('t.id, t.team_name, t.rating_avg, t.club_id, pc.name AS platform_club_name')
                ->join('platform_clubs pc', 'pc.id = t.club_id AND pc.status IN (\'active\', \'verified\')', 'left')
                ->where('t.tenant_id', $tenantId)->whereIn('t.id', $ids)->where('t.deleted_at', null)->orderBy('t.team_name', 'ASC')->get()->getResult();
        } catch (\Throwable $e) { return []; }
    }

    private function related(int $tournamentId, int $tenantId): array
    {
        return array_slice(array_values(array_filter(model(TournamentModel::class)->getByTenant($tenantId), static fn (object $row): bool => (int) $row->id !== $tournamentId)), 0, 4);
    }
}
