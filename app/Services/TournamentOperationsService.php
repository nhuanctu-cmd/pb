<?php

namespace App\Services;

use App\Models\CourtModel;
use App\Models\TournamentModel;
use CodeIgniter\Database\BaseConnection;

class TournamentOperationsService
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function getDashboard(int $tournamentId, int $tenantId, ?string $date = null): ?array
    {
        $tournament = model(TournamentModel::class)->findForTenant($tournamentId, $tenantId);
        if (! $tournament) {
            return null;
        }

        $date = $date ?: ($tournament->start_date ?: date('Y-m-d'));
        $matches = $this->matches($tournamentId, $tenantId, $date);
        $registrations = $this->registrations($tournamentId, $tenantId);
        $courts = model(CourtModel::class)->where('tenant_id', $tenantId)
            ->where('branch_id', $tournament->branch_id)
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')->orderBy('code', 'ASC')->findAll();

        $now = date('H:i:s');
        $liveStatuses = ['called', 'on_court', 'running', 'in_progress'];
        $live = array_values(array_filter($matches, static fn (object $m): bool => in_array($m->effective_status, $liveStatuses, true)));
        $completed = array_values(array_filter($matches, static fn (object $m): bool => $m->effective_status === 'completed'));
        $delayed = array_values(array_filter($matches, static fn (object $m): bool => $m->effective_status === 'delayed'));
        $waiting = array_values(array_filter($matches, static fn (object $m): bool => in_array($m->effective_status, ['scheduled', 'pending'], true)));

        $courtBoard = [];
        foreach ($courts as $court) {
            $courtMatches = array_values(array_filter($matches, static fn (object $m): bool => (int) ($m->court_id ?? 0) === (int) $court->id));
            $current = $this->firstByStatus($courtMatches, $liveStatuses);
            $upcoming = array_values(array_filter($courtMatches, static fn (object $m): bool => in_array($m->effective_status, ['scheduled', 'pending', 'delayed'], true)));
            usort($upcoming, static fn (object $a, object $b): int => strcmp(($a->start_time ?? '99:99:99'), ($b->start_time ?? '99:99:99')));
            $next = $upcoming[0] ?? null;
            $onDeck = $upcoming[1] ?? null;
            $status = strtolower((string) ($court->status ?? 'available'));
            if (in_array($status, ['maintenance', 'inactive'], true)) {
                $boardStatus = 'maintenance';
            } elseif ($current) {
                $boardStatus = $current->effective_status;
            } elseif ($next && ($next->start_time ?? '') <= $now) {
                $boardStatus = 'delayed';
            } else {
                $boardStatus = 'available';
            }
            $courtBoard[] = [
                'court' => $court,
                'status' => $boardStatus,
                'current' => $current,
                'next' => $next,
                'on_deck' => $onDeck,
            ];
        }

        $checkedIn = count(array_filter($registrations, static fn (object $r): bool => ($r->checkin_status ?? '') === 'checked_in'));
        $noShows = count(array_filter($registrations, static fn (object $r): bool => ($r->checkin_status ?? '') === 'no_show' || (int) ($r->no_show ?? 0) === 1));
        $notCheckedIn = array_values(array_filter($registrations, static fn (object $r): bool => ($r->checkin_status ?? 'pending') === 'pending' && ($r->registration_status ?? '') !== 'cancelled'));
        $availableCourts = count(array_filter($courtBoard, static fn (array $row): bool => $row['status'] === 'available'));

        return [
            'tournament' => $tournament,
            'date' => $date,
            'matches' => $matches,
            'courts' => $courtBoard,
            'not_checked_in' => $notCheckedIn,
            'stats' => [
                'total_matches' => count($matches),
                'completed' => count($completed),
                'live' => count($live),
                'delayed' => count($delayed),
                'waiting' => count($waiting),
                'available_courts' => $availableCourts,
                'total_courts' => count($courtBoard),
                'registered_players' => count($registrations),
                'checked_in' => $checkedIn,
                'no_shows' => $noShows,
            ],
        ];
    }

    public function updateMatchStatus(int $matchId, int $tenantId, string $status, ?string $note = null): array
    {
        $allowed = ['scheduled', 'called', 'on_court', 'running', 'in_progress', 'delayed', 'completed', 'no_show', 'walkover', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'Trạng thái trận không hợp lệ.'];
        }

        $match = $this->db->table('tournament_matches')->where('id', $matchId)->where('tenant_id', $tenantId)->get()->getRow();
        if (! $match) {
            return ['success' => false, 'message' => 'Không tìm thấy trận đấu.'];
        }

        $data = ['status' => $status];
        if ($this->db->fieldExists('operations_note', 'tournament_matches')) {
            $data['operations_note'] = $note;
        }
        if ($status === 'called' && $this->db->fieldExists('called_at', 'tournament_matches')) {
            $data['called_at'] = date('Y-m-d H:i:s');
        }
        if (in_array($status, ['on_court', 'running', 'in_progress'], true) && $this->db->fieldExists('actual_start_time', 'tournament_matches')) {
            $data['actual_start_time'] = date('Y-m-d H:i:s');
        }
        if (in_array($status, ['completed', 'walkover', 'no_show'], true) && $this->db->fieldExists('completed_at', 'tournament_matches')) {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        $this->db->table('tournament_matches')->where('id', $matchId)->where('tenant_id', $tenantId)->update($data);

        return ['success' => true, 'message' => 'Đã cập nhật trạng thái trận.'];
    }

    public function matches(int $tournamentId, int $tenantId, ?string $date = null): array
    {
        $builder = $this->db->table('tournament_matches m')
            ->select('m.*, c.name_vi AS category_name, co.name_vi AS court_name, co.code AS court_code, ta.team_name AS team_a_name, tb.team_name AS team_b_name')
            ->join('tournament_categories c', 'c.id = m.category_id AND c.tenant_id = m.tenant_id', 'left')
            ->join('courts co', 'co.id = m.court_id AND co.tenant_id = m.tenant_id', 'left')
            ->join('teams ta', 'ta.id = m.team_a_id AND ta.tenant_id = m.tenant_id', 'left')
            ->join('teams tb', 'tb.id = m.team_b_id AND tb.tenant_id = m.tenant_id', 'left')
            ->where('m.tournament_id', $tournamentId)->where('m.tenant_id', $tenantId);
        if ($date) {
            $builder->where('m.scheduled_date', $date);
        }
        $rows = $builder->orderBy('m.start_time', 'ASC')->orderBy('m.match_no', 'ASC')->get()->getResult();
        $now = date('H:i:s');
        foreach ($rows as $row) {
            $row->effective_status = (string) $row->status;
            if (in_array($row->effective_status, ['scheduled', 'pending'], true)
                && $row->scheduled_date === date('Y-m-d')
                && $row->start_time && $row->start_time < $now) {
                $row->effective_status = 'delayed';
            }
            $row->team_a_label = $row->team_a_name ?: ($row->team_a_id ? 'Team #' . $row->team_a_id : 'BYE');
            $row->team_b_label = $row->team_b_name ?: ($row->team_b_id ? 'Team #' . $row->team_b_id : 'BYE');
        }
        return $rows;
    }

    private function registrations(int $tournamentId, int $tenantId): array
    {
        return $this->db->table('tournament_registrations r')
            ->select('r.*, c.name_vi AS category_name, p.id AS player_id, pp.id AS partner_player_id, p.full_name AS player_name, pp.full_name AS partner_name')
            ->join('tournament_categories c', 'c.id = r.category_id AND c.tenant_id = r.tenant_id', 'left')
            ->join('players p', 'p.id = r.player_id AND p.tenant_id = r.tenant_id', 'left')
            ->join('players pp', 'pp.id = r.partner_player_id AND pp.tenant_id = r.tenant_id', 'left')
            ->where('r.tournament_id', $tournamentId)->where('r.tenant_id', $tenantId)->where('r.deleted_at', null)
            ->orderBy('r.created_at', 'ASC')->get()->getResult();
    }

    private function firstByStatus(array $matches, array $statuses): ?object
    {
        foreach ($matches as $match) {
            if (in_array($match->effective_status, $statuses, true)) {
                return $match;
            }
        }
        return null;
    }
}
