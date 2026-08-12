<?php

namespace App\Services;

use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Models\TournamentSponsorModel;
use CodeIgniter\Database\BaseConnection;

class TournamentPrintCenterService
{
    public const DOCUMENT_TYPES = [
        'badges', 'name_tags', 'team_badges', 'staff_badges', 'court_signs',
        'match_cards', 'schedule', 'bracket', 'certificates', 'results',
        'participants', 'checkin',
    ];

    private BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function getTournaments(int $tenantId): array
    {
        return model(TournamentModel::class)->getByTenant($tenantId);
    }

    public function getTournamentsPaginated(int $tenantId, int $page = 1, int $perPage = 12, string $search = '', string $status = ''): array
    {
        $page = max(1, $page);
        $perPage = max(6, min(48, $perPage));
        $search = trim($search);
        $allowedStatuses = ['draft', 'open', 'closed', 'running', 'completed', 'cancelled'];
        $status = in_array($status, $allowedStatuses, true) ? $status : '';

        $apply = function ($builder) use ($tenantId, $search, $status) {
            $builder->where('t.tenant_id', $tenantId)->where('t.deleted_at', null);
            if ($search !== '') $builder->groupStart()->like('t.name_vi', $search)->orLike('t.name_en', $search)->orLike('t.slug_vi', $search)->groupEnd();
            if ($status !== '') $builder->where('t.status', $status);
            return $builder;
        };
        $countQuery = $apply($this->db->table('tournaments t'));
        $total = (int) $countQuery->countAllResults();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $items = $apply($this->db->table('tournaments t')->select('t.*, b.name AS branch_name')->join('branches b', 'b.id = t.branch_id AND b.tenant_id = t.tenant_id', 'left'))
            ->orderBy('t.start_date', 'DESC')->orderBy('t.id', 'DESC')->get($perPage, ($page - 1) * $perPage)->getResult();

        return compact('items', 'total', 'page', 'perPage', 'pages', 'search', 'status');
    }

    public function overview(int $tournamentId, int $tenantId): ?array
    {
        $tournament = model(TournamentModel::class)->findForTenant($tournamentId, $tenantId);
        if (! $tournament) return null;
        $registrationScope = $this->db->table('tournament_registrations')
            ->where('tournament_id', $tournamentId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null);
        $matchScope = $this->db->table('tournament_matches')
            ->where('tournament_id', $tournamentId)
            ->where('tenant_id', $tenantId);
        return [
            'tournament' => $tournament,
            'categories' => (int) $this->db->table('tournament_categories')->where('tournament_id', $tournamentId)->where('tenant_id', $tenantId)->where('deleted_at', null)->countAllResults(),
            'registrations' => (int) $registrationScope->countAllResults(),
            'matches' => (int) $matchScope->countAllResults(),
            'completed_matches' => (int) $this->db->table('tournament_matches')
                ->where('tournament_id', $tournamentId)
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['completed', 'walkover'])
                ->countAllResults(),
            'checked_in' => (int) $this->db->table('tournament_registrations')->where('tournament_id', $tournamentId)->where('tenant_id', $tenantId)->where('deleted_at', null)->whereIn('checkin_status', ['checked_in'])->countAllResults(),
        ];
    }

    public function getDocument(string $type, int $tournamentId, int $tenantId): ?array
    {
        if (! in_array($type, self::DOCUMENT_TYPES, true)) return null;
        $tournament = model(TournamentModel::class)->findForTenant($tournamentId, $tenantId);
        if (! $tournament) {
            return null;
        }

        return [
            'type' => $type,
            'tournament' => $tournament,
            'categories' => model(TournamentCategoryModel::class)->getByTournament($tournamentId),
            'sponsors' => model(TournamentSponsorModel::class)->getByTournament($tournamentId),
            'registrations' => $this->registrations($tournamentId, $tenantId),
            'matches' => $this->matches($tournamentId, $tenantId),
        ];
    }

    private function registrations(int $tournamentId, int $tenantId): array
    {
        return $this->db->table('tournament_registrations r')
            ->select('r.*, c.name_vi AS category_name, p.full_name AS player_name, pp.full_name AS partner_name, t.team_name')
            ->join('tournament_categories c', 'c.id = r.category_id AND c.tenant_id = r.tenant_id', 'left')
            ->join('players p', 'p.id = r.player_id AND p.tenant_id = r.tenant_id', 'left')
            ->join('players pp', 'pp.id = r.partner_player_id AND pp.tenant_id = r.tenant_id', 'left')
            ->join('teams t', 't.id = r.team_id AND t.tenant_id = r.tenant_id', 'left')
            ->where('r.tournament_id', $tournamentId)->where('r.tenant_id', $tenantId)->where('r.deleted_at', null)
            ->orderBy('c.name_vi', 'ASC')->orderBy('r.id', 'ASC')->get()->getResult();
    }

    private function matches(int $tournamentId, int $tenantId): array
    {
        return $this->db->table('tournament_matches m')
            ->select('m.*, c.name_vi AS category_name, co.name_vi AS court_name, ta.team_name AS team_a_name, tb.team_name AS team_b_name')
            ->join('tournament_categories c', 'c.id = m.category_id AND c.tenant_id = m.tenant_id', 'left')
            ->join('courts co', 'co.id = m.court_id AND co.tenant_id = m.tenant_id', 'left')
            ->join('teams ta', 'ta.id = m.team_a_id AND ta.tenant_id = m.tenant_id', 'left')
            ->join('teams tb', 'tb.id = m.team_b_id AND tb.tenant_id = m.tenant_id', 'left')
            ->where('m.tournament_id', $tournamentId)->where('m.tenant_id', $tenantId)
            ->orderBy('m.scheduled_date', 'ASC')->orderBy('m.start_time', 'ASC')->orderBy('m.match_no', 'ASC')->get()->getResult();
    }
}
