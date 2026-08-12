<?php

namespace App\Services;

use App\Models\TenantDataPolicyModel;
use CodeIgniter\Database\BaseBuilder;
use Config\Database;

/**
 * Single authorization boundary for tenant-owned and platform-owned data.
 *
 * This service deliberately does not make operational data globally visible:
 * every caller must choose an explicit scope. Public projections are allowed
 * cross-tenant only when the policy says the resource is published.
 */
class TenantDataPolicy
{
    public const OPERATIONAL = 'operational';
    public const PLATFORM_PUBLIC = 'platform_public';
    public const PLATFORM_NETWORK = 'platform_network';
    public const GOVERNANCE = 'platform_governance';
    public const RESTRICTED = 'restricted';

    private TenantDataPolicyModel $model;

    public function __construct()
    {
        $this->model = model(TenantDataPolicyModel::class);
    }

    public function context(?int $requestedTenantId = null, ?int $sessionTenantId = null, bool $isSuperAdmin = false): array
    {
        $sessionTenantId = $sessionTenantId ?: (function_exists('current_tenant_id') ? current_tenant_id() : null);
        $requestedTenantId = $requestedTenantId ?: null;
        if ($requestedTenantId && $sessionTenantId && $requestedTenantId !== (int) $sessionTenantId && ! $isSuperAdmin) {
            return ['allowed' => false, 'tenant_id' => (int) $sessionTenantId, 'reason' => 'TENANT_CONTEXT_MISMATCH'];
        }
        return ['allowed' => true, 'tenant_id' => (int) ($requestedTenantId ?: $sessionTenantId ?: 0), 'is_superadmin' => $isSuperAdmin];
    }

    public function canAccess(?int $resourceTenantId, ?int $contextTenantId, string $scope = self::OPERATIONAL, bool $isSuperAdmin = false, bool $published = false, ?string $resourceType = null): bool
    {
        $resourceTenantId = $resourceTenantId ? (int) $resourceTenantId : null;
        $contextTenantId = $contextTenantId ? (int) $contextTenantId : null;

        if ($isSuperAdmin && $scope !== self::RESTRICTED) return true;
        if ($scope === self::RESTRICTED || $scope === self::GOVERNANCE) return false;
        if ($scope === self::OPERATIONAL) return $resourceTenantId !== null && $contextTenantId !== null && $resourceTenantId === $contextTenantId;
        if ($scope === self::PLATFORM_PUBLIC || $scope === self::PLATFORM_NETWORK) {
            return $published && ($resourceTenantId === null || $contextTenantId === null || $resourceTenantId === $contextTenantId || $this->allows($contextTenantId, $resourceType ?: 'platform', $scope));
        }
        return false;
    }

    public function assertAccess(?int $resourceTenantId, ?int $contextTenantId, string $scope = self::OPERATIONAL, bool $isSuperAdmin = false, bool $published = false, ?string $resourceType = null): array
    {
        $allowed = $this->canAccess($resourceTenantId, $contextTenantId, $scope, $isSuperAdmin, $published, $resourceType);
        return ['allowed' => $allowed, 'code' => $allowed ? null : ($scope === self::OPERATIONAL ? 'TENANT_ISOLATION' : 'POLICY_DENIED'), 'scope' => $scope];
    }

    /** Add the mandatory tenant predicate to an operational query. */
    public function constrain(BaseBuilder $builder, int $tenantId, string $column = 'tenant_id'): BaseBuilder
    {
        if ($tenantId <= 0) return $builder->where($column, -1);
        return $builder->where($column, $tenantId);
    }

    public function ensureDefaults(int $tenantId, ?int $createdBy = null): array
    {
        if ($tenantId <= 0 || ! Database::connect()->tableExists('tenant_data_policies')) return ['success' => false, 'created' => 0];
        $defaults = [
            ['resource_type' => 'operational', 'access_scope' => self::OPERATIONAL, 'effect' => 'deny', 'visibility' => 'private'],
            ['resource_type' => 'player_passport', 'access_scope' => self::PLATFORM_PUBLIC, 'effect' => 'allow', 'visibility' => 'published', 'requires_consent' => 1],
            ['resource_type' => 'official_ranking', 'access_scope' => self::PLATFORM_NETWORK, 'effect' => 'allow', 'visibility' => 'published'],
            ['resource_type' => 'official_match', 'access_scope' => self::PLATFORM_NETWORK, 'effect' => 'allow', 'visibility' => 'published'],
            ['resource_type' => 'identity_evidence', 'access_scope' => self::RESTRICTED, 'effect' => 'deny', 'visibility' => 'restricted'],
        ];
        $created = 0;
        foreach ($defaults as $default) {
            $query = $this->model->where('tenant_id', $tenantId)->where('resource_type', $default['resource_type'])->where('access_scope', $default['access_scope'])->where('version', 'v1');
            if ($query->first()) continue;
            $id = $this->model->insert($default + ['tenant_id' => $tenantId, 'version' => 'v1', 'status' => 'active', 'created_by' => $createdBy, 'configuration' => json_encode(['policy_source' => 'system-default'])]);
            if ($id) $created++;
        }
        return ['success' => true, 'created' => $created];
    }

    public function policies(int $tenantId): array
    {
        if ($tenantId <= 0 || ! Database::connect()->tableExists('tenant_data_policies')) return [];
        return $this->model->where('tenant_id', $tenantId)->where('status', 'active')->orderBy('resource_type')->findAll();
    }

    private function allows(?int $tenantId, string $resourceType, string $scope): bool
    {
        if (! $tenantId || ! Database::connect()->tableExists('tenant_data_policies')) return false;
        return (bool) $this->model->where('tenant_id', $tenantId)->where('resource_type', $resourceType)->where('access_scope', $scope)->where('effect', 'allow')->where('status', 'active')->first();
    }
}
