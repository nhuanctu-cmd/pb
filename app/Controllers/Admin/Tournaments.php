<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\TournamentCategoryModel;
use App\Models\PlayerModel;
use App\Models\TournamentModel;
use App\Models\TournamentRegistrationModel;
use App\Models\TournamentRuleModel;
use App\Models\TournamentSponsorModel;
use App\Services\TournamentRegistrationService;
use App\Services\TournamentService;

class Tournaments extends BaseController
{
    protected TournamentService $tournamentService;
    protected TournamentRegistrationService $registrationService;
    protected TournamentModel $tournamentModel;

    public function __construct()
    {
        $this->tournamentService = new TournamentService();
        $this->registrationService = new TournamentRegistrationService();
        $this->tournamentModel = model(TournamentModel::class);
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $filters = [
            'status' => $this->request->getGet('status'),
            'search' => $this->request->getGet('search'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to'),
        ];
        $perPage = (int) ($this->request->getGet('per_page') ?: 15);
        $pagination = $this->tournamentModel->getByTenantPaginated($tenantId, (int) ($this->request->getGet('page') ?: 1), $perPage, $filters);

        return $this->render('admin/tournaments/index', [
            'pageTitle' => 'Giải đấu',
            'pageDescription' => 'Tạo event, mở đăng ký, duyệt vận động viên và quản lý nhà tài trợ.',
            'tournaments' => $pagination['items'],
            'pagination' => $pagination,
            'filters' => $filters,
        ]);
    }

    public function export()
    {
        $filters = [
            'status' => $this->request->getGet('status'),
            'search' => $this->request->getGet('search'),
            'date_from' => $this->request->getGet('date_from'),
            'date_to' => $this->request->getGet('date_to'),
        ];
        $rows = $this->tournamentModel->getByTenant((int) current_tenant_id(), $filters);
        $csv = "\xEF\xBB\xBF" . implode(',', ['ID', 'Tên giải', 'Slug', 'Chi nhánh', 'Ngày bắt đầu', 'Ngày kết thúc', 'Trạng thái', 'Phí']) . "\n";
        foreach ($rows as $row) {
            $values = [$row->id, $row->name_vi, $row->slug_vi, $row->branch_name, $row->start_date, $row->end_date, $row->status, $row->registration_fee];
            $csv .= implode(',', array_map(static fn ($value) => '"' . str_replace('"', '""', (string) $value) . '"', $values)) . "\n";
        }
        return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')->setHeader('Content-Disposition', 'attachment; filename="tournaments.csv"')->setBody($csv);
    }

    public function create()
    {
        return $this->form('create');
    }

    public function store()
    {
        $result = $this->tournamentService->createTournament($this->payload((int) current_tenant_id()));
        if ($result['success']) {
            return redirect()->to('/admin/tournaments')->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function edit(int $id)
    {
        $tournament = $this->tournamentModel->findForTenant($id, (int) current_tenant_id());
        if (! $tournament) {
            return redirect()->to('/admin/tournaments')->with('error', 'Không tìm thấy giải đấu.');
        }

        return $this->form('edit', $tournament);
    }

    public function update(int $id)
    {
        $result = $this->tournamentService->updateTournament(
            $id, $this->payload((int) current_tenant_id()), (int) current_tenant_id()
        );
        if ($result['success']) {
            return redirect()->to('/admin/tournaments/show/' . $id)->with('success', $result['message']);
        }

        return redirect()->back()->withInput()->with('error', $result['message']);
    }

    public function show(int $id)
    {
        $data = $this->detailData($id);
        if (! $data) {
            return redirect()->to('/admin/tournaments')->with('error', 'Không tìm thấy giải đấu.');
        }

        return $this->render('admin/tournaments/show', $data + [
            'pageTitle' => $data['tournament']->name_vi,
        ]);
    }

    /**
     * Điểm vào chung từ menu: mở thẳng màn hình đăng ký của giải đang mở gần
     * nhất, tránh bắt nhân viên phải nhớ và nhập ID giải thủ công.
     */
    public function registrationHub()
    {
        $tenantId = (int) current_tenant_id();
        $tournaments = $this->tournamentModel->getByTenant($tenantId, ['status' => '']);
        usort($tournaments, static function (object $a, object $b): int {
            $priority = ['running' => 0, 'open' => 1, 'draft' => 2, 'closed' => 3, 'completed' => 4, 'cancelled' => 5];
            return ($priority[$a->status] ?? 9) <=> ($priority[$b->status] ?? 9) ?: strcmp((string) $a->start_date, (string) $b->start_date);
        });
        $tournament = $tournaments[0] ?? null;
        if (! $tournament) return redirect()->to('/admin/tournaments')->with('error', 'Tenant chưa có giải đấu để quản lý đăng ký.');
        return redirect()->to('/admin/tournaments/registrations/' . (int) $tournament->id);
    }

    public function registrations(int $id)
    {
        $data = $this->detailData($id);
        if (! $data) {
            return redirect()->to('/admin/tournaments')->with('error', 'Không tìm thấy giải đấu.');
        }

        $registrationFilters = [
            'search' => trim((string) $this->request->getGet('search')),
            'category_id' => (int) $this->request->getGet('category_id'),
            'approval_status' => (string) $this->request->getGet('approval_status'),
            'payment_status' => (string) $this->request->getGet('payment_status'),
            'checkin_status' => (string) $this->request->getGet('checkin_status'),
        ];
        $registrationPage = model(TournamentRegistrationModel::class)->getByTournamentPaginated(
            $id,
            (int) current_tenant_id(),
            (int) ($this->request->getGet('page') ?: 1),
            (int) ($this->request->getGet('per_page') ?: 20),
            $registrationFilters
        );
        $data['registrations'] = $registrationPage['items'];

        return $this->render('admin/tournaments/registrations', $data + [
            'pageTitle' => 'Đăng ký: ' . $data['tournament']->name_vi,
            'players' => model(PlayerModel::class)->where('tenant_id', (int) current_tenant_id())->where('status', 'active')->where('deleted_at', null)->orderBy('full_name', 'ASC')->findAll(200),
            'registrationPage' => $registrationPage,
            'registrationFilters' => $registrationFilters,
        ]);
    }

    public function approveRegistration(int $id)
    {
        $result = $this->registrationService->approveRegistration($id, (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function rejectRegistration(int $id)
    {
        $result = $this->registrationService->rejectRegistration($id, $this->request->getPost('note'), (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function registerAthlete(int $tournamentId)
    {
        $tenantId = (int) current_tenant_id();
        $tournament = $this->tournamentModel->findForTenant($tournamentId, $tenantId);
        $categoryId = (int) $this->request->getPost('category_id');
        $category = $categoryId ? model(TournamentCategoryModel::class)->where('id', $categoryId)->where('tenant_id', $tenantId)->first() : null;
        $playerId = (int) $this->request->getPost('player_id');
        $player = $playerId ? model(PlayerModel::class)->where('id', $playerId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first() : null;

        if (! $tournament || ! $category || (int) $category->tournament_id !== $tournamentId || ! $player) {
            return redirect()->back()->with('error', 'Giải, hạng mục hoặc vận động viên không hợp lệ.');
        }
        if (in_array((string) $tournament->status, ['completed', 'cancelled'], true)) {
            return redirect()->back()->with('error', 'Giải đấu đã kết thúc hoặc bị hủy, không thể thêm đăng ký.');
        }
        $registrationModel = model(TournamentRegistrationModel::class);
        $duplicate = $registrationModel->where('tenant_id', $tenantId)->where('tournament_id', $tournamentId)->where('category_id', $categoryId)->where('player_id', $playerId)->where('deleted_at', null)->first();
        if ($duplicate) {
            return redirect()->back()->with('error', 'Vận động viên đã có trong hạng mục này.');
        }

        $partnerPlayerId = (int) ($this->request->getPost('partner_player_id') ?: 0);
        if ($partnerPlayerId && ! model(PlayerModel::class)->where('id', $partnerPlayerId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first()) {
            return redirect()->back()->with('error', 'Vận động viên đánh cặp không thuộc tenant hiện tại.');
        }
        $approvedCount = $registrationModel->countApprovedByCategory($categoryId, $tenantId);
        $approved = $this->request->getPost('quick_approve') === '1';
        $isFull = (int) ($category->max_teams ?? 0) > 0 && $approvedCount >= (int) $category->max_teams;
        $approvalStatus = $approved && ! $isFull ? 'approved' : 'pending';
        $registrationStatus = $approved && ! $isFull ? 'confirmed' : ($isFull ? 'waitlisted' : 'pending');
        $waitlistPosition = $isFull ? $registrationModel->getNextWaitlistPosition($categoryId, $tenantId) : null;
        $inserted = $registrationModel->insert([
            'tenant_id' => $tenantId, 'tournament_id' => $tournamentId, 'category_id' => $categoryId,
            'player_id' => $playerId, 'partner_player_id' => $partnerPlayerId ?: null,
            'team_id' => $this->nullIfEmpty($this->request->getPost('team_id')),
            'contact_name' => trim((string) ($this->request->getPost('contact_name') ?: $player->full_name)),
            'contact_phone' => trim((string) ($this->request->getPost('contact_phone') ?: ($player->phone ?? ''))),
            'payment_status' => $this->request->getPost('payment_status') ?: 'unpaid',
            'approval_status' => $approvalStatus,
            'registration_status' => $registrationStatus,
            'eligibility_status' => 'passed', 'waitlist_position' => $waitlistPosition,
            'invoice_amount' => (float) ($category->registration_fee ?? $tournament->registration_fee ?? 0),
            'invoice_code' => 'TRN-' . date('ymd') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
        ]);
        if (! $inserted) {
            return redirect()->back()->with('error', 'Không thể tạo hồ sơ đăng ký.');
        }

        return redirect()->back()->with('success', $isFull ? 'Hạng mục đã đủ, hồ sơ được đưa vào danh sách chờ.' : ($approved ? 'Đã thêm và duyệt nhanh vận động viên.' : 'Đã thêm hồ sơ vận động viên vào giải.'));
    }

    public function updateRegistration(int $registrationId)
    {
        $tenantId = (int) current_tenant_id();
        $model = model(TournamentRegistrationModel::class);
        $registration = $model->where('id', $registrationId)->where('tenant_id', $tenantId)->first();
        if (! $registration) {
            return redirect()->back()->with('error', 'Không tìm thấy hồ sơ đăng ký.');
        }
        $enums = [
            'approval_status' => ['pending', 'approved', 'rejected'],
            'registration_status' => ['draft', 'pending', 'confirmed', 'waitlisted', 'cancelled'],
            'payment_status' => ['unpaid', 'pending', 'paid', 'refunded', 'failed'],
            'eligibility_status' => ['pending', 'passed', 'flagged'],
        ];
        $payload = [];
        foreach ($enums as $field => $values) {
            $value = $this->request->getPost($field);
            if ($value !== null && in_array($value, $values, true)) $payload[$field] = $value;
        }
        if ($this->request->getPost('note') !== null) $payload['note'] = trim((string) $this->request->getPost('note'));
        if (($payload['approval_status'] ?? null) === 'approved' && $registration->approval_status !== 'approved') {
            $category = model(TournamentCategoryModel::class)->where('id', $registration->category_id)->where('tenant_id', $tenantId)->first();
            if ($category && (int) ($category->max_teams ?? 0) > 0 && $registrationModel->countApprovedByCategory((int) $registration->category_id, $tenantId) >= (int) $category->max_teams) {
                return redirect()->back()->with('error', 'Hạng mục đã đủ số lượng, không thể duyệt thêm.');
            }
        }
        if ($payload && $model->update($registrationId, $payload)) {
            return redirect()->back()->with('success', 'Đã cập nhật hồ sơ đăng ký.');
        }
        return redirect()->back()->with('error', 'Không có thay đổi hợp lệ.');
    }

    public function checkinRegistration(int $registrationId)
    {
        $tenantId = (int) current_tenant_id();
        $model = model(TournamentRegistrationModel::class);
        $registration = $model->where('id', $registrationId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
        if (! $registration) return redirect()->back()->with('error', 'Không tìm thấy hồ sơ đăng ký.');

        $status = $this->request->getPost('status') ?: 'checked_in';
        if (! in_array($status, ['pending', 'checked_in', 'no_show'], true)) $status = 'pending';
        $model->update($registrationId, ['checkin_status' => $status, 'checked_in_at' => $status === 'checked_in' ? date('Y-m-d H:i:s') : null, 'no_show' => $status === 'no_show' ? 1 : 0]);
        if ($status === 'checked_in' && $registration->player_id && Config\Database::connect()->tableExists('tournament_checkins')) {
            $checkinModel = model(\App\Models\TournamentCheckinModel::class);
            $players = array_filter([(int) $registration->player_id, (int) ($registration->partner_player_id ?? 0)]);
            foreach ($players as $playerId) {
                $existing = $checkinModel->findByRegistration($registrationId, $playerId);
                $data = ['tenant_id' => $tenantId, 'tournament_id' => $registration->tournament_id, 'category_id' => $registration->category_id, 'registration_id' => $registrationId, 'player_id' => $playerId, 'status' => 'checked_in', 'checked_in_by' => user_id(), 'checked_in_at' => date('Y-m-d H:i:s')];
                $existing ? $checkinModel->update($existing->id, $data) : $checkinModel->insert($data);
            }
        }
        return redirect()->back()->with('success', $status === 'checked_in' ? 'Đã check-in vận động viên.' : 'Đã cập nhật trạng thái check-in.');
    }

    public function deleteRegistration(int $registrationId)
    {
        $model = model(TournamentRegistrationModel::class);
        $registration = $model->where('id', $registrationId)->where('tenant_id', (int) current_tenant_id())->first();
        if (! $registration) return redirect()->back()->with('error', 'Không tìm thấy hồ sơ đăng ký.');
        $model->delete($registrationId);
        return redirect()->back()->with('success', 'Đã xóa hồ sơ đăng ký khỏi giải.');
    }

    public function exportRegistrations(int $tournamentId)
    {
        $data = $this->detailData($tournamentId);
        if (! $data) return redirect()->to('/admin/tournaments')->with('error', 'Không tìm thấy giải đấu.');
        $filters = [
            'search' => trim((string) $this->request->getGet('search')),
            'category_id' => (int) $this->request->getGet('category_id'),
            'approval_status' => (string) $this->request->getGet('approval_status'),
            'payment_status' => (string) $this->request->getGet('payment_status'),
            'checkin_status' => (string) $this->request->getGet('checkin_status'),
        ];
        $data['registrations'] = array_values(array_filter($data['registrations'], static function ($row) use ($filters) {
            if ($filters['category_id'] && (int) $row->category_id !== $filters['category_id']) return false;
            foreach (['approval_status', 'payment_status', 'checkin_status'] as $field) if ($filters[$field] !== '' && (string) ($row->{$field} ?? '') !== $filters[$field]) return false;
            if ($filters['search'] !== '' && stripos((string) ($row->contact_name ?? '') . ' ' . ($row->contact_phone ?? '') . ' ' . ($row->invoice_code ?? ''), $filters['search']) === false) return false;
            return true;
        }));
        $csv = "\xEF\xBB\xBF" . implode(',', ['VĐV/Đội', 'Điện thoại', 'Hạng mục', 'Duyệt', 'Đăng ký', 'Thanh toán', 'Check-in', 'Số tiền', 'Ngày tạo']) . "\n";
        foreach ($data['registrations'] as $registration) {
            $row = [$registration->contact_name ?? '', $registration->contact_phone ?? '', $registration->category_name ?? '', $registration->approval_status ?? '', $registration->registration_status ?? '', $registration->payment_status ?? '', $registration->checkin_status ?? '', $registration->invoice_amount ?? 0, $registration->created_at ?? ''];
            $csv .= implode(',', array_map(static fn ($value) => '"' . str_replace('"', '""', (string) $value) . '"', $row)) . "\n";
        }
        return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')->setHeader('Content-Disposition', 'attachment; filename="tournament-' . $tournamentId . '-registrations.csv"')->setBody($csv);
    }

    public function open(int $id)
    {
        $result = $this->tournamentService->openRegistration($id, (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function close(int $id)
    {
        $result = $this->tournamentService->closeRegistration($id, (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function start(int $id)
    {
        $result = $this->tournamentService->startTournament($id, (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function complete(int $id)
    {
        $result = $this->tournamentService->completeTournament($id, (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function cancel(int $id)
    {
        $result = $this->tournamentService->cancelTournament($id, (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function form(string $mode, ?object $tournament = null): string
    {
        $tenantId = (int) current_tenant_id();
        $id = (int) ($tournament->id ?? 0);

        return $this->render('admin/tournaments/form', [
            'pageTitle' => $mode === 'create' ? 'Tạo giải đấu' : 'Sửa giải đấu',
            'mode' => $mode,
            'tournament' => $tournament,
            'branches' => model(BranchModel::class)->getByTenant($tenantId),
            'categories' => $id ? model(TournamentCategoryModel::class)->getByTournament($id) : [],
            'rule' => $id ? model(TournamentRuleModel::class)->where('tournament_id', $id)->first() : null,
            'sponsors' => $id ? model(TournamentSponsorModel::class)->getByTournament($id) : [],
        ]);
    }

    private function detailData(int $id): ?array
    {
        $tenantId = (int) current_tenant_id();
        $tournament = $this->tournamentModel->findForTenant($id, $tenantId);
        if (! $tournament) {
            return null;
        }

        $db = \Config\Database::connect();
        $registrationTable = $db->table('tournament_registrations');
        $registrationQuery = static function (string $column, $value) use ($db, $id, $tenantId) {
            $query = $db->table('tournament_registrations')
                ->where('tournament_id', $id)
                ->where('tenant_id', $tenantId)
                ->where('deleted_at', null);
            if ($column !== '') {
                $query->where($column, $value);
            }
            return (int) $query->countAllResults();
        };
        $totalRegistrations = $registrationQuery('', null);
        $approvedRegistrations = $registrationQuery('approval_status', 'approved');
        $pendingRegistrations = $registrationQuery('approval_status', 'pending');
        $rejectedRegistrations = $registrationQuery('approval_status', 'rejected');
        $confirmedRegistrations = $registrationQuery('registration_status', 'confirmed');
        $paidRegistrations = $registrationQuery('payment_status', 'paid');
        $checkedInRegistrations = 0;
        if ($db->fieldExists('checkin_status', 'tournament_registrations')) {
            $checkedInRegistrations = $registrationQuery('checkin_status', 'checked_in');
        } elseif ($db->fieldExists('checked_in_at', 'tournament_registrations')) {
            $checkedInRegistrations = (int) $registrationTable
                ->where('tournament_id', $id)->where('tenant_id', $tenantId)
                ->where('deleted_at', null)->where('checked_in_at IS NOT NULL', null, false)
                ->countAllResults();
        }

        $matchStats = [
            'total' => 0, 'scheduled' => 0, 'completed' => 0, 'live' => 0,
            'unassigned' => 0, 'next' => [],
        ];
        if ($db->tableExists('tournament_matches')) {
            $matchBase = $db->table('tournament_matches')
                ->where('tournament_id', $id)->where('tenant_id', $tenantId);
            $matchStats['total'] = (int) (clone $matchBase)->countAllResults();
            $matchStats['scheduled'] = (int) (clone $matchBase)->whereNotIn('status', ['draft', 'pending'])->countAllResults();
            $matchStats['completed'] = (int) (clone $matchBase)->whereIn('status', ['completed', 'finished'])->countAllResults();
            $matchStats['live'] = (int) (clone $matchBase)->whereIn('status', ['running', 'in_progress', 'on_court'])->countAllResults();
            $matchStats['unassigned'] = (int) (clone $matchBase)->groupStart()->where('court_id', null)->orWhere('scheduled_date', null)->groupEnd()->countAllResults();
            $matchStats['next'] = $db->table('tournament_matches')
                ->where('tournament_id', $id)->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['completed', 'finished', 'cancelled'])
                ->groupStart()->where('scheduled_date >=', date('Y-m-d'))->orWhere('scheduled_date', null)->groupEnd()
                ->orderBy('scheduled_date', 'ASC')->orderBy('start_time', 'ASC')->orderBy('match_no', 'ASC')
                ->get(8)->getResult();
        }

        $categories = model(TournamentCategoryModel::class)->getByTournament($id);
        $categoryStats = [];
        $hasCheckinStatus = $db->fieldExists('checkin_status', 'tournament_registrations');
        foreach ($categories as $category) {
            $categoryId = (int) $category->id;
            $categoryBuilder = static function () use ($db, $id, $tenantId, $categoryId) {
                return $db->table('tournament_registrations')
                    ->where('tournament_id', $id)->where('tenant_id', $tenantId)
                    ->where('category_id', $categoryId)->where('deleted_at', null);
            };
            $checkedInBuilder = $categoryBuilder();
            if ($hasCheckinStatus) {
                $checkedInBuilder->where('checkin_status', 'checked_in');
            } else {
                $checkedInBuilder->where('checked_in_at IS NOT NULL', null, false);
            }
            $categoryStats[$categoryId] = [
                'total' => (int) $categoryBuilder()->countAllResults(),
                'approved' => (int) $categoryBuilder()->where('approval_status', 'approved')->countAllResults(),
                'checked_in' => (int) $checkedInBuilder->countAllResults(),
                'matches' => $db->tableExists('tournament_matches') ? (int) $db->table('tournament_matches')->where('tournament_id', $id)->where('tenant_id', $tenantId)->where('category_id', $categoryId)->countAllResults() : 0,
            ];
        }

        $expectedRevenue = (float) $db->table('tournament_registrations')
            ->selectSum('invoice_amount')
            ->where('tournament_id', $id)->where('tenant_id', $tenantId)
            ->whereIn('approval_status', ['approved', 'pending'])
            ->where('deleted_at', null)->get()->getRow('invoice_amount');

        return [
            'tournament' => $tournament,
            'categories' => $categories,
            'rule' => model(TournamentRuleModel::class)->where('tournament_id', $id)->first(),
            'sponsors' => model(TournamentSponsorModel::class)->getByTournament($id),
            'registrations' => model(TournamentRegistrationModel::class)->getByTournament($id, $tenantId),
            'categoryStats' => $categoryStats,
            'tournamentStats' => [
                'registrations' => $totalRegistrations,
                'approved' => $approvedRegistrations,
                'pending' => $pendingRegistrations,
                'rejected' => $rejectedRegistrations,
                'confirmed' => $confirmedRegistrations,
                'paid' => $paidRegistrations,
                'checked_in' => $checkedInRegistrations,
                'expected_revenue' => $expectedRevenue,
                'capacity' => (int) ($tournament->max_teams ?? 0),
                'matches' => $matchStats['total'],
                'scheduled_matches' => $matchStats['scheduled'],
                'completed_matches' => $matchStats['completed'],
                'live_matches' => $matchStats['live'],
                'unassigned_matches' => $matchStats['unassigned'],
            ],
            'nextMatches' => $matchStats['next'],
        ];
    }

    private function payload(int $tenantId): array
    {
        return [
            'tenant_id' => $tenantId,
            'branch_id' => $this->nullIfEmpty($this->request->getPost('branch_id')),
            'name_vi' => trim((string) $this->request->getPost('name_vi')),
            'name_en' => $this->nullIfEmpty($this->request->getPost('name_en')),
            'slug_vi' => $this->nullIfEmpty($this->request->getPost('slug_vi')),
            'slug_en' => $this->nullIfEmpty($this->request->getPost('slug_en')),
            'description_vi' => $this->nullIfEmpty($this->request->getPost('description_vi')),
            'description_en' => $this->nullIfEmpty($this->request->getPost('description_en')),
            'banner' => $this->nullIfEmpty($this->request->getPost('banner')),
            'start_date' => $this->nullIfEmpty($this->request->getPost('start_date')),
            'end_date' => $this->nullIfEmpty($this->request->getPost('end_date')),
            'registration_start' => $this->cleanDateTime($this->request->getPost('registration_start')),
            'registration_end' => $this->cleanDateTime($this->request->getPost('registration_end')),
            'max_teams' => $this->nullIfEmpty($this->request->getPost('max_teams')),
            'registration_fee' => $this->request->getPost('registration_fee') ?: 0,
            'status' => $this->request->getPost('status') ?: 'draft',
            'categories' => $this->request->getPost('categories') ?? [],
            'rule_content_vi' => $this->request->getPost('rule_content_vi'),
            'rule_content_en' => $this->request->getPost('rule_content_en'),
            'sponsors' => $this->request->getPost('sponsors') ?? [],
        ];
    }

    private function nullIfEmpty($value)
    {
        return $value === '' || $value === null ? null : $value;
    }

    private function cleanDateTime($value): ?string
    {
        $value = $this->nullIfEmpty($value);
        return $value ? str_replace('T', ' ', (string) $value) . (strlen((string) $value) === 16 ? ':00' : '') : null;
    }
}
