<?php

namespace App\Services;

use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Models\TournamentRuleModel;
use App\Models\TournamentSponsorModel;
use App\Models\TournamentTemplateModel;

class TournamentTemplateService
{
    private TournamentTemplateModel $templateModel;
    private TournamentModel $tournamentModel;

    public function __construct()
    {
        $this->templateModel = model(TournamentTemplateModel::class);
        $this->tournamentModel = model(TournamentModel::class);
    }

    public function saveFromTournament(int $tournamentId, int $tenantId, ?int $userId = null): array
    {
        $tournament = $this->tournamentModel->findForTenant($tournamentId, $tenantId);
        if (! $tournament) {
            return ['success' => false, 'message' => 'Không tìm thấy giải đấu.'];
        }

        $rule = model(TournamentRuleModel::class)->where('tournament_id', $tournamentId)->first();
        $snapshot = [
            'branch_id' => (int) $tournament->branch_id,
            'name_vi' => $tournament->name_vi,
            'name_en' => $tournament->name_en,
            'description_vi' => $tournament->description_vi,
            'description_en' => $tournament->description_en,
            'banner' => $tournament->banner,
            'max_teams' => $tournament->max_teams,
            'registration_fee' => $tournament->registration_fee,
            'categories' => array_map(static fn (object $category): array => [
                'name_vi' => $category->name_vi,
                'name_en' => $category->name_en,
                'category_type' => $category->category_type,
                'max_teams' => $category->max_teams,
                'min_rating' => $category->min_rating,
                'max_rating' => $category->max_rating,
                'registration_fee' => $category->registration_fee,
                'status' => $category->status,
            ], model(TournamentCategoryModel::class)->getByTournament($tournamentId)),
            'rule_content_vi' => $rule->rule_content_vi ?? null,
            'rule_content_en' => $rule->rule_content_en ?? null,
            'sponsors' => array_map(static fn (object $sponsor): array => [
                'sponsor_name' => $sponsor->sponsor_name,
                'logo' => $sponsor->logo,
                'website' => $sponsor->website,
                'sort_order' => $sponsor->sort_order,
                'status' => $sponsor->status,
            ], model(TournamentSponsorModel::class)->getByTournament($tournamentId)),
        ];

        $id = $this->templateModel->insert([
            'tenant_id' => $tenantId,
            'source_tournament_id' => $tournamentId,
            'name' => $tournament->name_vi . ' · Template',
            'description' => 'Mẫu tạo từ ' . $tournament->name_vi,
            'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'created_by' => $userId,
        ]);

        return $id
            ? ['success' => true, 'message' => 'Đã lưu giải đấu thành template.']
            : ['success' => false, 'message' => 'Không lưu được template.'];
    }

    public function createFromTemplate(int $templateId, int $tenantId, array $overrides): array
    {
        $template = $this->templateModel->findForTenant($templateId, $tenantId);
        if (! $template) {
            return ['success' => false, 'message' => 'Không tìm thấy template.'];
        }

        $snapshot = json_decode((string) $template->snapshot, true) ?: [];
        $payload = array_merge($snapshot, $overrides, [
            'tenant_id' => $tenantId,
            'status' => $overrides['status'] ?? 'draft',
            'slug_vi' => null,
            'slug_en' => null,
            'start_date' => $overrides['start_date'] ?? null,
            'end_date' => $overrides['end_date'] ?? null,
            'registration_start' => $overrides['registration_start'] ?? null,
            'registration_end' => $overrides['registration_end'] ?? null,
        ]);

        return (new TournamentService())->createTournament($payload);
    }
}
