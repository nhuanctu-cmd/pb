<?php

namespace App\Services;

use App\Models\PlayerModel;
use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Models\TournamentRegistrationModel;

class TournamentRegistrationService
{
    protected TournamentModel $tournamentModel;
    protected TournamentCategoryModel $categoryModel;
    protected TournamentRegistrationModel $registrationModel;
    protected PlayerModel $playerModel;

    public function __construct()
    {
        $this->tournamentModel = model(TournamentModel::class);
        $this->categoryModel = model(TournamentCategoryModel::class);
        $this->registrationModel = model(TournamentRegistrationModel::class);
        $this->playerModel = model(PlayerModel::class);
    }

    public function registerPlayer(array $data): array
    {
        $data['player_id'] = $data['player_id'] ?? $this->findOrCreatePlayer($data);
        $data['team_id'] = null;
        return $this->createRegistration($data);
    }

    public function registerTeam(array $data): array
    {
        $data['player_id'] = $data['player_id'] ?? null;
        return $this->createRegistration($data);
    }

    public function approveRegistration(int $registrationId, ?int $tenantId = null): array
    {
        $builder = $this->registrationModel->where('id', $registrationId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        $registration = $builder->first();
        if (! $registration) {
            return ['success' => false, 'message' => 'Không tìm thấy đăng ký.'];
        }

        if (! $this->checkCategoryLimit((int) $registration->category_id, $tenantId ?? (int) $registration->tenant_id)) {
            return ['success' => false, 'message' => 'Hạng mục đã đủ số lượng.'];
        }

        $this->registrationModel->update($registrationId, ['approval_status' => 'approved']);
        return ['success' => true, 'message' => 'Đã duyệt đăng ký.'];
    }

    public function rejectRegistration(int $registrationId, ?string $note = null, ?int $tenantId = null): array
    {
        $builder = $this->registrationModel->where('id', $registrationId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        $registration = $builder->first();
        if (! $registration) {
            return ['success' => false, 'message' => 'Không tìm thấy đăng ký.'];
        }

        $this->registrationModel->update($registrationId, [
            'approval_status' => 'rejected',
            'note' => trim(($registration->note ? $registration->note . "\n" : '') . ($note ?? '')),
        ]);

        return ['success' => true, 'message' => 'Đã từ chối đăng ký.'];
    }

    public function checkCategoryLimit(int $categoryId, ?int $tenantId = null): bool
    {
        $categoryQuery = $this->categoryModel->where('id', $categoryId);
        if ($tenantId !== null) {
            $categoryQuery->where('tenant_id', $tenantId);
        }
        $category = $categoryQuery->first();
        if (! $category || empty($category->max_teams)) {
            return true;
        }

        return $this->registrationModel->countApprovedByCategory($categoryId, $tenantId) < (int) $category->max_teams;
    }

    public function createRegistrationInvoice(int $registrationId, ?int $tenantId = null): array
    {
        $builder = $this->registrationModel->where('id', $registrationId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        $registration = $builder->first();
        if (! $registration) {
            return ['success' => false, 'message' => 'Không tìm thấy đăng ký.'];
        }

        $category = $this->categoryModel->where('id', $registration->category_id)->where('tenant_id', $registration->tenant_id)->first();
        $tournament = $this->tournamentModel->findForTenant((int) $registration->tournament_id, (int) $registration->tenant_id);
        $amount = (float) ($category->registration_fee ?? $tournament->registration_fee ?? 0);
        $invoiceCode = 'TRN-' . date('ymd') . '-' . str_pad((string) $registrationId, 5, '0', STR_PAD_LEFT);

        $this->registrationModel->update($registrationId, [
            'invoice_code' => $invoiceCode,
            'invoice_amount' => $amount,
        ]);

        return ['success' => true, 'invoice_code' => $invoiceCode, 'amount' => $amount];
    }

    private function createRegistration(array $data): array
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $tournament = $this->tournamentModel->findForTenant((int) $data['tournament_id'], $tenantId);
        $category = $this->categoryModel->where('id', (int) $data['category_id'])->where('tenant_id', $tenantId)->first();

        if (! $tournament || ! $category || (int) $category->tournament_id !== (int) $tournament->id) {
            return ['success' => false, 'message' => 'Thông tin giải hoặc hạng mục không hợp lệ.'];
        }

        if ($tournament->status !== 'open') {
            return ['success' => false, 'message' => 'Giải đấu chưa mở đăng ký.'];
        }

        if ($tournament->registration_end && strtotime($tournament->registration_end) < time()) {
            return ['success' => false, 'message' => 'Đã hết hạn đăng ký.'];
        }

        if (! empty($data['player_id'])) {
            $player = $this->playerModel->where('id', $data['player_id'])->where('tenant_id', $tenantId)->first();
            if (! $player) {
                return ['success' => false, 'message' => 'Vận động viên không thuộc tenant.'];
            }
        }

        $registrationId = $this->registrationModel->insert([
            'tenant_id' => $tournament->tenant_id,
            'tournament_id' => $tournament->id,
            'category_id' => $category->id,
            'player_id' => $data['player_id'] ?? null,
            'team_id' => $data['team_id'] ?? null,
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'payment_status' => $data['payment_status'] ?? 'unpaid',
            'approval_status' => 'pending',
            'note' => $data['note'] ?? null,
        ]);

        if (! $registrationId) {
            return ['success' => false, 'message' => 'Không gửi được đăng ký.'];
        }

        $invoice = $this->createRegistrationInvoice((int) $registrationId, (int) $tournament->tenant_id);

        return [
            'success' => true,
            'message' => 'Đăng ký đã được gửi, vui lòng chờ duyệt.',
            'registration' => $this->registrationModel->find($registrationId),
            'invoice' => $invoice,
        ];
    }

    private function findOrCreatePlayer(array $data): int
    {
        $tenantId = (int) $data['tenant_id'];
        $phone = $data['contact_phone'] ?? null;

        if ($phone) {
            $player = $this->playerModel->where('tenant_id', $tenantId)
                ->where('phone', $phone)
                ->where('deleted_at', null)
                ->first();
            if ($player) {
                return (int) $player->id;
            }
        }

        return (int) $this->playerModel->insert([
            'tenant_id' => $tenantId,
            'full_name' => $data['contact_name'],
            'phone' => $phone,
            'email' => $data['contact_email'] ?? null,
            'status' => 'active',
        ]);
    }
}
