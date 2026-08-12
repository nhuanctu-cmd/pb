<?php

namespace App\Services;

use App\Models\PartnerApiKeyModel;
use Config\Database;

/** Scoped credentials for external clubs, media and tournament partners. */
class PartnerApiService
{
    public const SCOPES = ['players.read', 'rankings.read', 'clubs.read', 'tournaments.read'];

    private PartnerApiKeyModel $model;

    public function __construct()
    {
        $this->model = model(PartnerApiKeyModel::class);
    }

    public function createKey(int $tenantId, string $name, array $scopes, ?int $userId = null, ?string $expiresAt = null): array
    {
        $name = trim($name);
        $scopes = array_values(array_unique(array_intersect(array_map('strval', $scopes), self::SCOPES)));
        if ($tenantId <= 0 || $name === '' || mb_strlen($name) > 180 || $scopes === []) {
            return ['success' => false, 'message' => 'API key cần tên và ít nhất một scope hợp lệ.'];
        }
        if ($expiresAt !== null && strtotime($expiresAt) === false) {
            return ['success' => false, 'message' => 'Ngày hết hạn API key không hợp lệ.'];
        }

        $raw = 'pk_live_' . self::base64Url(random_bytes(30));
        $id = $this->model->insert([
            'tenant_id' => $tenantId,
            'name' => $name,
            'key_prefix' => substr($raw, 0, 16),
            'key_hash' => hash('sha256', $raw),
            'scopes' => json_encode($scopes, JSON_UNESCAPED_UNICODE),
            'status' => 'active',
            'expires_at' => $expiresAt ?: null,
            'created_by' => $userId,
        ]);
        if (! $id) return ['success' => false, 'message' => 'Không tạo được API key.'];

        if (function_exists('log_audit')) {
            log_audit(['action' => 'partner_api_key_created', 'entity_type' => 'partner_api_key', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => ['scopes' => $scopes]]);
        }
        return ['success' => true, 'id' => (int) $id, 'key' => $raw, 'message' => 'Đã tạo API key. Hãy sao chép ngay vì secret chỉ hiển thị một lần.'];
    }

    public function authenticate(string $rawKey): ?object
    {
        $rawKey = trim($rawKey);
        if ($rawKey === '' || ! str_starts_with($rawKey, 'pk_live_')) return null;
        $key = $this->model->where('key_hash', hash('sha256', $rawKey))->where('status', 'active')->first();
        if (! $key || ($key->expires_at && strtotime((string) $key->expires_at) < time())) return null;
        $this->model->update($key->id, ['last_used_at' => date('Y-m-d H:i:s')]);
        return $key;
    }

    public function hasScope(?object $key, string $scope): bool
    {
        if (! $key) return false;
        return in_array($scope, json_decode((string) $key->scopes, true) ?: [], true);
    }

    public function forTenant(int $tenantId): array
    {
        return $this->model->where('tenant_id', $tenantId)->orderBy('id', 'DESC')->findAll(100);
    }

    public function revoke(int $id, int $tenantId, ?int $userId = null): array
    {
        $key = $this->model->where('id', $id)->where('tenant_id', $tenantId)->where('status', 'active')->first();
        if (! $key) return ['success' => false, 'message' => 'API key không thuộc tenant hiện tại.'];
        $ok = $this->model->update($id, ['status' => 'revoked', 'revoked_at' => date('Y-m-d H:i:s')]);
        if ($ok && function_exists('log_audit')) log_audit(['action' => 'partner_api_key_revoked', 'entity_type' => 'partner_api_key', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => ['user_id' => $userId]]);
        return ['success' => (bool) $ok, 'message' => $ok ? 'Đã thu hồi API key.' : 'Không thể thu hồi API key.'];
    }

    public static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
