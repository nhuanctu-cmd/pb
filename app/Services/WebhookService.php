<?php

namespace App\Services;

use App\Models\JobModel;
use App\Models\WebhookDeliveryModel;
use App\Models\WebhookEndpointModel;

class WebhookService
{
    public const EVENT_TYPES = [
        'booking.created', 'booking.cancelled', 'booking.completed', 'payment.succeeded',
        'tournament.updated', 'tournament.completed', 'livestream.status_changed',
        'player.updated', 'rating.updated', 'ranking.updated', 'match.official', '*',
    ];

    private WebhookEndpointModel $endpointModel;
    private WebhookDeliveryModel $deliveryModel;
    private JobModel $jobModel;

    public function __construct()
    {
        $this->endpointModel = new WebhookEndpointModel();
        $this->deliveryModel = new WebhookDeliveryModel();
        $this->jobModel = new JobModel();
    }

    public function endpoints(int $tenantId): array
    {
        return $tenantId ? $this->endpointModel->allForTenant($tenantId) : [];
    }

    public function updateEndpointStatus(int $id, string $status, int $tenantId, ?int $userId = null): array
    {
        if (! in_array($status, ['active', 'disabled'], true)) {
            return ['success' => false, 'message' => 'Trạng thái webhook không hợp lệ.'];
        }
        $endpoint = $this->endpointModel->findForTenant($id, $tenantId);
        if (! $endpoint) return ['success' => false, 'message' => 'Webhook không thuộc tenant hiện tại.'];
        $db = \Config\Database::connect();
        $db->transStart();
        $ok = $this->endpointModel->update($id, ['status' => $status, 'updated_by' => $userId]);
        $db->transComplete();
        if (! $db->transStatus() || ! $ok) return ['success' => false, 'message' => 'Không thể cập nhật webhook.'];
        $this->audit('endpoint_status_changed', $id, $tenantId, ['status' => $status, 'user_id' => $userId]);
        return ['success' => true, 'message' => 'Đã cập nhật webhook.'];
    }

    public function createEndpoint(array $data, int $tenantId, ?int $userId = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $url = trim((string) ($data['url'] ?? ''));
        $secret = trim((string) ($data['secret'] ?? ''));
        $events = $data['event_types'] ?? [];
        if (is_string($events)) $events = preg_split('/[,\s]+/', $events, -1, PREG_SPLIT_NO_EMPTY);
        $events = array_values(array_unique(array_map('strval', is_array($events) ? $events : [])));

        if (! $tenantId || $name === '' || mb_strlen($name) > 180 || ! self::isSafeUrl($url)
            || mb_strlen($secret) < 16 || mb_strlen($secret) > 255 || ! self::validEvents($events)) {
            return ['success' => false, 'message' => 'Webhook không hợp lệ: cần URL HTTPS, secret tối thiểu 16 ký tự và event hợp lệ.'];
        }

        $secretCiphertext = self::encryptSecret($secret);
        $db = \Config\Database::connect();
        $db->transStart();
        $id = $this->endpointModel->insert([
            'tenant_id' => $tenantId,
            'name' => $name,
            'url' => $url,
            'secret_ciphertext' => $secretCiphertext,
            'event_types' => json_encode($events, JSON_UNESCAPED_UNICODE),
            'status' => 'active',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        $db->transComplete();
        if (! $db->transStatus() || ! $id) return ['success' => false, 'message' => 'Không thể tạo webhook endpoint.'];

        $this->audit('endpoint_created', (int) $id, $tenantId, ['event_types' => $events]);
        return ['success' => true, 'id' => (int) $id, 'message' => 'Đã tạo webhook endpoint.'];
    }

    public function dispatch(int $tenantId, string $eventType, array $payload): array
    {
        if (! $tenantId || ! in_array($eventType, self::EVENT_TYPES, true)) {
            return ['success' => false, 'queued' => 0, 'message' => 'Event webhook không hợp lệ.'];
        }

        try {
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return ['success' => false, 'queued' => 0, 'message' => 'Payload webhook không thể mã hóa JSON.'];
        }
        $targets = [];
        foreach ($this->endpointModel->activeForTenant($tenantId) as $endpoint) {
            $events = json_decode((string) $endpoint->event_types, true) ?: [];
            if (! in_array('*', $events, true) && ! in_array($eventType, $events, true)) continue;
            try {
                $secret = self::decryptSecret((string) $endpoint->secret_ciphertext);
            } catch (\Throwable $exception) {
                log_message('error', 'Webhook endpoint secret could not be decrypted: endpoint=' . (int) $endpoint->id);
                continue;
            }
            $targets[] = [$endpoint, $secret];
        }

        $db = \Config\Database::connect();
        $db->transStart();
        $queued = 0;
        foreach ($targets as [$endpoint, $secret]) {
            $deliveryId = $this->deliveryModel->insert([
                'tenant_id' => $tenantId,
                'endpoint_id' => (int) $endpoint->id,
                'event_type' => $eventType,
                'payload_json' => $payloadJson,
                'signature' => self::sign($payloadJson, $secret),
                'status' => 'pending',
                'attempts' => 0,
                'max_attempts' => 5,
                'next_attempt_at' => date('Y-m-d H:i:s'),
            ]);
            if (! $deliveryId || ! $this->jobModel->push('webhook_delivery', ['delivery_id' => (int) $deliveryId, 'tenant_id' => $tenantId], 0, 5, $tenantId, 'webhook_delivery:' . (int) $deliveryId)) {
                $db->transRollback();
                return ['success' => false, 'queued' => $queued, 'message' => 'Không thể xếp hàng webhook.'];
            }
            $queued++;
        }
        $db->transComplete();
        if (! $db->transStatus()) return ['success' => false, 'queued' => 0, 'message' => 'Không thể ghi hàng đợi webhook.'];
        return ['success' => true, 'queued' => $queued];
    }

    public function deliver(int $deliveryId, int $tenantId): array
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $delivery = $this->deliveryModel->findForUpdate($deliveryId, $tenantId);
        if (! $delivery || in_array($delivery->status, ['succeeded', 'sending'], true)
            || ($delivery->next_attempt_at && $delivery->next_attempt_at > date('Y-m-d H:i:s'))
            || (int) $delivery->attempts >= (int) $delivery->max_attempts) {
            $db->transRollback();
            return ['success' => false, 'message' => 'Delivery không sẵn sàng hoặc đã hoàn tất.'];
        }
        $endpoint = $this->endpointModel->findForTenant((int) $delivery->endpoint_id, $tenantId);
        if (! $endpoint || $endpoint->status !== 'active') {
            $this->deliveryModel->update($deliveryId, ['status' => 'failed', 'error_message' => 'Endpoint không còn hoạt động.', 'attempts' => (int) $delivery->attempts + 1]);
            $db->transComplete();
            return ['success' => false, 'message' => 'Endpoint không còn hoạt động.'];
        }
        $attempt = (int) $delivery->attempts + 1;
        $this->deliveryModel->update($deliveryId, ['status' => 'sending', 'attempts' => $attempt, 'error_message' => null]);
        $db->transComplete();
        if (! $db->transStatus()) return ['success' => false, 'message' => 'Không thể claim webhook delivery.'];

        try {
            if (! function_exists('curl_init')) throw new \RuntimeException('PHP cURL chưa được bật.');
            $ch = curl_init((string) $endpoint->url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => (string) $delivery->payload_json,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Pickleball-Event: ' . $delivery->event_type, 'X-Pickleball-Signature: sha256=' . $delivery->signature],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $body = (string) curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($code < 200 || $code >= 300) throw new \RuntimeException($error ?: 'HTTP ' . $code);
            $this->deliveryModel->update($deliveryId, ['status' => 'succeeded', 'response_code' => $code, 'response_body' => mb_substr($body, 0, 2000), 'delivered_at' => date('Y-m-d H:i:s'), 'next_attempt_at' => null]);
            $this->audit('delivery_succeeded', $deliveryId, $tenantId, ['endpoint_id' => (int) $endpoint->id, 'response_code' => $code]);
            return ['success' => true, 'response_code' => $code];
        } catch (\Throwable $exception) {
            $delay = min(3600, 2 ** min($attempt, 10));
            $this->deliveryModel->update($deliveryId, ['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 1000), 'next_attempt_at' => date('Y-m-d H:i:s', time() + $delay)]);
            $this->audit('delivery_failed', $deliveryId, $tenantId, ['endpoint_id' => (int) $endpoint->id, 'attempt' => $attempt]);
            return ['success' => false, 'message' => 'Webhook delivery thất bại.'];
        }
    }

    public static function isSafeUrl(string $url): bool
    {
        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) !== false && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    public static function validEvents(array $events): bool
    {
        return $events !== [] && count(array_diff($events, self::EVENT_TYPES)) === 0;
    }

    public static function sign(string $payloadJson, string $secret): string
    {
        return hash_hmac('sha256', $payloadJson, $secret);
    }

    public static function encryptSecret(string $secret): string
    {
        return base64_encode((string) service('encrypter')->encrypt($secret));
    }

    public static function decryptSecret(string $ciphertext): string
    {
        $decoded = base64_decode($ciphertext, true);
        if ($decoded === false) throw new \RuntimeException('Webhook secret không hợp lệ.');
        return (string) service('encrypter')->decrypt($decoded);
    }

    private function audit(string $action, int $id, int $tenantId, array $data): void
    {
        if (function_exists('log_audit')) log_audit(['action' => 'webhook_' . $action, 'entity_type' => 'webhook', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => $data]);
    }
}
