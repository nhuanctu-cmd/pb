<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Models\TournamentRegistrationModel;
use App\Models\TournamentRuleModel;
use App\Models\TournamentSponsorModel;
use App\Services\TournamentRegistrationService;

class Tournaments extends BaseController
{
    protected TournamentModel $tournamentModel;
    protected TournamentRegistrationService $registrationService;

    public function __construct()
    {
        $this->tournamentModel = model(TournamentModel::class);
        $this->registrationService = new TournamentRegistrationService();
    }

    public function list()
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $tournaments = $this->tournamentModel->where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'closed', 'running', 'completed'])
            ->where('deleted_at', null)
            ->orderBy('start_date', 'DESC')
            ->findAll();

        return view('public/tournaments/list', [
            'pageTitle' => lang('Tournament.public_title'),
            'current_locale' => service('language')->getLocale(),
            'tournaments' => $tournaments,
        ]);
    }

    public function detail(string $slug)
    {
        $data = $this->publicData($slug);
        if (! $data) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('public/tournaments/detail', $data + [
            'pageTitle' => $this->localized($data['tournament'], 'name'),
            'current_locale' => service('language')->getLocale(),
        ]);
    }

    public function register(string $slug)
    {
        $data = $this->publicData($slug);
        if (! $data) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('public/tournaments/register', $data + [
            'pageTitle' => lang('Tournament.register'),
            'current_locale' => service('language')->getLocale(),
        ]);
    }

    public function submitRegistration(string $slug)
    {
        $tournament = $this->tournamentModel->findBySlug($slug);
        if (! $tournament) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'tenant_id' => (int) $tournament->tenant_id,
            'tournament_id' => (int) $tournament->id,
            'category_id' => (int) $this->request->getPost('category_id'),
            'contact_name' => $this->request->getPost('contact_name'),
            'contact_phone' => $this->request->getPost('contact_phone'),
            'contact_email' => $this->request->getPost('contact_email'),
            'note' => $this->request->getPost('note'),
        ];

        $category = model(TournamentCategoryModel::class)->find($data['category_id']);
        $result = $category && in_array($category->category_type, ['double_male', 'double_female', 'mixed_double', 'team_battle'], true)
            ? $this->registrationService->registerTeam($data + ['team_id' => $this->request->getPost('team_id') ?: null])
            : $this->registrationService->registerPlayer($data);

        return redirect()->to('/tournaments/' . $slug)->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function publicData(string $slug): ?array
    {
        $tournament = $this->tournamentModel->findBySlug($slug);
        if (! $tournament) {
            return null;
        }

        return [
            'tournament' => $tournament,
            'categories' => model(TournamentCategoryModel::class)->getByTournament((int) $tournament->id),
            'rule' => model(TournamentRuleModel::class)->where('tournament_id', $tournament->id)->first(),
            'sponsors' => model(TournamentSponsorModel::class)->getByTournament((int) $tournament->id),
            'registrations' => model(TournamentRegistrationModel::class)->getByTournament((int) $tournament->id),
            'localized' => fn (object $row, string $field) => $this->localized($row, $field),
        ];
    }

    private function localized(object $row, string $field): string
    {
        $locale = service('language')->getLocale();
        $value = $row->{$field . '_' . $locale} ?? null;
        return $value ?: ($row->{$field . '_vi'} ?? $row->{$field . '_en'} ?? '');
    }
}
