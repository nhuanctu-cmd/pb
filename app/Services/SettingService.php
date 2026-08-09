<?php

namespace App\Services;

use App\Models\SettingModel;

/**
 * Settings: lấy theo thứ tự tenant override → global default
 */
class SettingService
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    /**
     * Lấy giá trị setting. Tenant override ưu tiên hơn global default.
     */
    public function get(string $key, $default = null, ?int $tenantId = null)
    {
        $tenantId = $tenantId ?? session('tenant_id');

        // 1. Tenant-specific
        if ($tenantId) {
            $row = $this->settingModel->getSetting($key, $tenantId);
            if ($row && $row->value !== null) {
                return $this->castValue($row);
            }
        }

        // 2. Global default
        $row = $this->settingModel->getSetting($key, null);
        if ($row && $row->value !== null) {
            return $this->castValue($row);
        }

        return $default;
    }

    /**
     * Lấy toàn bộ setting của 1 nhóm (tenant + global) để render form
     */
    public function getGroup(string $group, ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? session('tenant_id');

        $tenantRows = $tenantId
            ? $this->settingModel->getSettingsByGroup($group, $tenantId)
            : [];

        $globalRows = $this->settingModel->getSettingsByGroup($group, null);

        // Global làm mặc định, tenant ghi đè
        $settings = [];
        foreach ($globalRows as $row) {
            $settings[$row->key] = $this->prepareForForm($row, false);
        }
        foreach ($tenantRows as $row) {
            $settings[$row->key] = $this->prepareForForm($row, true);
        }

        return $settings;
    }

    /**
     * Cập nhật nhiều setting cùng nhóm
     */
    public function setGroup(string $group, array $values, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? session('tenant_id');
        if (! $tenantId) {
            return false;
        }

        foreach ($values as $key => $value) {
            $this->set($key, $value, $tenantId, $group);
        }

        return true;
    }

    public function set(string $key, $value, ?int $tenantId = null, string $group = 'general', string $type = 'text'): bool
    {
        return (bool) $this->settingModel->setSetting($key, $value, $tenantId, $type, $group);
    }

    private function castValue(object $row)
    {
        if ((int) ($row->is_json ?? 0) === 1) {
            return json_decode($row->value, true);
        }
        if ($row->type === 'number') {
            return is_numeric($row->value) ? (float) $row->value : $row->value;
        }
        if ($row->type === 'boolean') {
            return in_array($row->value, ['1', 'true', 'yes', 'on'], true);
        }
        return $row->value;
    }

    private function prepareForForm(object $row, bool $isTenantOverride): array
    {
        return [
            'id'        => $row->id,
            'key'       => $row->key,
            'value'     => $row->value,
            'type'      => $row->type ?? 'text',
            'group'     => $row->group ?? 'general',
            'is_json'   => (int) ($row->is_json ?? 0),
            'tenant_id' => $row->tenant_id,
            'is_override'=> $isTenantOverride,
        ];
    }
}
