<?php

namespace App\Services;

use App\Models\MembershipModel;
use App\Models\MembershipPackageModel;
use App\Models\MembershipRenewalHistoryModel;

class MembershipService
{
    protected MembershipModel $membershipModel;
    protected MembershipPackageModel $packageModel;
    protected MembershipRenewalHistoryModel $historyModel;

    public function __construct()
    {
        $this->membershipModel = new MembershipModel();
        $this->packageModel    = new MembershipPackageModel();
        $this->historyModel   = new MembershipRenewalHistoryModel();
    }

    public function buyPackage(int $playerId, int $packageId, int $tenantId, ?int $createdBy = null): ?int
    {
        if ($playerId <= 0 || $packageId <= 0 || $tenantId <= 0) {
            return null;
        }
        $package = $this->packageModel->findForTenant($packageId, $tenantId);
        if (!$package || (int) $package->tenant_id !== $tenantId || $package->status !== 'active') {
            return null;
        }

        if ((int) $package->duration_days <= 0) {
            return null;
        }

        $player = model(\App\Models\PlayerModel::class)->find($playerId);
        if (!$player || (int) $player->tenant_id !== $tenantId) {
            return null;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Serialize membership replacement for this player and tenant.
        $db->query(
            'SELECT id FROM memberships WHERE player_id = ? AND tenant_id = ? AND status = ? AND deleted_at IS NULL FOR UPDATE',
            [$playerId, $tenantId, 'active']
        );
        $currentMembership = $this->membershipModel
            ->where('player_id', $playerId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->orderBy('end_date', 'DESC')
            ->first();

        if ($currentMembership !== null) {
            $this->membershipModel->update((int) $currentMembership->id, ['status' => 'cancelled']);
            $this->recordHistory(
                $tenantId,
                (int) $currentMembership->id,
                (int) $playerId,
                (int) $currentMembership->package_id,
                (int) $package->id,
                (string) $currentMembership->start_date,
                (string) $currentMembership->end_date,
                date('Y-m-d'),
                date('Y-m-d', strtotime('+'.$package->duration_days.' days')),
                'issued',
                $createdBy,
                'Mua gói mới: gói cũ được hủy.'
            );
        }

        // Create new membership
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime("+{$package->duration_days} days"));

        $data = [
            'tenant_id'  => $tenantId,
            'player_id'  => $playerId,
            'package_id' => $packageId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => 'active',
            'created_by' => $createdBy,
        ];

        $membershipId = $this->membershipModel->insert($data);
        if (!$membershipId) {
            $db->transRollback();
            return null;
        }

        $this->recordHistory(
            $tenantId,
            (int) $membershipId,
            $playerId,
            null,
            (int) $package->id,
            null,
            null,
            $startDate,
            $endDate,
            'issued',
            $createdBy,
            'Mua gói hội viên mới.'
        );

        $db->transComplete();
        if ($db->transStatus() === false) {
            $db->transRollback();
            return null;
        }

        return (int) $membershipId;
    }

    public function renew(int $membershipId, ?int $tenantId = null, ?int $packageId = null, ?int $actorUserId = null): ?int
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $membership = $this->membershipModel->findForUpdate($membershipId, $tenantId);
        if (!$membership) {
            $db->transRollback();
            return null;
        }

        $activeTenantId = (int) $membership->tenant_id;
        if ($packageId !== null) {
            $package = $this->packageModel->findForTenant((int) $packageId, $activeTenantId);
        } else {
            $package = $this->packageModel->findForTenant((int) $membership->package_id, $activeTenantId);
        }
        if (!$package) {
            $db->transRollback();
            return null;
        }

        // Create new membership starting from end date of current
        $startDate = max(date('Y-m-d'), $membership->end_date);
        $endDate   = date('Y-m-d', strtotime($startDate . " +{$package->duration_days} days"));

        // Mark old as expired
        $this->membershipModel->update($membershipId, ['status' => 'expired']);

        $data = [
            'tenant_id'  => $activeTenantId,
            'player_id'  => $membership->player_id,
            'package_id' => $package->id,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => 'active',
        ];

        $newId = $this->membershipModel->insert($data);
        if (! $newId) {
            $db->transRollback();
            return null;
        }

        $this->recordHistory(
            $activeTenantId,
            (int) $newId,
            (int) $membership->player_id,
            (int) $membership->package_id,
            (int) $package->id,
            (string) $membership->start_date,
            (string) $membership->end_date,
            $startDate,
            $endDate,
            'renewed',
            $actorUserId,
            'Gia hạn hội viên.'
        );
        $db->transComplete();
        return $db->transStatus() ? (int) $newId : null;
    }

    public function cancel(int $membershipId, ?int $tenantId = null): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $membership = $this->membershipModel->findForUpdate($membershipId, $tenantId);
        if (!$membership || $membership->status !== 'active') {
            $db->transRollback();
            return false;
        }

        $updated = $this->membershipModel->update($membershipId, ['status' => 'cancelled']);
        if ($updated) {
            $this->recordHistory(
                (int) $membership->tenant_id,
                $membershipId,
                (int) $membership->player_id,
                (int) $membership->package_id,
                (int) $membership->package_id,
                (string) $membership->start_date,
                (string) $membership->end_date,
                null,
                null,
                'cancelled',
                null,
                'Hủy hội viên thủ công.'
            );
        }
        $db->transComplete();
        return $updated && $db->transStatus();
    }

    public function checkActiveMembership(int $playerId, ?int $tenantId = null): bool
    {
        $membership = $this->membershipModel->getActiveByPlayer($playerId, $tenantId);
        return $membership !== null;
    }

    public function getActiveMembership(int $playerId, ?int $tenantId = null)
    {
        return $this->membershipModel->getActiveByPlayer($playerId, $tenantId);
    }

    public function getPlayerMemberships(int $playerId, ?int $tenantId = null)
    {
        return $this->membershipModel->getByPlayer($playerId, $tenantId);
    }

    public function getMemberships(int $tenantId, array $filters = [])
    {
        return $this->membershipModel->getByTenant($tenantId, $filters);
    }

    public function getRenewalCandidates(int $tenantId, int $days = 30): array
    {
        $db = \Config\Database::connect();
        return $db->table('memberships m')
            ->select('m.*, p.player_code, p.full_name, p.phone, mp.name_vi AS package_name_vi, mp.name_en AS package_name_en, mp.price, DATEDIFF(m.end_date, CURDATE()) AS remaining_days')
            ->join('players p', 'p.id = m.player_id', 'left')
            ->join('membership_packages mp', 'mp.id = m.package_id AND mp.tenant_id = m.tenant_id', 'left')
            ->where('m.tenant_id', $tenantId)->where('m.deleted_at', null)->whereIn('m.status', ['active', 'expired'])
            ->where('m.end_date <=', date('Y-m-d', strtotime('+' . max(0, $days) . ' days')))
            ->orderBy('m.end_date', 'ASC')->get()->getResult();
    }

    public function getRenewalCandidatesFiltered(
        int $tenantId,
        int $days = 30,
        ?int $packageId = null,
        string $status = '',
        string $search = ''
    ): array {
        $db = \Config\Database::connect();
        $query = $db->table('memberships m')
            ->select('m.*, p.player_code, p.full_name, p.phone, mp.name_vi AS package_name_vi, mp.name_en AS package_name_en, mp.price, DATEDIFF(m.end_date, CURDATE()) AS remaining_days')
            ->join('players p', 'p.id = m.player_id', 'left')
            ->join('membership_packages mp', 'mp.id = m.package_id AND mp.tenant_id = m.tenant_id', 'left')
            ->where('m.tenant_id', $tenantId)
            ->where('m.deleted_at', null)
            ->whereIn('m.status', ['active', 'expired'])
            ->where('m.end_date <=', date('Y-m-d', strtotime('+' . max(0, $days) . ' days')));

        if ($status === 'active' || $status === 'expired') {
            $query->where('m.status', $status);
        }

        if ($packageId !== null && $packageId > 0) {
            $query->where('m.package_id', $packageId);
        }

        if ($search !== '') {
            $query->groupStart()
                ->like('p.full_name', $search)
                ->orLike('p.phone', $search)
                ->orLike('p.player_code', $search)
                ->groupEnd();
        }

        return $query->orderBy('m.end_date', 'ASC')->get()->getResult();
    }

    public function bulkRenew(int $tenantId, array $membershipIds, ?int $packageId = null): array
    {
        $requested = array_unique(array_map('intval', $membershipIds));
        $successIds = [];
        $failedIds = [];
        foreach ($requested as $membershipId) {
            if ($membershipId <= 0) {
                continue;
            }
            $newId = $this->renew((int) $membershipId, $tenantId, $packageId);
            if ($newId) {
                $successIds[] = (int) $membershipId;
            } else {
                $failedIds[] = (int) $membershipId;
            }
        }

        return [
            'requested' => count($requested),
            'success' => count($successIds),
            'failed' => count($failedIds),
            'successIds' => $successIds,
            'failedIds' => $failedIds,
        ];
    }

    public function getPackages(int $tenantId)
    {
        return $this->packageModel->getActiveByTenant($tenantId);
    }

    public function getAllPackages(int $tenantId)
    {
        return $this->packageModel->getByTenant($tenantId);
    }

    public function createPackage(array $data): ?int
    {
        if ((int) ($data['tenant_id'] ?? 0) <= 0 || (int) ($data['duration_days'] ?? 0) <= 0 || (float) ($data['price'] ?? 0) < 0) {
            return null;
        }
        $id = $this->packageModel->insert($data);
        return $id ? (int) $id : null;
    }

    public function updatePackage(int $id, array $data, ?int $tenantId = null): bool
    {
        if ($tenantId !== null && ! $this->packageModel->findForTenant($id, $tenantId)) {
            return false;
        }
        return $this->packageModel->update($id, $data);
    }

    public function deletePackage(int $id, ?int $tenantId = null): bool
    {
        if ($tenantId !== null && ! $this->packageModel->findForTenant($id, $tenantId)) {
            return false;
        }
        return $this->packageModel->delete($id);
    }

    public function getPackageById(int $id, ?int $tenantId = null)
    {
        return $tenantId === null ? $this->packageModel->find($id) : $this->packageModel->findForTenant($id, $tenantId);
    }

    public function expireOverdueMemberships(): int
    {
        return $this->membershipModel->expireOverdue();
    }

    public function getRenewalHistory(int $tenantId, ?int $membershipId = null, int $limit = 30): array
    {
        $builder = \Config\Database::connect()->table('membership_renewal_histories h')
            ->select(
                'h.*, p.full_name AS player_name, p.phone AS player_phone, p.player_code, '
                . 'pb.name_vi AS package_before_name_vi, pb.name_en AS package_before_name_en, '
                . 'pa.name_vi AS package_after_name_vi, pa.name_en AS package_after_name_en, '
                . 'u.full_name AS actor_name'
            )
            ->join('players p', 'p.id = h.player_id', 'left')
            ->join('users u', 'u.id = h.actor_user_id', 'left')
            ->join('membership_packages pb', 'pb.id = h.package_id_before AND pb.tenant_id = h.tenant_id', 'left')
            ->join('membership_packages pa', 'pa.id = h.package_id_after AND pa.tenant_id = h.tenant_id', 'left')
            ->where('h.tenant_id', $tenantId)
            ->orderBy('h.created_at', 'DESC');

        if ($membershipId > 0) {
            $builder->where('h.membership_id', $membershipId);
        }

        return $builder->limit($limit)->get()->getResult();
    }

    public function sendReminder(int $membershipId, int $tenantId, string $channel = 'sms', bool $testMode = false, ?string $recipientOverride = null, ?int $actorUserId = null, ?string $messageTemplate = null): array
    {
        $membership = $this->membershipModel
            ->select('m.*, p.full_name, p.phone, p.email, p.user_id')
            ->join('players p', 'p.id = m.player_id', 'left')
            ->where('m.id', $membershipId)
            ->where('m.tenant_id', $tenantId)
            ->where('m.deleted_at', null)
            ->first();
        if (! $membership) {
            return ['success' => false, 'message' => 'Không tìm thấy hồ sơ hội viên.'];
        }

        $payload = $this->buildReminderPayload((int) $tenantId, $membership, $messageTemplate, $channel);
        $target = $this->resolveReminderTarget($channel, $membership, $recipientOverride);
        if (! $target) {
            return [
                'success' => false,
                'message' => match ($channel) {
                    'email' => 'Hội viên chưa có email để gửi nhắc gia hạn.',
                    'zalo' => 'Hội viên chưa có số Zalo.',
                    default => 'Hội viên chưa có số điện thoại để gửi nhắc nhở.',
                },
            ];
        }

        $payload['target'] = $target;
        $sendResult = $this->mockDelivery((string) $channel, $payload, $testMode);
        if (! $testMode) {
            $this->recordHistory(
                (int) $tenantId,
                (int) $membership->id,
                (int) $membership->player_id,
                (int) $membership->package_id,
                (int) $membership->package_id,
                (string) $membership->start_date,
                (string) $membership->end_date,
                null,
                null,
                'reminder',
                $actorUserId,
                trim((string) $sendResult['message'])
            );
        } else {
            log_message('info', '[MembershipReminder:TEST] dry-run for tenant=' . $tenantId . ', membership=' . $membership->id . ', target=' . $target);
        }

        return [
            'success' => (bool) $sendResult['ok'],
            'message' => $sendResult['message'],
            'target' => $target,
        ];
    }

    public function sendBulkReminders(
        array $membershipIds,
        int $tenantId,
        string $channel = 'sms',
        bool $testMode = false,
        ?string $recipientOverride = null,
        ?int $actorUserId = null,
        ?string $messageTemplate = null
    ): array {
        $result = [
            'requested' => 0,
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $ids = array_values(array_filter(array_unique(array_map('intval', $membershipIds))));
        $result['requested'] = count($ids);

        foreach ($ids as $membershipId) {
            if ($membershipId <= 0) {
                continue;
            }
            $reminder = $this->sendReminder(
                (int) $membershipId,
                $tenantId,
                $channel,
                $testMode,
                $recipientOverride,
                $actorUserId,
                $messageTemplate
            );
            if ($reminder['success']) {
                $result['success']++;
            } else {
                $result['failed']++;
                $result['errors'][] = (string) ($reminder['message'] ?? 'Gửi lỗi không rõ.');
            }
        }

        return $result;
    }

    public function reminderTemplateDefaults(): array
    {
        return [
            'sms' => 'Chào {{name}}, gói hội viên {{package_name}} của bạn sẽ hết hạn vào ngày {{expiry_date}} (còn {{days_left}} ngày). Vui lòng gia hạn tại quầy để không gián đoạn trải nghiệm.',
            'zalo' => 'Thông báo nhắc nhở gia hạn: {{name}}, gói hội viên {{package_name}} hết hạn {{expiry_date}} (còn {{days_left}} ngày). Gia hạn ngay để tiếp tục đặt sân.',
            'email' => 'Xin chào {{name}},\nGói hội viên {{package_name}} của bạn đang sắp hết hạn vào ngày {{expiry_date}} (còn {{days_left}} ngày).\nVui lòng gia hạn để tiếp tục tận hưởng ưu đãi.'
        ];
    }

    private function buildReminderPayload(int $tenantId, object $membership, ?string $templateOverride = null, string $channel = 'sms'): array
    {
        $packageName = '';
        if (! empty($membership->package_id) && (int) $membership->package_id > 0) {
            $package = $this->packageModel->find((int) $membership->package_id);
            if ($package) {
                $packageName = (string) ($package->name_vi ?: $package->name_en);
            }
        }

        $defaults = $this->reminderTemplateDefaults();
        $defaultsForChannel = $defaults[$channel] ?? $defaults['sms'];
        $messageTemplate = trim((string) $templateOverride) !== '' ? (string) $templateOverride : $defaultsForChannel;
        $expiryDate = (string) ($membership->end_date ?? '');
        $daysLeft = 0;
        if ($expiryDate !== '') {
            $daysLeft = max(0, (int) floor((strtotime($expiryDate) - strtotime(date('Y-m-d'))) / 86400));
        }

        $vars = [
            'name' => (string) ($membership->full_name ?? 'Bạn'),
            'phone' => (string) ($membership->phone ?? ''),
            'email' => (string) ($membership->email ?? ''),
            'package_name' => $packageName,
            'expiry_date' => $expiryDate,
            'days_left' => (string) $daysLeft,
            'tenant_id' => (string) $tenantId,
            'renewal_link' => base_url('/admin/memberships/renewals'),
        ];
        $message = $this->applyTemplate((string) $messageTemplate, $vars);

        return [
            'tenant_id' => $tenantId,
            'membership_id' => (int) $membership->id,
            'name' => $vars['name'],
            'phone' => $vars['phone'],
            'email' => $vars['email'],
            'message' => $message,
            'days_left' => $daysLeft,
            'expiry_date' => $expiryDate,
        ];
    }

    private function resolveReminderTarget(string $channel, object $membership, ?string $recipientOverride = null): ?string
    {
        $channel = strtolower((string) $channel);
        if ($recipientOverride !== null && trim($recipientOverride) !== '') {
            $normalized = trim($recipientOverride);
            if ($channel === 'email' && filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                return $normalized;
            }
            if (in_array($channel, ['sms', 'zalo'], true)) {
                return $normalized;
            }
        }

        if ($channel === 'email') {
            return trim((string) ($membership->email ?? '')) !== '' ? trim((string) $membership->email) : null;
        }

        return trim((string) ($membership->phone ?? '')) !== '' ? trim((string) $membership->phone) : null;
    }

    private function mockDelivery(string $channel, array $payload, bool $testMode): array
    {
        $channel = strtolower(trim($channel));
        $name = (string) ($payload['name'] ?? '');
        $target = (string) ($payload['target'] ?? '');
        if ($target === '') {
            return ['ok' => false, 'message' => 'Không có thông tin kênh gửi.'];
        }

        if ($channel === 'email') {
            log_message('info', '[MembershipReminder:' . ($testMode ? 'TEST' : 'LIVE') . '] EMAIL -> ' . $target . ' | ' . $name . ' | ' . (string) $payload['message']);
            return ['ok' => true, 'message' => 'Đã đưa message Email vào hàng đợi mock.'];
        }

        if ($channel === 'zalo') {
            log_message('info', '[MembershipReminder:' . ($testMode ? 'TEST' : 'LIVE') . '] ZALO -> ' . $target . ' | ' . $name . ' | ' . (string) $payload['message']);
            return ['ok' => true, 'message' => 'Đã đưa message Zalo vào hàng đợi mock.'];
        }

        log_message('info', '[MembershipReminder:' . ($testMode ? 'TEST' : 'LIVE') . '] SMS -> ' . $target . ' | ' . $name . ' | ' . (string) $payload['message']);
        return ['ok' => true, 'message' => 'Đã đưa message SMS vào hàng đợi mock.'];
    }

    private function recordHistory(
        int $tenantId,
        int $membershipId,
        int $playerId,
        ?int $packageBefore,
        ?int $packageAfter,
        ?string $startBefore,
        ?string $endBefore,
        ?string $startAfter,
        ?string $endAfter,
        string $action,
        ?int $actorUserId = null,
        ?string $notes = null
    ): void {
        $payload = [
            'tenant_id' => $tenantId,
            'membership_id' => $membershipId,
            'player_id' => $playerId,
            'package_id_before' => $packageBefore > 0 ? $packageBefore : null,
            'package_id_after' => $packageAfter > 0 ? $packageAfter : null,
            'start_date_before' => $startBefore,
            'end_date_before' => $endBefore,
            'start_date_after' => $startAfter,
            'end_date_after' => $endAfter,
            'action' => $action,
            'actor_user_id' => $actorUserId,
            'notes' => $notes,
        ];

        $this->historyModel->insert($payload);
    }

    private function applyTemplate(string $template, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', static function ($match) use ($vars) {
            $key = (string) $match[1];
            return array_key_exists($key, $vars) ? (string) $vars[$key] : $match[0];
        }, $template);
    }
}
