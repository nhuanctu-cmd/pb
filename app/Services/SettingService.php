<?php

namespace App\Services;

use App\Models\SettingModel;

class SettingService
{
    protected SettingModel $settingModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
    }

    public function get(string $key, ?int $tenantId = null, $default = null)
    {
        $setting = $this->settingModel->getSetting($key, $tenantId);
        if (!$setting) {
            return $default;
        }
        if ($setting->is_json) {
            return json_decode($setting->value, true);
        }
        return $setting->value;
    }

    public function set(string $key, $value, ?int $tenantId = null, string $type = 'text', string $group = 'general'): bool
    {
        return (bool) $this->settingModel->setSetting($key, $value, $tenantId, $type, $group);
    }

    public function getGroup(string $group, ?int $tenantId = null): array
    {
        $settings = $this->settingModel->getSettingsByGroup($group, $tenantId);
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->is_json ? json_decode($setting->value, true) : $setting->value;
        }
        return $result;
    }

    public function getAll(?int $tenantId = null): array
    {
        $query = $this->settingModel->where('deleted_at', null);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->where('tenant_id', null);
        }
        $settings = $query->findAll();
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->is_json ? json_decode($setting->value, true) : $setting->value;
        }
        return $result;
    }

    public function delete(string $key, ?int $tenantId = null): bool
    {
        $setting = $this->settingModel->getSetting($key, $tenantId);
        if ($setting) {
            return $this->settingModel->delete($setting->id);
        }
        return false;
    }
}
