<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;
use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Services\TournamentRegistrationService;

class Tournaments extends BaseController
{
    protected TournamentModel $tournamentModel;
    protected TournamentRegistrationService $registrationService;
    protected \App\Services\PublicTournamentService $publicTournamentService;

    public function __construct()
    {
        $this->tournamentModel = model(TournamentModel::class);
        $this->registrationService = new TournamentRegistrationService();
        $this->publicTournamentService = new \App\Services\PublicTournamentService();
    }

    public function list()
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $status = (string) ($this->request->getGet('status') ?: 'all');
        $search = trim((string) ($this->request->getGet('q') ?: ''));
        $public = $this->publicTournamentService->list($tenantId, ['status' => $status, 'search' => $search]);

        return view('public/tournaments/list', [
            'pageTitle' => lang('Tournament.public_title'),
            'current_locale' => service('language')->getLocale(),
            'tournaments' => $public['tournaments'],
            'counts' => $public['counts'],
            'featured' => $public['featured'],
            'active_status' => $status,
            'search' => $search,
        ]);
    }

    public function detail(string $slug)
    {
        $data = $this->publicData($slug, (int) (current_tenant_id() ?: 1));
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
        $data = $this->publicData($slug, (int) (current_tenant_id() ?: 1));
        if (! $data) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('public/tournaments/register', $data + [
            'pageTitle' => lang('Tournament.register'),
            'current_locale' => service('language')->getLocale(),
        ]);
    }

    public function tv(string $slug)
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $tournament = $this->tournamentModel->findBySlug($slug, $tenantId);
        if (! $tournament) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return view('public/live_scores/tv', [
            'pageTitle' => $this->localized($tournament, 'name') . ' · TV',
            'data' => service('liveScoreService')->getTvDisplayData((int) $tournament->tenant_id, (int) $tournament->id),
        ]);
    }

    public function submitRegistration(string $slug)
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $tournament = $this->tournamentModel->findBySlug($slug, $tenantId);
        if (! $tournament) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $data = [
            'tenant_id' => (int) $tournament->tenant_id,
            'tournament_id' => (int) $tournament->id,
            'category_id' => (int) $this->request->getPost('category_id'),
            'player_id' => $this->request->getPost('player_id') ?: null,
            'partner_player_id' => $this->request->getPost('partner_player_id') ?: null,
            'contact_name' => $this->request->getPost('contact_name'),
            'contact_phone' => $this->request->getPost('contact_phone'),
            'contact_email' => $this->request->getPost('contact_email'),
            'note' => $this->request->getPost('note'),
        ];

        $category = model(TournamentCategoryModel::class)->findForTenant($data['category_id'], (int) $tournament->tenant_id);
        if (! $category || (int) $category->tournament_id !== (int) $tournament->id) {
            return redirect()->to('/tournaments/' . $slug)->with('error', 'Hạng mục đăng ký không thuộc giải đấu này.');
        }
        $result = $category && in_array($category->category_type, ['double_male', 'double_female', 'mixed_double', 'team_battle'], true)
            ? $this->registrationService->registerTeam($data + ['team_id' => $this->request->getPost('team_id') ?: null])
            : $this->registrationService->registerPlayer($data);

        return redirect()->to('/tournaments/' . $slug)->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    private function publicData(string $slug, int $tenantId): ?array
    {
        $tournament = $this->tournamentModel->findBySlug($slug, $tenantId);
        if (! $tournament) {
            return null;
        }

        return [
            'tournament' => $tournament,
            ...$this->publicTournamentService->detail($tournament, $tenantId),
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
