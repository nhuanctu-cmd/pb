<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\LivestreamChannelModel;
use App\Models\TournamentModel;

class LivestreamService
{
    private LivestreamChannelModel $channelModel;
    private BranchModel $branchModel;
    private TournamentModel $tournamentModel;

    public function __construct()
    {
        $this->channelModel = new LivestreamChannelModel();
        $this->branchModel = new BranchModel();
        $this->tournamentModel = new TournamentModel();
    }

    public function channels(int $tenantId): array
    {
        return $tenantId ? $this->channelModel->allForTenant($tenantId) : [];
    }

    public function publicChannels(int $tenantId): array
    {
        return $tenantId ? $this->channelModel->publicForTenant($tenantId) : [];
    }

    public function create(array $data, int $tenantId, ?int $userId = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $provider = (string) ($data['provider'] ?? 'custom');
        $streamUrl = trim((string) ($data['stream_url'] ?? ''));
        $embedUrl = trim((string) ($data['embed_url'] ?? ''));
        $branchId = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;
        $tournamentId = ! empty($data['tournament_id']) ? (int) $data['tournament_id'] : null;
        $status = (string) ($data['status'] ?? 'draft');

        if (! $tenantId || $name === '' || mb_strlen($name) > 180 || ! in_array($provider, ['youtube', 'facebook', 'custom'], true)
            || ! self::isSafeUrl($streamUrl) || ($embedUrl !== '' && ! self::isSafeUrl($embedUrl))
            || ! in_array($status, ['draft', 'scheduled'], true)
            || ($branchId && ! $this->branchModel->findForTenant($branchId, $tenantId))
            || ($tournamentId && ! $this->tournamentModel->findForTenant($tournamentId, $tenantId))) {
            return ['success' => false, 'message' => 'Thông tin livestream không hợp lệ hoặc không thuộc tenant.'];
        }

        $scheduledAt = self::normalizeDateTime((string) ($data['scheduled_at'] ?? ''));
        if ($status === 'scheduled' && (! $scheduledAt || ! self::isValidDateTime($scheduledAt))) {
            return ['success' => false, 'message' => 'Livestream đã lên lịch phải có thời gian hợp lệ.'];
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $id = $this->channelModel->insert([
            'tenant_id' => $tenantId,
            'branch_id' => $branchId,
            'tournament_id' => $tournamentId,
            'name' => $name,
            'provider' => $provider,
            'stream_url' => $streamUrl,
            'embed_url' => $embedUrl ?: null,
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        $db->transComplete();
        if (! $db->transStatus() || ! $id) {
            return ['success' => false, 'message' => 'Không thể tạo kênh livestream.'];
        }

        $this->audit('created', (int) $id, $tenantId, ['provider' => $provider, 'status' => $status]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã tạo kênh livestream.'];
    }

    public function updateStatus(int $id, string $status, int $tenantId, ?int $userId = null): array
    {
        if (! in_array($status, ['scheduled', 'live', 'ended', 'disabled'], true)) {
            return ['success' => false, 'message' => 'Trạng thái livestream không hợp lệ.'];
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $channel = $this->channelModel->findForUpdate($id, $tenantId);
        if (! $channel || ! self::canTransition((string) $channel->status, $status)) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Chuyển trạng thái livestream không hợp lệ.'];
        }

        $fields = ['status' => $status, 'updated_by' => $userId];
        if ($status === 'live') $fields['started_at'] = date('Y-m-d H:i:s');
        if ($status === 'ended') $fields['ended_at'] = date('Y-m-d H:i:s');
        if ($status === 'scheduled' && empty($channel->scheduled_at)) $fields['scheduled_at'] = date('Y-m-d H:i:s');
        $ok = $this->channelModel->update($id, $fields);
        $db->transComplete();
        if (! $db->transStatus() || ! $ok) return ['success' => false, 'message' => 'Không thể cập nhật livestream.'];

        $this->audit('status_changed', $id, $tenantId, ['from' => $channel->status, 'to' => $status, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã cập nhật trạng thái livestream.'];
    }

    public static function isSafeUrl(string $url): bool
    {
        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    public static function isValidDateTime(string $value): bool
    {
        return self::normalizeDateTime($value) !== null;
    }

    public static function normalizeDateTime(string $value): ?string
    {
        $value = trim(str_replace('T', ' ', $value));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $value);
        return $date !== false && $date->format('Y-m-d H:i') === $value ? $date->format('Y-m-d H:i:s') : null;
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, [
            'draft' => ['scheduled', 'disabled'],
            'scheduled' => ['live', 'disabled'],
            'live' => ['ended', 'disabled'],
            'ended' => [],
            'disabled' => ['draft'],
        ][$from] ?? [], true);
    }

    private function audit(string $action, int $id, int $tenantId, array $data): void
    {
        if (function_exists('log_audit')) log_audit([
            'action' => 'livestream_' . $action,
            'entity_type' => 'livestream_channel',
            'entity_id' => $id,
            'tenant_id' => $tenantId,
            'metadata' => $data,
        ]);
    }
}
