<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\TournamentCategoryModel;
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
        ];

        return $this->render('admin/tournaments/index', [
            'pageTitle' => 'Giải đấu',
            'pageDescription' => 'Tạo event, mở đăng ký, duyệt vận động viên và quản lý nhà tài trợ.',
            'tournaments' => $this->tournamentModel->getByTenant($tenantId, $filters),
            'filters' => $filters,
        ]);
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

    public function registrations(int $id)
    {
        $data = $this->detailData($id);
        if (! $data) {
            return redirect()->to('/admin/tournaments')->with('error', 'Không tìm thấy giải đấu.');
        }

        return $this->render('admin/tournaments/registrations', $data + [
            'pageTitle' => 'Đăng ký: ' . $data['tournament']->name_vi,
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
        $tournament = $this->tournamentModel->findForTenant($id, (int) current_tenant_id());
        if (! $tournament) {
            return null;
        }

        return [
            'tournament' => $tournament,
            'categories' => model(TournamentCategoryModel::class)->getByTournament($id),
            'rule' => model(TournamentRuleModel::class)->where('tournament_id', $id)->first(),
            'sponsors' => model(TournamentSponsorModel::class)->getByTournament($id),
            'registrations' => model(TournamentRegistrationModel::class)->getByTournament($id),
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
