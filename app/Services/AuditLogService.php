<?php

namespace App\Services;

use App\Models\AuditLogModel;
use CodeIgniter\HTTP\RequestInterface;

class AuditLogService
{
    protected AuditLogModel $auditLogModel;
    protected ?RequestInterface $request;

    public function __construct()
    {
        $this->auditLogModel = new AuditLogModel();
        $this->request = service('request');
    }

    public function log(
        string $action,
        string $module,
        ?string $tableName = null,
        ?int $recordId = null,
        $oldValues = null,
        $newValues = null,
        ?string $description = null,
        ?int $tenantId = null,
        ?int $branchId = null,
        ?int $userId = null
    ): bool {
        $data = [
            'tenant_id'   => $tenantId ?? session('tenant_id'),
            'branch_id'   => $branchId ?? session('branch_id'),
            'user_id'     => $userId ?? user_id(),
            'action'      => $action,
            'module'      => $module,
            'table_name'  => $tableName,
            'record_id'   => $recordId,
            'old_values'  => $oldValues ? json_encode($oldValues) : null,
            'new_values'  => $newValues ? json_encode($newValues) : null,
            'ip_address'  => $this->request ? $this->request->getIPAddress() : null,
            'user_agent'  => $this->request ? $this->request->getUserAgent()->getAgentString() : null,
            'description' => $description,
        ];

        return (bool) $this->auditLogModel->insert($data);
    }

    public function logCreate(string $module, string $tableName, int $recordId, $values, ?string $description = null): bool
    {
        return $this->log('create', $module, $tableName, $recordId, null, $values, $description);
    }

    public function logUpdate(string $module, string $tableName, int $recordId, $oldValues, $newValues, ?string $description = null): bool
    {
        return $this->log('update', $module, $tableName, $recordId, $oldValues, $newValues, $description);
    }

    public function logDelete(string $module, string $tableName, int $recordId, $oldValues, ?string $description = null): bool
    {
        return $this->log('delete', $module, $tableName, $recordId, $oldValues, null, $description);
    }

    public function getByUser(int $userId, int $limit = 50)
    {
        return $this->auditLogModel->getByUser($userId, $limit);
    }

    public function getByModule(string $module, int $limit = 50)
    {
        return $this->auditLogModel->getByModule($module, $limit);
    }

    public function getRecent(int $tenantId = null, int $limit = 50)
    {
        $query = $this->auditLogModel->orderBy('created_at', 'DESC')->limit($limit);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        return $query->findAll();
    }
}
