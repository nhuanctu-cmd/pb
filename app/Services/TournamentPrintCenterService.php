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

    public const DOCUMENT_CATALOG = [
        'badges' => ['label' => 'Thẻ VĐV', 'icon' => 'bi-person-badge', 'group' => 'checkin', 'description' => 'Tên, mã đăng ký, mã QR nhanh', 'scope' => 'tất cả vận động viên'],
        'name_tags' => ['label' => 'Bảng tên', 'icon' => 'bi-type', 'group' => 'checkin', 'description' => 'Tên lớn dùng tại bàn check-in', 'scope' => 'all players'],
        'team_badges' => ['label' => 'Thẻ đội', 'icon' => 'bi-people', 'group' => 'checkin', 'description' => 'Thông tin đối tác/đội cho đơn vị thi đấu', 'scope' => 'đơn vị đôi hoặc team'],
        'staff_badges' => ['label' => 'Thẻ staff', 'icon' => 'bi-person-vcard', 'group' => 'staff', 'description' => 'Staff, referee, MC, truyền thông', 'scope' => 'nhân sự'],
        'court_signs' => ['label' => 'Bảng sân', 'icon' => 'bi-grid-3x3', 'group' => 'operations', 'description' => 'Tên sân + hạng mục + QR xem lịch', 'scope' => 'tất cả sân có trận'],
        'match_cards' => ['label' => 'Phiếu trận', 'icon' => 'bi-clipboard2-check', 'group' => 'competition', 'description' => 'Phiếu ghi điểm cho từng trận', 'scope' => 'theo bộ lọc'],
        'schedule' => ['label' => 'Lịch thi đấu', 'icon' => 'bi-calendar3', 'group' => 'competition', 'description' => 'Lịch theo ngày/sân/khung giờ', 'scope' => 'danh sách trận'],
        'bracket' => ['label' => 'Bracket', 'icon' => 'bi-diagram-3', 'group' => 'competition', 'description' => 'Nhóm đấu theo hạng mục', 'scope' => 'theo danh mục'],
        'certificates' => ['label' => 'Chứng nhận', 'icon' => 'bi-award', 'group' => 'result', 'description' => 'Chứng nhận tham gia', 'scope' => 'tất cả vận động viên'],
        'results' => ['label' => 'Bảng kết quả', 'icon' => 'bi-trophy', 'group' => 'result', 'description' => 'Kết quả chung cuộc & xếp hạng nhanh', 'scope' => 'trận đã hoàn tất'],
        'participants' => ['label' => 'Danh sách VĐV', 'icon' => 'bi-list-ul', 'group' => 'ops', 'description' => 'Danh sách đăng ký cho in hồ sơ', 'scope' => 'chi tiết theo hạng mục'],
        'checkin' => ['label' => 'Danh sách check-in', 'icon' => 'bi-person-check', 'group' => 'checkin', 'description' => 'Theo dõi điểm danh để đối chiếu', 'scope' => 'theo trạng thái check-in'],
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

    public function getCategoryOptions(int $tournamentId): array
    {
        return model(TournamentCategoryModel::class)->getByTournament($tournamentId);
    }

    public function getCourtOptions(int $tournamentId, int $tenantId): array
    {
        return $this->courts($tournamentId, $tenantId);
    }

    public function getDocument(string $type, int $tournamentId, int $tenantId): ?array
    {
        if (! in_array($type, self::DOCUMENT_TYPES, true)) return null;
        $tournament = model(TournamentModel::class)->findForTenant($tournamentId, $tenantId);
        if (! $tournament) {
            return null;
        }
        $options = $this->normalizePrintOptions([]);

        return [
            'type' => $type,
            'tournament' => $tournament,
            'documentOptions' => $options,
            'documentMeta' => self::DOCUMENT_CATALOG[$type] ?? ['label' => ucfirst($type), 'icon' => 'bi-printer'],
            'categories' => model(TournamentCategoryModel::class)->getByTournament($tournamentId),
            'sponsors' => model(TournamentSponsorModel::class)->getByTournament($tournamentId),
            'registrations' => $this->registrations($tournamentId, $tenantId, $options),
            'matches' => $this->matches($tournamentId, $tenantId, $options),
            'results' => $this->results($tournamentId, $tenantId, $options),
            'courts' => $this->courts($tournamentId, $tenantId, $options),
            'printScopeTitle' => $this->buildScopeLabel($options),
        ];
    }

    public function normalizePrintOptions(array $input): array
    {
        $categoryId = (int) ($input['category_id'] ?? 0);
        $courtId = (int) ($input['court_id'] ?? 0);
        $dateFrom = trim((string) ($input['date_from'] ?? ''));
        $dateTo = trim((string) ($input['date_to'] ?? ''));
        $status = (string) ($input['status'] ?? '');
        $checkinStatus = (string) ($input['checkin_status'] ?? '');
        $sequence = (string) ($input['sequence'] ?? '');

        if ($status !== '' && in_array($status, ['scheduled', 'pending', 'called', 'on_court', 'running', 'in_progress', 'completed', 'walkover', 'cancelled'], true)) {
            // keep
        } else {
            $status = '';
        }

        if ($checkinStatus !== '' && in_array($checkinStatus, ['pending', 'checked_in', 'absent'], true)) {
            // keep
        } else {
            $checkinStatus = '';
        }

        return [
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'court_id' => $courtId > 0 ? $courtId : null,
            'date_from' => $dateFrom !== '' ? $dateFrom : null,
            'date_to' => $dateTo !== '' ? $dateTo : null,
            'status' => $status !== '' ? $status : null,
            'checkin_status' => $checkinStatus !== '' ? $checkinStatus : null,
            'sequence' => trim($sequence),
        ];
    }

    private function registrations(int $tournamentId, int $tenantId, array $options = []): array
    {
        $builder = $this->db->table('tournament_registrations r')
            ->select('r.*, c.name_vi AS category_name, p.full_name AS player_name, pp.full_name AS partner_name, t.team_name')
            ->join('tournament_categories c', 'c.id = r.category_id AND c.tenant_id = r.tenant_id', 'left')
            ->join('players p', 'p.id = r.player_id AND p.tenant_id = r.tenant_id', 'left')
            ->join('players pp', 'pp.id = r.partner_player_id AND pp.tenant_id = r.tenant_id', 'left')
            ->join('teams t', 't.id = r.team_id AND t.tenant_id = r.tenant_id', 'left')
            ->where('r.tournament_id', $tournamentId)->where('r.tenant_id', $tenantId)->where('r.deleted_at', null);
        if ($options['category_id'] ?? null) {
            $builder->where('r.category_id', (int) $options['category_id']);
        }
        if ($options['checkin_status'] ?? null) {
            $builder->where('r.checkin_status', (string) $options['checkin_status']);
        }
        return $builder->orderBy('c.name_vi', 'ASC')->orderBy('r.id', 'ASC')->get()->getResult();
    }

    private function matches(int $tournamentId, int $tenantId, array $options = []): array
    {
        $builder = $this->db->table('tournament_matches m')
            ->select('m.*, c.name_vi AS category_name, co.name_vi AS court_name, ta.team_name AS team_a_name, tb.team_name AS team_b_name')
            ->join('tournament_categories c', 'c.id = m.category_id AND c.tenant_id = m.tenant_id', 'left')
            ->join('courts co', 'co.id = m.court_id AND co.tenant_id = m.tenant_id', 'left')
            ->join('teams ta', 'ta.id = m.team_a_id AND ta.tenant_id = m.tenant_id', 'left')
            ->join('teams tb', 'tb.id = m.team_b_id AND tb.tenant_id = m.tenant_id', 'left')
            ->where('m.tournament_id', $tournamentId)->where('m.tenant_id', $tenantId);

        if ($options['category_id'] ?? null) {
            $builder->where('m.category_id', (int) $options['category_id']);
        }
        if ($options['court_id'] ?? null) {
            $builder->where('m.court_id', (int) $options['court_id']);
        }
        if ($options['status'] ?? null) {
            $builder->where('m.status', (string) $options['status']);
        }
        if ($options['date_from'] ?? null) {
            $builder->where('m.scheduled_date >=', (string) $options['date_from']);
        }
        if ($options['date_to'] ?? null) {
            $builder->where('m.scheduled_date <=', (string) $options['date_to']);
        }

        return $builder->orderBy('m.scheduled_date', 'ASC')->orderBy('m.start_time', 'ASC')->orderBy('m.match_no', 'ASC')->get()->getResult();
    }

    private function courts(int $tournamentId, int $tenantId, array $options = []): array
    {
        $builder = $this->db->table('tournament_matches m')
            ->select('co.id AS court_id, co.name_vi AS court_name, c.name_vi AS category_name')
            ->join('courts co', 'co.id = m.court_id AND co.tenant_id = m.tenant_id', 'left')
            ->join('tournament_categories c', 'c.id = m.category_id AND c.tenant_id = m.tenant_id', 'left')
            ->where('m.tournament_id', $tournamentId)
            ->where('m.tenant_id', $tenantId)
            ->where('co.id IS NOT NULL')
            ->groupBy('co.id')
            ->orderBy('co.name_vi', 'ASC');
        $rows = $builder->get()->getResult();
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int) $row->court_id,
                'name' => $row->court_name ?: ('Court #' . (int) $row->court_id),
                'category_name' => $row->category_name,
            ];
        }
        return $items;
    }

    private function results(int $tournamentId, int $tenantId, array $options = []): array
    {
        $matches = $this->matches($tournamentId, $tenantId, array_merge($options, ['status' => 'completed']));
        usort($matches, static fn($a, $b) => strcmp((string) ($b->scheduled_date ?? ''), (string) ($a->scheduled_date ?? '')));
        $top = [];
        $winners = [];
        foreach ($matches as $match) {
            $winner = $this->resolveWinner($match);
            if ($winner !== null) {
                $winners[] = $winner;
            }
            if (count($top) < 5) {
                $top[] = $match;
            }
        }

        return [
            'rows' => $matches,
            'top' => $top,
            'winners' => array_slice($winners, 0, 3),
        ];
    }

    private function resolveWinner(object $match): ?string
    {
        if (($match->status ?? '') !== 'completed' && ($match->status ?? '') !== 'walkover') {
            return null;
        }
        if (! empty($match->winner_team_id)) {
            if ((int) $match->winner_team_id === (int) ($match->team_a_id ?? 0)) {
                return $match->team_a_name ?: ('Team #' . (int) ($match->team_a_id ?? 0));
            }
            if ((int) $match->winner_team_id === (int) ($match->team_b_id ?? 0)) {
                return $match->team_b_name ?: ('Team #' . (int) ($match->team_b_id ?? 0));
            }
        }
        return null;
    }

    public function getPrintPack(string $type, int $tournamentId, int $tenantId, array $rawOptions = []): array
    {
        $allTypes = $rawOptions['types'] ?? self::DOCUMENT_TYPES;
        $types = [];
        foreach ((array) $allTypes as $documentType) {
            if (! is_string($documentType) || ! in_array($documentType, self::DOCUMENT_TYPES, true)) {
                continue;
            }
            $types[] = $documentType;
        }
        if (empty($types)) {
            $types = self::DOCUMENT_TYPES;
        }

        $options = $this->normalizePrintOptions($rawOptions);
        $printData = [];
        foreach ($types as $documentType) {
            $documentData = $this->getDocumentWithOptions($documentType, $tournamentId, $tenantId, $options);
            if ($documentData) {
                $printData[] = $documentData;
            }
        }
        return $printData;
    }

    public function getDocumentWithOptions(string $type, int $tournamentId, int $tenantId, array $options = []): ?array
    {
        if (! in_array($type, self::DOCUMENT_TYPES, true)) return null;
        $tournament = model(TournamentModel::class)->findForTenant($tournamentId, $tenantId);
        if (! $tournament) {
            return null;
        }
        $options = $this->normalizePrintOptions($options);

        return [
            'type' => $type,
            'tournament' => $tournament,
            'documentOptions' => $options,
            'documentMeta' => self::DOCUMENT_CATALOG[$type] ?? ['label' => ucfirst($type), 'icon' => 'bi-printer'],
            'categories' => model(TournamentCategoryModel::class)->getByTournament($tournamentId),
            'sponsors' => model(TournamentSponsorModel::class)->getByTournament($tournamentId),
            'registrations' => $this->registrations($tournamentId, $tenantId, $options),
            'matches' => $this->matches($tournamentId, $tenantId, $options),
            'results' => $this->results($tournamentId, $tenantId, $options),
            'courts' => $this->courts($tournamentId, $tenantId, $options),
            'printScopeTitle' => $this->buildScopeLabel($options),
        ];
    }

    private function buildScopeLabel(array $options): string
    {
        $parts = [];
        if ($options['category_id'] ?? null) {
            $parts[] = 'Mã hạng mục #' . (int) $options['category_id'];
        }
        if ($options['court_id'] ?? null) {
            $parts[] = 'Sân #' . (int) $options['court_id'];
        }
        if (($options['date_from'] ?? null) || ($options['date_to'] ?? null)) {
            $parts[] = trim(($options['date_from'] ?? '-') . ' → ' . ($options['date_to'] ?? '-'));
        }
        return $parts ? implode(' | ', $parts) : 'Toàn bộ dữ liệu';
    }

    public function getDocumentPackMeta(array $rawOptions = []): array
    {
        $options = $this->normalizePrintOptions($rawOptions);
        return array_merge([
            'options' => $options,
            'label' => $this->buildScopeLabel($options),
        ], []);
    }
}
