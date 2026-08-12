<?php

namespace App\Models;

use CodeIgniter\Model;

class OrganizationMembershipModel extends Model
{
    protected $table = 'organization_memberships';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['tenant_id', 'user_id', 'branch_id', 'role_code', 'status', 'is_primary', 'starts_at', 'ends_at', 'metadata'];
    protected $useTimestamps = true;

    public function forUser(int $userId, ?int $tenantId = null): array
    {
        $builder = $this->where('user_id', $userId)->where('status', 'active');
        if ($tenantId) $builder->where('tenant_id', $tenantId);
        return $builder->orderBy('is_primary', 'DESC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object { return $this->where('id', $id)->where('tenant_id', $tenantId)->first(); }
}
