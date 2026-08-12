<?php

namespace App\Models;

use CodeIgniter\Model;

class WebhookEndpointModel extends Model
{
    protected $table = 'webhook_endpoints';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'name', 'url', 'secret_ciphertext', 'event_types', 'status', 'created_by', 'updated_by'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function allForTenant(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'DESC')->findAll(100);
    }

    public function activeForTenant(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)->where('status', 'active')->where('deleted_at', null)->findAll(100);
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }
}
