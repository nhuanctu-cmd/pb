<?php

namespace App\Models;

use CodeIgniter\Model;

class CourtDeviceLogModel extends Model
{
    protected $table            = 'court_device_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'device_id', 'action', 'value', 'previous_value',
        'triggered_by', 'triggered_user_id', 'metadata', 'created_at',
    ];
    protected $useTimestamps = false;

    public function getLogs(int $deviceId, int $limit = 50): array
    {
        return $this->where('device_id', $deviceId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    public function getRecentActions(int $branchId, int $minutes = 60): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        return $this->select('court_device_logs.*, court_devices.name_vi as device_name, court_devices.device_type')
            ->join('court_devices', 'court_devices.id = court_device_logs.device_id')
            ->where('court_devices.branch_id', $branchId)
            ->where('court_device_logs.created_at >=', $since)
            ->orderBy('court_device_logs.created_at', 'DESC')
            ->limit(100)
            ->findAll();
    }

    public function log(int $tenantId, int $deviceId, string $action, ?string $value = null, ?string $previousValue = null, string $triggeredBy = 'system', ?int $userId = null, ?array $metadata = null): bool
    {
        return (bool) $this->insert([
            'tenant_id'          => $tenantId,
            'device_id'          => $deviceId,
            'action'             => $action,
            'value'              => $value,
            'previous_value'     => $previousValue,
            'triggered_by'       => $triggeredBy,
            'triggered_user_id'  => $userId,
            'metadata'           => $metadata ? json_encode($metadata) : null,
            'created_at'         => date('Y-m-d H:i:s'),
        ]);
    }
}
