<?php

use App\Libraries\SettingLibrary;

if (!function_exists('setting')) {
    function setting(string $key, $default = null, ?int $tenantId = null)
    {
        return SettingLibrary::get($key, $default, $tenantId);
    }
}
