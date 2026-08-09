<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run()
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Hệ thống Pickleball', 'type' => 'text', 'group' => 'general', 'is_public' => 1],
            ['key' => 'default_timezone', 'value' => 'Asia/Ho_Chi_Minh', 'type' => 'text', 'group' => 'general'],
            ['key' => 'currency', 'value' => 'VND', 'type' => 'text', 'group' => 'general'],
            ['key' => 'date_format', 'value' => 'd/m/Y', 'type' => 'text', 'group' => 'general'],
            ['key' => 'time_format', 'value' => 'H:i', 'type' => 'text', 'group' => 'general'],
            ['key' => 'email_signature', 'value' => '<p>Trân trọng,<br>Hệ thống Pickleball</p>', 'type' => 'textarea', 'group' => 'general'],

            ['key' => 'booking_prefix', 'value' => 'BK', 'type' => 'text', 'group' => 'booking'],
            ['key' => 'max_booking_days_ahead', 'value' => '7', 'type' => 'number', 'group' => 'booking'],
            ['key' => 'auto_cancel_minutes', 'value' => '30', 'type' => 'number', 'group' => 'booking'],
            ['key' => 'deposit_percent', 'value' => '30', 'type' => 'number', 'group' => 'booking'],

            ['key' => 'vat_percent', 'value' => '8', 'type' => 'number', 'group' => 'payment'],
            ['key' => 'enable_wallet', 'value' => '1', 'type' => 'boolean', 'group' => 'payment'],
            ['key' => 'payment_qr_timeout', 'value' => '15', 'type' => 'number', 'group' => 'payment'],

            ['key' => 'sms_provider', 'value' => '', 'type' => 'text', 'group' => 'notifications'],
            ['key' => 'enable_email_notifications', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications'],

            ['key' => 'opening_time', 'value' => '06:00', 'type' => 'text', 'group' => 'business'],
            ['key' => 'closing_time', 'value' => '22:00', 'type' => 'text', 'group' => 'business'],
            ['key' => 'slot_duration', 'value' => '60', 'type' => 'number', 'group' => 'business'],
            ['key' => 'default_branch_id', 'value' => '', 'type' => 'number', 'group' => 'business'],

            ['key' => 'pagination_per_page', 'value' => '20', 'type' => 'number', 'group' => 'system'],
            ['key' => 'upload_max_size', 'value' => '10', 'type' => 'number', 'group' => 'system'],
            ['key' => 'upload_allowed_types', 'value' => 'jpg,jpeg,png,gif,pdf,doc,docx', 'type' => 'text', 'group' => 'system'],
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'system'],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($settings as $setting) {
            $setting['is_public'] = $setting['is_public'] ?? 0;
            $setting['is_active'] = 1;
            $setting['status']    = 'active';
            $setting['tenant_id'] = null;
            $setting['branch_id'] = null;
            $setting['created_by'] = 1;
            $setting['updated_at'] = $now;

            $existing = $this->db->table('settings')->where('key', $setting['key'])
                                 ->where('tenant_id', null)->get()->getRowArray();
            if ($existing) {
                $this->db->table('settings')->where('id', $existing['id'])->update($setting);
            } else {
                $setting['created_at'] = $now;
                $this->db->table('settings')->insert($setting);
            }
        }
    }
}
