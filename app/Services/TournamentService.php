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
        $branchId = (int) ($data['branch_id'] ?? 0);
        $branch = model(\App\Models\BranchModel::class)
            ->where('id', $branchId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
        if ($tenantId <= 0 || ! $branch) {
            return ['success' => false, 'message' => 'Chi nhánh không thuộc tenant hiện tại.'];
        }
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

        return ['success' => true, 'message' => 'Đã tạo giải đấu.', 'tournament' => $this->tournamentModel->findForTenant((int) $tournamentId, $tenantId)];
    }

    public function updateTournament(int $id, array $data, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? (int) ($data['tenant_id'] ?? 0);
        $tournament = $this->tournamentModel->findForTenant($id, $tenantId);
        if (! $tournament) {
            return ['success' => false, 'message' => 'Không tìm thấy giải đấu.'];
        }

        $branchId = (int) ($data['branch_id'] ?? $tournament->branch_id);
        if (! model(\App\Models\BranchModel::class)->where('id', $branchId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first()) {
            return ['success' => false, 'message' => 'Chi nhánh không thuộc tenant hiện tại.'];
        }
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

        return ['success' => true, 'message' => 'Đã cập nhật giải đấu.', 'tournament' => $this->tournamentModel->findForTenant($id, $tenantId)];
    }

    public function openRegistration(int $id, ?int $tenantId = null): array
    {
        return $this->setStatus($id, 'open', 'Đã mở đăng ký.', $tenantId);
    }

    public function closeRegistration(int $id, ?int $tenantId = null): array
    {
        return $this->setStatus($id, 'closed', 'Đã đóng đăng ký.', $tenantId);
    }

    public function publishTournament(int $id, ?int $tenantId = null): array
    {
        return $this->openRegistration($id, $tenantId);
    }

    public function cancelTournament(int $id, ?int $tenantId = null): array
    {
        return $this->setStatus($id, 'cancelled', 'Đã hủy giải đấu.', $tenantId);
    }

    private function setStatus(int $id, string $status, string $message, ?int $tenantId = null): array
    {
        if ($tenantId === null) {
            $tournament = $this->tournamentModel->find($id);
            $tenantId = $tournament ? (int) $tournament->tenant_id : null;
        } else {
            $tournament = $this->tournamentModel->findForTenant($id, $tenantId);
        }
        if (! $tournament) {
            return ['success' => false, 'message' => 'Không tìm thấy giải đấu.'];
        }

        $allowed = [
            'draft' => ['open', 'cancelled'], 'open' => ['closed', 'running', 'cancelled'],
            'closed' => ['running', 'cancelled'], 'running' => ['completed', 'cancelled'],
            'completed' => [], 'cancelled' => [],
        ];
        if (! in_array($status, $allowed[$tournament->status] ?? [], true)) {
            return ['success' => false, 'message' => 'Trạng thái giải đấu không hợp lệ.'];
        }
        $db = \Config\Database::connect();
        $db->transStart();
        $locked = $this->tournamentModel->findForUpdate($id, (int) $tenantId);
        if (! $locked || ! in_array($status, $allowed[$locked->status] ?? [], true)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Trạng thái giải đấu không hợp lệ.'];
        }
        $this->tournamentModel->update($id, ['status' => $status]);
        $db->transComplete();
        if (! $db->transStatus()) {
            return ['success' => false, 'message' => 'Không cập nhật được trạng thái giải đấu.'];
        }
        return ['success' => true, 'message' => $message, 'tournament' => $this->tournamentModel->findForTenant($id, (int) $tenantId)];
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
