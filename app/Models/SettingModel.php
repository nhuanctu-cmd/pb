<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'key', 'value', 'type',
        'group', 'is_json', 'is_public', 'is_active', 'status',
        'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'key'   => 'required|max_length[100]',
        'type'  => 'permit_empty|max_length[50]',
        'group' => 'permit_empty|max_length[50]',
        'status'=> 'required|in_list[active,inactive]',
    ];

    public function getSetting(string $key, ?int $tenantId = null, ?int $branchId = null)
    {
        $query = $this->where('key', $key)
                      ->where('deleted_at', null);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->where('tenant_id', null);
        }
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        return $query->first();
    }

    public function getSettingsByGroup(string $group, ?int $tenantId = null)
    {
        $query = $this->where('group', $group)
                      ->where('deleted_at', null);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->where('tenant_id', null);
        }
        return $query->findAll();
    }

    public function setSetting(string $key, $value, ?int $tenantId = null, string $type = 'text', string $group = 'general')
    {
        $existing = $this->getSetting($key, $tenantId);
        $data = [
            'key'       => $key,
            'value'     => is_array($value) ? json_encode($value) : $value,
            'type'      => $type,
            'group'     => $group,
            'is_json'   => is_array($value) ? 1 : 0,
            'is_active' => 1,
            'status'    => 'active',
            'tenant_id' => $tenantId,
        ];
        if ($existing) {
            return $this->update($existing->id, $data);
        }
        return $this->insert($data);
    }
}
