<?php

namespace App\Services;

use App\Services\NotificationService;
use CodeIgniter\Database\BaseBuilder;

class CrmCampaignService
{
    private \App\Models\CrmCampaignModel $campaigns;
    private bool $hasScheduledAt = false;
    private bool $hasSentAt = false;
    private bool $hasThrottlePerMinute = false;
    private bool $hasMaxRetries = false;
    private bool $hasAttempts = false;
    private bool $hasNextRetryAt = false;
    private bool $hasLastAttemptAt = false;
    private bool $hasRecipientSentAt = false;
    private bool $hasRecipientErrorMessage = false;
    private bool $hasSegmentEnum = false;
    private ?array $campaignSegments = null;

    private array $segments = [
        'all' => [
            'label' => 'Tất cả khách hàng hoạt động',
            'description' => 'Tất cả khách hàng đang ở trạng thái active hoặc có điểm tiếp cận gần nhất.',
        ],
        'expiring_membership' => [
            'label' => 'Sắp hết hạn hội viên',
            'description' => 'Hội viên còn hiệu lực nhưng hết hạn trong 30 ngày tới.',
        ],
        'inactive' => [
            'label' => 'Không hoạt động gần đây',
            'description' => 'Không ghé lại trong ít nhất 30 ngày.',
        ],
        'recently_active' => [
            'label' => 'Hoạt động gần đây',
            'description' => 'Có lần ghé/thăm trong 7 ngày gần nhất.',
        ],
        'tournament_participants' => [
            'label' => 'Người tham gia giải gần đây',
            'description' => 'Khách có đăng ký giải trong 30 ngày gần đây.',
        ],
        'high_value' => [
            'label' => 'Khách giá trị cao',
            'description' => 'Chi tiêu lũy kế từ 5.000.000 trở lên.',
        ],
        'manual' => [
            'label' => 'Tùy chọn thủ công',
            'description' => 'Dùng cho import/đánh thủ công sau khi có danh sách từ marketing.',
        ],
    ];

    public function __construct()
    {
        $this->campaigns = new \App\Models\CrmCampaignModel();
        $db = \Config\Database::connect();
        $this->hasScheduledAt = $db->fieldExists('scheduled_at', 'crm_campaigns');
        $this->hasSentAt = $db->fieldExists('sent_at', 'crm_campaigns');
        $this->hasThrottlePerMinute = $db->fieldExists('throttle_per_minute', 'crm_campaigns');
        $this->hasMaxRetries = $db->fieldExists('max_retries', 'crm_campaigns');
        $this->hasAttempts = $db->fieldExists('attempts', 'crm_campaign_recipients');
        $this->hasNextRetryAt = $db->fieldExists('next_retry_at', 'crm_campaign_recipients');
        $this->hasLastAttemptAt = $db->fieldExists('last_attempt_at', 'crm_campaign_recipients');
        $this->hasRecipientSentAt = $db->fieldExists('sent_at', 'crm_campaign_recipients');
        $this->hasRecipientErrorMessage = $db->fieldExists('error_message', 'crm_campaign_recipients');
        $this->hasSegmentEnum = $this->isSegmentColumnEnum();
        $this->campaignSegments = $this->loadCampaignSegments();
    }

    public function list(int $tenantId): array
    {
        return $this->campaigns->where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->findAll();
    }

    public function createDraft(int $tenantId, array $data, ?int $userId): ?int
    {
        $data['tenant_id'] = $tenantId;
        $data['status'] = 'draft';
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $data['segment'] = $this->normalizeSegment((string) ($data['segment'] ?? 'all'));
        $data['segment'] = $this->normalizeDbSegment((string) $data['segment']);
        if ($this->hasThrottlePerMinute) {
            $data['throttle_per_minute'] = max(1, min(500, (int) ($data['throttle_per_minute'] ?? 60)));
        } else {
            unset($data['throttle_per_minute']);
        }

        if ($this->hasMaxRetries) {
            $data['max_retries'] = max(1, min(10, (int) ($data['max_retries'] ?? 3)));
        } else {
            unset($data['max_retries']);
        }
        if (! $this->hasScheduledAt) {
            unset($data['scheduled_at']);
        }
        if (($data['scheduled_at'] ?? '') === '') {
            unset($data['scheduled_at']);
        }

        $id = $this->campaigns->insert($data);
        return $id ? (int) $id : null;
    }

    public function segmentOptions(): array
    {
        $allowed = $this->campaignSegments ?? array_keys($this->segments);
        $fallback = array_keys($this->segments);
        $merged = array_values(array_unique(array_merge($allowed, $fallback)));

        $filtered = [];
        foreach ($this->segments as $key => $option) {
            if (in_array($key, $merged, true)) {
                $filtered[$key] = $option;
            }
        }
        return $filtered;
    }

    public function recipients(int $tenantId, int $campaignId): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('crm_campaign_recipients')) {
            return [];
        }

        return $db->table('crm_campaign_recipients r')
            ->select('r.*, c.full_name, c.phone, c.email')
            ->join('customers c', 'c.id = r.customer_id', 'left')
            ->where('r.tenant_id', $tenantId)
            ->where('r.campaign_id', $campaignId)
            ->orderBy('c.full_name', 'ASC')
            ->get()
            ->getResult();
    }

    public function launch(int $tenantId, int $campaignId, ?int $userId): int
    {
        return $this->run($tenantId, $campaignId, $userId, false);
    }

    public function retry(int $tenantId, int $campaignId, ?int $userId): int
    {
        return $this->run($tenantId, $campaignId, $userId, true);
    }

    public function dispatchDue(int $tenantId, ?int $userId = null): int
    {
        $db = \Config\Database::connect();
        if (! $this->dbHasCampaignTables()) {
            return -1;
        }

        $now = date('Y-m-d H:i:s');
        $query = $db->table('crm_campaigns')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['draft', 'scheduled', 'running'])
            ->groupStart()
                ->where('scheduled_at', null)
                ->orWhere('scheduled_at <=', $now)
            ->groupEnd()
            ->orderBy('id', 'ASC');

        $campaigns = $query->get()->getResult();
        $total = 0;
        foreach ($campaigns as $campaign) {
            $processed = $this->run($tenantId, (int) $campaign->id, $userId, false);
            if ($processed > 0) {
                $total += $processed;
            }
        }

        return $total;
    }

    public function sendTest(int $tenantId, int $campaignId, ?int $userId, string $recipient): array
    {
        $campaign = $this->campaigns->where('tenant_id', $tenantId)->find($campaignId);
        if (! $campaign) {
            return ['success' => false, 'message' => 'Chiến dịch không tồn tại.'];
        }

        $testCustomer = $this->findTestCustomer($tenantId, $campaign->channel, $recipient);
        if (! $testCustomer) {
            return ['success' => false, 'message' => 'Không tìm thấy khách test phù hợp với kênh đã chọn.'];
        }

        $payload = $this->buildPayloadForCustomer($campaign, $testCustomer, $tenantId);
        $result = $this->sendToRecipient($campaign->channel, $payload);
        if (! $result['ok']) {
            return ['success' => false, 'message' => 'Lỗi test: ' . (string) ($result['message'] ?? 'Không xác định')];
        }

        return ['success' => true, 'message' => "Test đã gửi tới {$payload['target']}; " . ($result['message'] ?? '')];
    }

    public function preview(int $tenantId, int $campaignId): int
    {
        $campaign = $this->campaigns->where('tenant_id', $tenantId)->find($campaignId);
        if (! $campaign) {
            return 0;
        }

        if (! $this->dbHasCampaignTables()) {
            return 0;
        }

        $db = \Config\Database::connect();
        return count($this->segmentCustomers($db, $tenantId, (string) $campaign->segment));
    }

    public function cancel(int $tenantId, int $campaignId, ?int $userId): bool
    {
        $campaign = $this->campaigns->where('tenant_id', $tenantId)->find($campaignId);
        if (! $campaign) {
            return false;
        }

        return (bool) $this->campaigns->update($campaignId, ['status' => 'cancelled', 'updated_by' => $userId]);
    }

    private function run(int $tenantId, int $campaignId, ?int $userId, bool $force): int
    {
        $campaign = $this->campaigns->where('tenant_id', $tenantId)->find($campaignId);
        if (! $campaign || $campaign->status === 'cancelled') {
            return -1;
        }

        if (! $this->dbHasCampaignTables() || ! $this->dbHasRecipientsTables()) {
            return -1;
        }

        $scheduledAt = $campaign->scheduled_at ?? null;
        $scheduledTs = $scheduledAt ? strtotime((string) $scheduledAt) : 0;
        if (! $force && in_array((string) $campaign->status, ['draft', 'scheduled'], true) && $scheduledTs > time()) {
            $this->campaigns->update($campaignId, ['status' => 'scheduled', 'updated_by' => $userId]);
            return 0;
        }

        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $nowTs = time();
        $created = 0;

        $customers = $this->segmentCustomers($db, $tenantId, (string) $campaign->segment);
        foreach ($customers as $customer) {
            $exists = $db->table('crm_campaign_recipients')->where([
                'tenant_id' => $tenantId,
                'campaign_id' => $campaignId,
                'customer_id' => $customer->id,
                'channel' => $campaign->channel,
            ])->countAllResults();

            if (! $exists) {
                $row = [
                    'tenant_id' => $tenantId,
                    'campaign_id' => $campaignId,
                    'customer_id' => $customer->id,
                    'channel' => $campaign->channel,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($this->hasAttempts) {
                    $row['attempts'] = 0;
                }
                if ($this->hasNextRetryAt) {
                    $row['next_retry_at'] = null;
                }
                if ($this->hasLastAttemptAt) {
                    $row['last_attempt_at'] = null;
                }
                $db->table('crm_campaign_recipients')->insert($row);
                $created++;
            }
        }

        $throttle = $this->hasThrottlePerMinute ? max(1, (int) ($campaign->throttle_per_minute ?? 60)) : 60;
        $maxRetries = $this->hasMaxRetries ? max(1, min(10, (int) ($campaign->max_retries ?? 3))) : 3;
        $eligible = $this->eligibleRecipientsQuery($tenantId, $campaignId, $maxRetries, $now)
            ->orderBy('r.id', 'ASC')
            ->get()
            ->getResult();

        $processed = 0;
        $sentNow = 0;
        $failedNow = 0;
        $recentWindowQuery = $db->table('crm_campaign_recipients r')
            ->where('r.campaign_id', $campaignId)
            ->where('r.tenant_id', $tenantId)
            ->where('r.status', 'sent');
        if ($this->hasLastAttemptAt) {
            $recentWindowQuery->where('r.last_attempt_at >=', date('Y-m-d H:i:s', $nowTs - 60));
        } elseif ($this->hasRecipientSentAt) {
            $recentWindowQuery->where('r.sent_at >=', date('Y-m-d H:i:s', $nowTs - 60));
        }
        $recentWindow = $recentWindowQuery->countAllResults();
        $available = max(0, $throttle - (int) $recentWindow);
        if ($available <= 0) {
            return $created;
        }

        foreach ($eligible as $recipient) {
            if ($processed >= $available) {
                break;
            }

            $customer = $db->table('customers')->where('id', (int) $recipient->customer_id)->get()->getRowObject();
            if (! $customer) {
                $this->updateRecipient((int) $recipient->id, 'failed', 1, 'Khách hàng không còn tồn tại.', $now, 0, $maxRetries);
                $failedNow++;
                $processed++;
                continue;
            }

            $payload = $this->buildPayloadForCustomer($campaign, $customer, $tenantId);
            $sendResult = $this->sendToRecipient((string) $campaign->channel, $payload);
            if ($sendResult['ok']) {
                $this->updateRecipient((int) $recipient->id, 'sent', 0, null, $now);
                $sentNow++;
            } else {
                $attempts = (int) ($recipient->attempts ?? 0) + 1;
                $nextStatus = $attempts >= $maxRetries ? 'failed' : 'pending';
                $nextRetryAt = $attempts >= $maxRetries ? null : date('Y-m-d H:i:s', $nowTs + $this->retryDelaySeconds($attempts));
                $this->updateRecipient(
                    (int) $recipient->id,
                    $nextStatus,
                    $attempts,
                    (string) ($sendResult['message'] ?? 'Gửi thất bại'),
                    $now,
                    $nextRetryAt
                );
                $failedNow++;
            }
            $processed++;
        }

        if ($available > 0 && $processed > 0) {
            $this->campaigns->update((int) $campaignId, ['status' => 'running', 'updated_by' => $userId]);
        }

        $summary = $db->table('crm_campaign_recipients')
            ->select('SUM(status = "sent") AS sent, SUM(status IN ("pending","failed")) AS waiting')
            ->where('tenant_id', $tenantId)
            ->where('campaign_id', $campaignId)
            ->get()
            ->getRowArray() ?: [];

        $sent = (int) ($summary['sent'] ?? 0);
        $waiting = (int) ($summary['waiting'] ?? 0);
        $status = $waiting > 0 ? 'running' : 'completed';
        $payload = [
            'status' => $status,
            'updated_by' => $userId,
        ];
        if ($this->hasSentAt && $sent > 0) {
            $payload['sent_at'] = $now;
        }
        $this->campaigns->update((int) $campaignId, $payload);

        return $created + $sentNow + $failedNow;
    }

    private function updateRecipient(
        int $recipientId,
        string $status,
        int $attempts,
        ?string $error,
        string $now,
        ?string $nextRetryAt = null
    ): void {
        $update = [
            'status' => $status,
            'updated_at' => $now,
        ];
        if ($this->hasRecipientErrorMessage && $error !== null) {
            $update['error_message'] = $error;
        }
        if ($this->hasAttempts) {
            $update['attempts'] = $attempts;
        }
        if ($this->hasLastAttemptAt) {
            $update['last_attempt_at'] = $now;
        }
        if ($this->hasNextRetryAt) {
            $update['next_retry_at'] = $nextRetryAt;
        }
        \Config\Database::connect()->table('crm_campaign_recipients')->where('id', $recipientId)->update($update);
    }

    private function sendToRecipient(string $channel, array $payload): array
    {
        $channel = $this->normalizeChannel($channel);
        $message = (string) ($payload['message'] ?? '');
        $subject = (string) ($payload['subject'] ?? '');
        $target = (string) ($payload['target'] ?? '');
        try {
            if ($channel === 'in_app') {
                if (! empty($payload['recipient_user_id'])) {
                    (new NotificationService())->notifyUser((int) $payload['recipient_user_id'], 'crm_campaign', [
                        'message' => $message,
                        'customer_name' => $payload['name'],
                    ], (int) $payload['tenant_id']);
                    return ['ok' => true];
                }
                return ['ok' => false, 'message' => 'Không xác định tài khoản in-app cho khách hàng.'];
            }

            if ($channel === 'email') {
                if (empty($target)) {
                    return ['ok' => false, 'message' => 'Không có email khách hàng.'];
                }
                log_message('info', "[CRM] Email test: {$target} | {$subject}");
                return ['ok' => true, 'message' => 'Gửi email đẩy vào hàng đợi log.'];
            }

            if ($channel === 'sms') {
                if (empty($target)) {
                    return ['ok' => false, 'message' => 'Không có số điện thoại khách hàng.'];
                }
                log_message('info', "[CRM] SMS test: {$target} | {$message}");
                return ['ok' => true, 'message' => 'Đã đẩy SMS test (mock provider).'];
            }

            if ($channel === 'zalo') {
                if (empty($target)) {
                    return ['ok' => false, 'message' => 'Không có số Zalo khách hàng.'];
                }
                log_message('info', "[CRM] Zalo test: {$target} | {$message}");
                return ['ok' => true, 'message' => 'Đã đẩy Zalo test (mock provider).'];
            }
        } catch (\Throwable $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        return ['ok' => false, 'message' => 'Kênh gửi không hợp lệ.'];
    }

    private function buildPayloadForCustomer(object $campaign, object $customer, int $tenantId): array
    {
        $variables = $this->resolveCustomerContext($tenantId, $customer);
        $channel = (string) $campaign->channel;
        return [
            'tenant_id' => $tenantId,
            'name' => $variables['name'],
            'phone' => $variables['phone'],
            'email' => $variables['email'],
            'target' => $channel === 'email' ? $variables['email'] : $variables['phone'],
            'recipient_user_id' => $variables['user_id'],
            'subject' => $this->applyTemplate((string) ($campaign->subject ?? ''), $variables),
            'message' => $this->applyTemplate((string) ($campaign->message ?? ''), $variables),
        ];
    }

    private function findTestCustomer(int $tenantId, string $channel, string $recipient): ?object
    {
        $db = \Config\Database::connect();
        $query = $db->table('customers')->where('tenant_id', $tenantId)->where('status', 'active');
        if ($recipient !== '') {
            $query->groupStart()
                ->like('phone', $recipient)
                ->orLike('email', $recipient)
                ->orLike('full_name', $recipient)
                ->groupEnd();
        }
        $query->orderBy('id', 'ASC')->limit(1);
        $customer = $query->get()->getRowObject();
        if (! $customer) {
            return null;
        }
        if ($channel === 'email' && empty((string) $customer->email)) {
            return null;
        }
        if (in_array($channel, ['sms', 'zalo'], true) && empty((string) $customer->phone)) {
            return null;
        }
        return $customer;
    }

    private function eligibleRecipientsQuery(int $tenantId, int $campaignId, int $maxRetries, string $now): BaseBuilder
    {
        $query = \Config\Database::connect()->table('crm_campaign_recipients r')
            ->where('r.tenant_id', $tenantId)
            ->where('r.campaign_id', $campaignId)
            ->whereIn('r.status', ['pending', 'failed']);

        if ($this->hasNextRetryAt) {
            $query->groupStart()
                ->where('r.status', 'pending')
                ->orGroupStart()
                    ->where('r.status', 'failed')
                    ->groupStart()
                        ->where('r.next_retry_at', null)
                        ->orWhere('r.next_retry_at <=', $now)
                    ->groupEnd()
                ->groupEnd()
            ->groupEnd();
        }

        if ($this->hasAttempts) {
            $query->where('r.attempts <', $maxRetries);
        }
        return $query;
    }

    private function retryDelaySeconds(int $attempts): int
    {
        return min(3600, 60 * max(1, (int) pow(2, $attempts - 1)));
    }

    private function normalizeSegment(string $segment): string
    {
        return array_key_exists($segment, $this->segments) ? $segment : 'all';
    }

    private function normalizeDbSegment(string $segment): string
    {
        if (! $this->hasSegmentEnum) {
            return $segment;
        }
        if (! in_array($segment, $this->campaignSegments ?? [], true)) {
            return 'all';
        }
        return $segment;
    }

    private function normalizeChannel(string $channel): string
    {
        $channel = strtolower(trim($channel));
        return in_array($channel, ['in_app', 'email', 'sms', 'zalo'], true) ? $channel : 'in_app';
    }

    private function applyTemplate(string $template, array $variables): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', static function ($match) use ($variables) {
            $key = (string) $match[1];
            return $variables[$key] ?? $match[0];
        }, $template);
    }

    private function resolveCustomerContext(int $tenantId, object $customer): array
    {
        $db = \Config\Database::connect();
        $name = (string) ($customer->full_name ?? '');
        $userId = null;
        if (! empty($customer->player_id) && $db->tableExists('players')) {
            $player = $db->table('players')->select('user_id')->where('id', (int) $customer->player_id)->where('tenant_id', $tenantId)->get()->getRow();
            $userId = $player->user_id ?? null;
        }

        $membership = null;
        if (! empty($customer->player_id) && $db->tableExists('memberships')) {
            $membership = $db->table('memberships m')
                ->where('m.player_id', (int) $customer->player_id)
                ->where('m.tenant_id', $tenantId)
                ->where('m.status', 'active')
                ->orderBy('m.end_date', 'DESC')
                ->get()
                ->getRow();
        }

        return [
            'name' => $name !== '' ? $name : (string) ($customer->full_name ?? ''),
            'customer_name' => $name,
            'phone' => (string) ($customer->phone ?? ''),
            'email' => (string) ($customer->email ?? ''),
            'expiry_date' => $membership && ! empty($membership->end_date) ? (string) $membership->end_date : '',
            'days_left' => $membership && ! empty($membership->end_date)
                ? (string) max(0, (int) ((strtotime((string) $membership->end_date) - strtotime(date('Y-m-d'))) / 86400))
                : '',
            'user_id' => $userId,
        ];
    }

    private function segmentCustomers($db, int $tenantId, string $segment): array
    {
        $query = $db->table('customers c')
            ->select('c.*')
            ->where('c.tenant_id', $tenantId)
            ->where('c.status', 'active')
            ->where('c.deleted_at', null);

        if ($segment === 'inactive') {
            $query->groupStart()
                ->where('c.last_visit_at <', date('Y-m-d H:i:s', strtotime('-30 days')))
                ->orWhere('c.last_visit_at', null)
                ->groupEnd();
        } elseif ($segment === 'high_value') {
            $query->where('c.total_spend >=', 5000000);
        } elseif ($segment === 'recently_active') {
            $query->groupStart()
                ->where('c.last_seen_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->orWhere('c.last_booking_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->orWhere('c.last_visit_at >=', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->groupEnd();
        } elseif ($segment === 'tournament_participants' && $db->tableExists('tournament_registrations')) {
            $query->join('tournament_registrations tr', 'tr.player_id = c.player_id AND tr.tenant_id = c.tenant_id', 'inner')
                ->where('tr.created_at >=', date('Y-m-d H:i:s', strtotime('-30 days')));
        } elseif ($segment === 'expiring_membership' && $db->tableExists('memberships')) {
            $query->join('memberships m', 'm.player_id = c.player_id AND m.tenant_id = c.tenant_id', 'inner')
                ->where('m.status', 'active')
                ->where('m.end_date <=', date('Y-m-d', strtotime('+30 days')))
                ->where('m.end_date >=', date('Y-m-d'));
        }

        return $query->groupBy('c.id')
            ->orderBy('c.full_name', 'ASC')
            ->get()
            ->getResult();
    }

    private function dbHasCampaignTables(): bool
    {
        $db = \Config\Database::connect();
        return $db->tableExists('crm_campaigns');
    }

    private function loadCampaignSegments(): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('crm_campaigns')) {
            return array_keys($this->segments);
        }

        $row = $db->query('SHOW COLUMNS FROM crm_campaigns WHERE Field = ?', ['segment'])->getRowArray();
        $enum = is_array($row) ? (string) ($row['Type'] ?? '') : '';
        if (preg_match('/^enum\\((.*)\\)$/i', $enum, $matches)) {
            $values = array_map(
                static fn ($value) => trim($value, "'\""),
                array_filter(array_map('trim', explode(',', $matches[1])))
            );
            return $values !== [] ? $values : array_keys($this->segments);
        }

        return array_keys($this->segments);
    }

    private function isSegmentColumnEnum(): bool
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('crm_campaigns')) {
            return false;
        }

        $row = $db->query('SHOW COLUMNS FROM crm_campaigns WHERE Field = ?', ['segment'])->getRowArray();
        $type = strtolower((string) ($row['Type'] ?? ''));
        return str_starts_with($type, 'enum(');
    }

    private function dbHasRecipientsTables(): bool
    {
        $db = \Config\Database::connect();
        return $db->tableExists('crm_campaign_recipients');
    }
}
