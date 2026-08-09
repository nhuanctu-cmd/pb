<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'Pickleball System', 'type' => 'text', 'group' => 'general', 'is_public' => 1],
            ['key' => 'app_description', 'value' => 'Pickleball Court Management System', 'type' => 'text', 'group' => 'general', 'is_public' => 1],
            ['key' => 'app_timezone', 'value' => 'Asia/Ho_Chi_Minh', 'type' => 'text', 'group' => 'general'],
            ['key' => 'app_locale', 'value' => 'vi', 'type' => 'text', 'group' => 'general'],
            ['key' => 'app_currency', 'value' => 'VND', 'type' => 'text', 'group' => 'general'],
            ['key' => 'app_date_format', 'value' => 'd/m/Y', 'type' => 'text', 'group' => 'general'],
            ['key' => 'app_time_format', 'value' => 'H:i', 'type' => 'text', 'group' => 'general'],
            ['key' => 'booking_prefix', 'value' => 'BK', 'type' => 'text', 'group' => 'booking'],
            ['key' => 'booking_advance_days', 'value' => '7', 'type' => 'number', 'group' => 'booking'],
            ['key' => 'booking_cancel_hours', 'value' => '2', 'type' => 'number', 'group' => 'booking'],
            ['key' => 'opening_time', 'value' => '06:00', 'type' => 'text', 'group' => 'business'],
            ['key' => 'closing_time', 'value' => '22:00', 'type' => 'text', 'group' => 'business'],
            ['key' => 'slot_duration', 'value' => '60', 'type' => 'number', 'group' => 'business'],
            ['key' => 'maintenance_day', 'value' => 'Monday', 'type' => 'text', 'group' => 'business'],
            ['key' => 'max_courts_per_branch', 'value' => '10', 'type' => 'number', 'group' => 'business'],
            ['key' => 'pagination_per_page', 'value' => '20', 'type' => 'number', 'group' => 'system'],
            ['key' => 'upload_max_size', 'value' => '10', 'type' => 'number', 'group' => 'system'],
            ['key' => 'upload_allowed_types', 'value' => 'jpg,jpeg,png,gif,pdf,doc,docx', 'type' => 'text', 'group' => 'system'],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'system'],
        ];

        foreach ($settings as &$setting) {
            $setting['is_public'] = $setting['is_public'] ?? 0;
            $setting['is_active'] = 1;
            $setting['status'] = 'active';
            $setting['tenant_id'] = null;
            $setting['branch_id'] = null;
            $setting['created_by'] = 1;
            $setting['created_at'] = date('Y-m-d H:i:s');
            $setting['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('settings')->insertBatch($settings);
    }
}
