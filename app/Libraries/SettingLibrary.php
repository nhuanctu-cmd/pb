<?php

namespace App\Libraries;

use App\Models\SettingModel;

class SettingLibrary
{
    protected static $settings = [];
    protected static $loaded = false;

    public static function get(string $key, $default = null, ?int $tenantId = null)
    {
        if (!self::$loaded) {
            self::loadAll($tenantId);
        }

        return self::$settings[$key] ?? $default;
    }

    public static function set(string $key, $value, ?int $tenantId = null): bool
    {
        $settingModel = new SettingModel();
        $result = $settingModel->setSetting($key, $value, $tenantId);
        if ($result) {
            self::$settings[$key] = $value;
        }
        return (bool) $result;
    }

    protected static function loadAll(?int $tenantId = null): void
    {
        $settingModel = new SettingModel();
        $query = $settingModel->where('deleted_at', null);
        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        } else {
            $query->where('tenant_id', null);
        }
        $settings = $query->findAll();

        foreach ($settings as $setting) {
            $value = $setting->is_json ? json_decode($setting->value, true) : $setting->value;
            self::$settings[$setting->key] = $value;
        }

        self::$loaded = true;
    }

    public static function clearCache(): void
    {
        self::$settings = [];
        self::$loaded = false;
    }
}
