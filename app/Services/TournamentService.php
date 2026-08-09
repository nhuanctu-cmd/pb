<?php

namespace App\Services;

use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Models\TournamentRuleModel;
use App\Models\TournamentSponsorModel;

class TournamentService
{
    protected TournamentModel $tournamentModel;
    protected TournamentCategoryModel $categoryModel;
    protected TournamentRuleModel $ruleModel;
    protected TournamentSponsorModel $sponsorModel;

    public function __construct()
    {
        $this->tournamentModel = model(TournamentModel::class);
        $this->categoryModel = model(TournamentCategoryModel::class);
        $this->ruleModel = model(TournamentRuleModel::class);
        $this->sponsorModel = model(TournamentSponsorModel::class);
    }

    public function createTournament(array $data): array
    {
        $tenantId = (int) $data['tenant_id'];
        $data['slug_vi'] = $data['slug_vi'] ?: $this->tournamentModel->generateUniqueSlug($data['name_vi'], $tenantId);
        $data['slug_en'] = $data['slug_en'] ?: ($data['name_en'] ? $this->tournamentModel->generateUniqueSlug($data['name_en'], $tenantId) : null);
        $data['status'] = $data['status'] ?? 'draft';

        $db = \Config\Database::connect();
        $db->transStart();

        $tournamentId = $this->tournamentModel->insert($this->onlyTournamentFields($data));
        if (! $tournamentId) {
            $db->transRollback();
            return ['success' => false, 'message' => implode(', ', $this->tournamentModel->errors()) ?: 'Không tạo được giải đấu.'];
        }

        $this->syncChildren($tenantId, (int) $tournamentId, $data);
        $db->transComplete();

        if (! $db->transStatus()) {
            return ['success' => false, 'message' => 'Không tạo được giải đấu.'];
        }

        return ['success' => true, 'message' => 'Đã tạo giải đấu.', 'tournament' => $this->tournamentModel->find($tournamentId)];
    }

    public function updateTournament(int $id, array $data): array
    {
        $tournament = $this->tournamentModel->find($id);
        if (! $tournament) {
            return ['success' => false, 'message' => 'Không tìm thấy giải đấu.'];
        }

        $tenantId = (int) $tournament->tenant_id;
        $data['slug_vi'] = $data['slug_vi'] ?: $this->tournamentModel->generateUniqueSlug($data['name_vi'], $tenantId, $id);
        $data['slug_en'] = $data['slug_en'] ?: ($data['name_en'] ? $this->tournamentModel->generateUniqueSlug($data['name_en'], $tenantId, $id) : null);

        $db = \Config\Database::connect();
        $db->transStart();
        $this->tournamentModel->update($id, $this->onlyTournamentFields($data));
        $this->syncChildren($tenantId, $id, $data);
        $db->transComplete();

        if (! $db->transStatus()) {
            return ['success' => false, 'message' => 'Không cập nhật được giải đấu.'];
        }

        return ['success' => true, 'message' => 'Đã cập nhật giải đấu.', 'tournament' => $this->tournamentModel->find($id)];
    }

    public function openRegistration(int $id): array
    {
        return $this->setStatus($id, 'open', 'Đã mở đăng ký.');
    }

    public function closeRegistration(int $id): array
    {
        return $this->setStatus($id, 'closed', 'Đã đóng đăng ký.');
    }

    public function publishTournament(int $id): array
    {
        return $this->openRegistration($id);
    }

    public function cancelTournament(int $id): array
    {
        return $this->setStatus($id, 'cancelled', 'Đã hủy giải đấu.');
    }

    private function setStatus(int $id, string $status, string $message): array
    {
        $tournament = $this->tournamentModel->find($id);
        if (! $tournament) {
            return ['success' => false, 'message' => 'Không tìm thấy giải đấu.'];
        }

        $this->tournamentModel->update($id, ['status' => $status]);
        return ['success' => true, 'message' => $message, 'tournament' => $this->tournamentModel->find($id)];
    }

    private function syncChildren(int $tenantId, int $tournamentId, array $data): void
    {
        $this->categoryModel->where('tournament_id', $tournamentId)->delete();
        foreach (($data['categories'] ?? []) as $category) {
            if (empty($category['name_vi'])) {
                continue;
            }

            $this->categoryModel->insert([
                'tenant_id' => $tenantId,
                'tournament_id' => $tournamentId,
                'name_vi' => $category['name_vi'],
                'name_en' => $category['name_en'] ?? null,
                'category_type' => $category['category_type'] ?? 'single_male',
                'max_teams' => $category['max_teams'] ?: null,
                'min_rating' => $category['min_rating'] ?: null,
                'max_rating' => $category['max_rating'] ?: null,
                'registration_fee' => $category['registration_fee'] ?: 0,
                'status' => $category['status'] ?? 'active',
            ]);
        }

        $rule = $this->ruleModel->where('tournament_id', $tournamentId)->first();
        $ruleData = [
            'tenant_id' => $tenantId,
            'tournament_id' => $tournamentId,
            'rule_content_vi' => $data['rule_content_vi'] ?? null,
            'rule_content_en' => $data['rule_content_en'] ?? null,
        ];
        $rule ? $this->ruleModel->update($rule->id, $ruleData) : $this->ruleModel->insert($ruleData);

        $this->sponsorModel->where('tournament_id', $tournamentId)->delete();
        foreach (($data['sponsors'] ?? []) as $i => $sponsor) {
            if (empty($sponsor['sponsor_name'])) {
                continue;
            }

            $this->sponsorModel->insert([
                'tenant_id' => $tenantId,
                'tournament_id' => $tournamentId,
                'sponsor_name' => $sponsor['sponsor_name'],
                'logo' => $sponsor['logo'] ?? null,
                'website' => $sponsor['website'] ?? null,
                'sort_order' => $sponsor['sort_order'] ?? $i,
                'status' => $sponsor['status'] ?? 'active',
            ]);
        }
    }

    private function onlyTournamentFields(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'tenant_id', 'branch_id', 'name_vi', 'name_en', 'slug_vi', 'slug_en',
            'description_vi', 'description_en', 'banner', 'start_date', 'end_date',
            'registration_start', 'registration_end', 'max_teams', 'registration_fee', 'status',
        ]));
    }
}
