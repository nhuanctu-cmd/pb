<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TournamentModel;
use App\Models\TournamentTemplateModel;
use App\Services\TournamentTemplateService;

class TournamentTemplatesController extends BaseController
{
    private TournamentTemplateService $templates;

    public function __construct()
    {
        $this->templates = new TournamentTemplateService();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $templatePage = model(TournamentTemplateModel::class)->getByTenantPaginated($tenantId, (int) ($this->request->getGet('page') ?: 1), (int) ($this->request->getGet('per_page') ?: 12), trim((string) $this->request->getGet('search')));
        return $this->render('admin/tournament_templates/index', [
            'pageTitle' => 'Tournament Templates',
            'pageDescription' => 'Lưu cấu hình giải để mở lại trong vài phút mỗi tháng.',
            'templates' => $templatePage['items'],
            'templatePage' => $templatePage,
            'tournaments' => model(TournamentModel::class)->getByTenant($tenantId),
        ]);
    }

    public function saveFromTournament()
    {
        $currentUser = user();
        $result = $this->templates->saveFromTournament((int) $this->request->getPost('tournament_id'), (int) current_tenant_id(), (int) ($currentUser->id ?? 0));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function use(int $id)
    {
        $template = model(TournamentTemplateModel::class)->findForTenant($id, (int) current_tenant_id());
        if (! $template) {
            return redirect()->to('/admin/tournament-templates')->with('error', 'Không tìm thấy template.');
        }
        $snapshot = json_decode((string) $template->snapshot, true) ?: [];
        $sourceTournament = null;
        if (! empty($template->source_tournament_id)) {
            $sourceTournament = model(TournamentModel::class)->findForTenant((int) $template->source_tournament_id, (int) current_tenant_id());
        }
        $suggestedName = $this->buildSuggestedTournamentName((string) ($snapshot['name_vi'] ?? ($template->name ?? 'Giải đấu mẫu')));
        $defaultStart = $this->normalizeDate((string) $this->request->getGet('start_date'));
        $defaultEnd = $this->normalizeDate((string) $this->request->getGet('end_date'), $defaultStart);
        return $this->render('admin/tournament_templates/use', [
            'pageTitle' => 'Tạo giải từ template',
            'template' => $template,
            'snapshot' => $snapshot,
            'sourceTournament' => $sourceTournament,
            'suggestedName' => $suggestedName,
            'defaultStart' => $defaultStart,
            'defaultEnd' => $defaultEnd,
            'datePresets' => $this->buildDatePresets(),
        ]);
    }

    public function create(int $id)
    {
        $cleanDate = static function ($value): ?string {
            $value = trim((string) $value);
            return $value === '' ? null : str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
        };
        $result = $this->templates->createFromTemplate($id, (int) current_tenant_id(), [
            'name_vi' => trim((string) $this->request->getPost('name_vi')),
            'name_en' => $this->request->getPost('name_en') ?: null,
            'start_date' => $this->request->getPost('start_date') ?: null,
            'end_date' => $this->request->getPost('end_date') ?: null,
            'registration_start' => $cleanDate($this->request->getPost('registration_start')),
            'registration_end' => $cleanDate($this->request->getPost('registration_end')),
            'status' => $this->request->getPost('status') ?: 'draft',
            'open_registration_now' => (string) $this->request->getPost('open_registration_now') === '1',
        ]);
        if (! $result['success']) {
            return redirect()->back()->withInput()->with('error', $result['message']);
        }
        $tournament = $result['tournament'] ?? null;
        return redirect()->to('/admin/tournaments/show/' . ($tournament->id ?? 0))->with('success', 'Đã tạo giải mới từ template.');
    }

    private function buildSuggestedTournamentName(string $baseName): string
    {
        $monthText = date('m/Y');
        return trim($baseName) . ' · ' . $monthText;
    }

    private function normalizeDate(string $value, ?string $fallback = null): string
    {
        $value = trim($value);
        if ($value !== '') {
            return $value;
        }

        return $fallback ?: date('Y-m-d');
    }

    private function buildDatePresets(): array
    {
        return [
            'today' => date('Y-m-d'),
            'tomorrow' => date('Y-m-d', strtotime('+1 day')),
            'nextMonday' => date('Y-m-d', strtotime('next monday')),
            'nextSaturday' => date('Y-m-d', strtotime('next saturday')),
            'nextWeek' => date('Y-m-d', strtotime('+7 day')),
        ];
    }
}
