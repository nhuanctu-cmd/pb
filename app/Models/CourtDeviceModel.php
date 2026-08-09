<?php

namespace App\Models;

use CodeIgniter\Model;

class CourtDeviceModel extends Model
{
    protected $table            = 'court_devices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\CourtDevice::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'court_id', 'facility_id', 'device_type',
        'code', 'name_vi', 'name_en', 'model', 'serial_number', 'ip_address',
        'mac_address', 'firmware_version', 'mqtt_topic', 'api_endpoint',
        'config', 'status', 'is_active', 'last_ping_at', 'last_value',
        'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getByBranch(int $branchId, array $filters = []): array
    {
        $builder = $this->where('branch_id', $branchId)->where('deleted_at', null);

        if (!empty($filters['device_type'])) {
            $builder->where('device_type', $filters['device_type']);
        }

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        return $builder->orderBy('device_type', 'ASC')->orderBy('code', 'ASC')->findAll();
    }

    public function getByCourt(int $courtId): array
    {
        return $this->where('court_id', $courtId)
            ->where('deleted_at', null)
            ->orderBy('device_type', 'ASC')
            ->findAll();
    }

    public function findForTenant(int $deviceId, int $tenantId): ?object
    {
        return $this->where('id', $deviceId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }

    public function getOnlineDevices(int $branchId): array
    {
        return $this->where('branch_id', $branchId)
            ->where('status', 'online')
            ->where('deleted_at', null)
            ->findAll();
    }

    public function updateDeviceStatus(int $deviceId, string $status, ?string $value = null): bool
    {
        return $this->update($deviceId, [
            'status'       => $status,
            'last_value'   => $value,
            'last_ping_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
