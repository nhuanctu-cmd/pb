<?php

namespace App\Services;

use App\Models\PlayerModel;
use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Models\TournamentRegistrationModel;
use Config\Database;
use App\Services\InvoiceService;
use App\Services\CustomerService;

class TournamentRegistrationService
{
    protected TournamentModel $tournamentModel;
    protected TournamentCategoryModel $categoryModel;
    protected TournamentRegistrationModel $registrationModel;
    protected PlayerModel $playerModel;
    protected InvoiceService $invoiceService;
    protected CustomerService $customerService;

    public function __construct()
    {
        $this->tournamentModel = model(TournamentModel::class);
        $this->categoryModel = model(TournamentCategoryModel::class);
        $this->registrationModel = model(TournamentRegistrationModel::class);
        $this->playerModel = model(PlayerModel::class);
        $this->invoiceService = new InvoiceService();
        $this->customerService = new CustomerService();
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

    public function registerAdmin(array $data): array
    {
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
        if (! $category || ! $tournament) {
            return ['success' => false, 'message' => 'Không tìm thấy hạng mục hoặc giải đấu của đăng ký.'];
        }

        $amount = (float) ($category->registration_fee ?? $tournament->registration_fee ?? 0);
        if ($amount <= 0) {
            $this->registrationModel->update($registrationId, [
                'invoice_code' => null,
                'invoice_amount' => 0,
            ]);
            return ['success' => true, 'message' => 'Không cần tạo invoice cho đăng ký miễn phí.', 'amount' => 0.0, 'invoice_id' => null];
        }

        $existingInvoice = $this->invoiceService->getInvoicesByRef('tournament_registration', (int) $registrationId, (int) $registration->tenant_id);
        if (! empty($existingInvoice)) {
            $currentInvoice = $existingInvoice[0];
            $this->registrationModel->update($registrationId, [
                'invoice_code' => (string) $currentInvoice->invoice_code,
                'invoice_amount' => (float) $currentInvoice->total_amount,
            ]);
            return [
                'success' => true,
                'message' => 'Invoice đã tồn tại cho hồ sơ đăng ký này.',
                'invoice_id' => (int) $currentInvoice->id,
                'invoice_code' => (string) $currentInvoice->invoice_code,
                'amount' => (float) $currentInvoice->total_amount,
            ];
        }

        $invoiceCode = 'TRN-' . date('ymd') . '-' . str_pad((string) $registrationId, 5, '0', STR_PAD_LEFT);
        $createdBy = property_exists($registration, 'created_by') ? $registration->created_by : null;
        $actorId = (int) ($createdBy ?: user_id() ?: 0);
        $customerPayload = [
            'player_id' => (int) ($registration->player_id ?: 0),
            'customer_name' => (string) ($registration->contact_name ?? ''),
            'customer_phone' => (string) ($registration->contact_phone ?? ''),
            'customer_email' => (string) ($registration->contact_email ?? ''),
        ];
        $customerId = null;
        if ($this->customerService->available()) {
            $resolvedCustomer = $this->customerService->resolveForBooking((int) $registration->tenant_id, $customerPayload, $actorId ?: null);
            if ($resolvedCustomer['success']) {
                $customerId = (int) $resolvedCustomer['customer_id'];
            }
        }

        try {
            $branchId = ! empty($tournament->branch_id) ? (int) $tournament->branch_id : null;
            $invoice = $this->invoiceService->createInvoice((int) $registration->tenant_id, $branchId, $invoiceCode, (float) $amount, [
                'customer_type' => ! empty($registration->player_id) ? 'player' : 'guest',
                'player_id' => ! empty($registration->player_id) ? (int) $registration->player_id : null,
                'ref_type' => 'tournament_registration',
                'ref_id' => (int) $registrationId,
                'created_by' => $actorId ?: null,
                'note' => 'Tournament registration #' . (int) $registration->id,
            ]);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Không thể tạo invoice cho hồ sơ đăng ký. ' . $exception->getMessage(),
                'code' => 'INVOICE_CREATE_ERROR',
            ];
        }

        $this->registrationModel->update($registrationId, [
            'invoice_code' => $invoice->invoice_code,
            'invoice_amount' => $amount,
        ]);

        if ($customerId) {
            $this->customerService->recordTimeline((int) $registration->tenant_id, $customerId, 'tournament_registration_invoiced', 'Tạo hóa đơn đăng ký giải', [
                'registration_id' => (int) $registrationId,
                'invoice_code' => $invoice->invoice_code,
                'invoice_id' => (int) $invoice->id,
                'amount' => $amount,
            ], 'Hệ thống tự tạo invoice khi đăng ký giải đấu.', $actorId ?: null, 'tournament_registration', (int) $registrationId);
        }

        return [
            'success' => true,
            'invoice_id' => (int) $invoice->id,
            'invoice_code' => (string) $invoice->invoice_code,
            'amount' => $amount,
        ];
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

        $partnerPlayerId = (int) ($data['partner_player_id'] ?? 0);
        if ($partnerPlayerId > 0) {
            $partner = $this->playerModel->where('id', $partnerPlayerId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
            if (! $partner) {
                return ['success' => false, 'message' => 'VĐV đánh cặp không thuộc tenant hiện tại.'];
            }
        }

        $playerIds = [];
        if (! empty($data['player_id'])) $playerIds[] = (int) $data['player_id'];
        if ($partnerPlayerId > 0) $playerIds[] = $partnerPlayerId;
        if (! empty($data['team_id']) && $this->registrationDb()->tableExists('team_members')) {
            foreach ($this->registrationDb()->table('team_members')->select('player_id')->where('team_id', (int) $data['team_id'])->where('tenant_id', $tenantId)->whereIn('status', ['accepted', 'active'])->where('deleted_at', null)->get()->getResult() as $member) $playerIds[] = (int) $member->player_id;
        }
        $playerIds = array_values(array_unique(array_filter($playerIds)));

        if (! $playerIds) {
            return ['success' => false, 'message' => 'Thiếu thông tin vận động viên.'];
        }

        if (! empty($data['team_id']) && $data['team_id']) {
            $teamDuplicate = $this->registrationModel->where('tenant_id', $tenantId)->where('category_id', (int) $data['category_id'])->where('team_id', (int) $data['team_id'])->whereIn('approval_status', ['pending', 'approved'])->where('deleted_at', null)->first();
            if ($teamDuplicate) {
                return ['success' => false, 'message' => 'Đội này đã có hồ sơ trong hạng mục này.'];
            }
        }

        $duplicateByPlayer = $this->registrationModel
            ->where('tenant_id', $tenantId)
            ->where('category_id', (int) $data['category_id'])
            ->whereIn('player_id', $playerIds)
            ->whereIn('approval_status', ['pending', 'approved'])
            ->where('deleted_at', null)
            ->groupStart()
                ->whereIn('registration_status', ['pending', 'confirmed'])
                ->orWhere('registration_status', null)
            ->groupEnd()
            ->first();
        if ($duplicateByPlayer) {
            return ['success' => false, 'message' => 'Một trong các vận động viên đã có trong hạng mục này.'];
        }

        if (! empty($data['partner_player_id']) && $this->registrationModel->where('tenant_id', $tenantId)->where('category_id', (int) $data['category_id'])->where('partner_player_id', $partnerPlayerId)->whereIn('approval_status', ['pending', 'approved'])->where('deleted_at', null)->first()) {
            return ['success' => false, 'message' => 'VĐV đánh cặp đã ở trong bài đăng ký khác trong cùng hạng mục.'];
        }

        $registrationLimit = (int) ($category->max_teams ?? 0);

        $rules = is_string($category->eligibility_rules ?? null) ? (json_decode($category->eligibility_rules, true) ?: []) : (array) ($category->eligibility_rules ?? []);
        $rules = array_merge(['policy' => 'STRICT', 'min_rating' => $category->min_rating, 'max_rating' => $category->max_rating, 'block_unrated' => false], $rules);
        $eligibility = $playerIds ? service('tournamentEligibilityService')->evaluate($tenantId, $playerIds, (string) ($category->discipline ?: 'singles'), $rules) : ['status' => 'flagged', 'eligible' => false, 'reasons' => [['code' => 'PLAYER_ID_REQUIRED']]];
        if (($eligibility['status'] ?? 'failed') === 'failed' && ! empty($rules['block_unrated'])) {
            return ['success' => false, 'message' => 'Đăng ký không đạt điều kiện rating/skill.', 'eligibility' => $eligibility];
        }
        $eligibilityStatus = ! empty($eligibility['eligible']) ? 'passed' : 'flagged';
        $quickApprove = (bool) ($data['quick_approve'] ?? false);
        $isFull = $registrationLimit > 0 && $this->registrationModel->countApprovedByCategory((int) $category->id, $tenantId) >= $registrationLimit;

        if ($quickApprove && ! $isFull && $eligibilityStatus === 'passed') {
            $approvalStatus = 'approved';
            $registrationStatus = 'confirmed';
            $waitlistPosition = null;
        } elseif ($isFull) {
            $approvalStatus = 'pending';
            $registrationStatus = 'waitlisted';
            $waitlistPosition = $this->registrationModel->getNextWaitlistPosition((int) $category->id, $tenantId);
        } else {
            $approvalStatus = 'pending';
            $registrationStatus = 'pending';
            $waitlistPosition = null;
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
            'approval_status' => $approvalStatus,
            'registration_status' => $registrationStatus,
            'eligibility_status' => $eligibilityStatus,
            'partner_player_id' => $partnerPlayerId ?: null,
            'waitlist_position' => $waitlistPosition,
            'note' => $data['note'] ?? null,
        ]);

        if (! $registrationId) {
            return ['success' => false, 'message' => 'Không gửi được đăng ký.'];
        }

        $invoice = $this->createRegistrationInvoice((int) $registrationId, (int) $tournament->tenant_id);

        return [
            'success' => true,
            'message' => match (true) {
                $isFull => 'Số lượng hạng mục đã kín, hồ sơ được đưa vào danh sách chờ.',
                $quickApprove && $eligibilityStatus === 'passed' => 'Đã thêm và duyệt nhanh vận động viên.',
                $eligibilityStatus === 'passed' => 'Đăng ký đã được gửi.',
                default => 'Đăng ký đã được gửi và cần ban tổ chức review eligibility.',
            },
            'registration' => $this->registrationModel->find($registrationId),
            'invoice' => $invoice,
            'eligibility' => $eligibility,
        ];
    }

    private function registrationDb()
    {
        return Database::connect();
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
