<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeder mẫu thông báo email/in-app vi/en
 */
class NotificationTemplateSeeder extends Seeder
{
    private const TEMPLATES = [
        [
            'code'      => 'password_reset',
            'channel'   => 'email',
            'locales'   => [
                'vi' => [
                    'subject' => 'Đặt lại mật khẩu - {{site_name}}',
                    'body'    => "<p>Chào {{full_name}},</p><p>Bạn vừa yêu cầu đặt lại mật khẩu. Nhấn vào liên kết bên dưới:</p><p><a href='{{reset_url}}'>Đặt lại mật khẩu</a></p><p>Liên kết hết hạn sau {{expires}} phút.</p>",
                ],
                'en' => [
                    'subject' => 'Reset your password - {{site_name}}',
                    'body'    => "<p>Hi {{full_name}},</p><p>You requested a password reset. Click the link below:</p><p><a href='{{reset_url}}'>Reset password</a></p><p>The link expires in {{expires}} minutes.</p>",
                ],
            ],
            'variables' => ['site_name', 'full_name', 'reset_url', 'expires'],
        ],
        [
            'code'      => 'booking_confirmation',
            'channel'   => 'email',
            'locales'   => [
                'vi' => [
                    'subject' => 'Xác nhận đặt sân {{booking_code}}',
                    'body'    => "<p>Chào {{customer_name}},</p><p>Đặt sân của bạn đã được xác nhận:</p><ul><li>Mã đặt: {{booking_code}}</li><li>Sân: {{court_name}}</li><li>Thờị gian: {{start_time}} - {{end_time}}</li><li>Tổng tiền: {{total_amount}}</li></ul>",
                ],
                'en' => [
                    'subject' => 'Booking confirmation {{booking_code}}',
                    'body'    => "<p>Hi {{customer_name}},</p><p>Your booking is confirmed:</p><ul><li>Code: {{booking_code}}</li><li>Court: {{court_name}}</li><li>Time: {{start_time}} - {{end_time}}</li><li>Total: {{total_amount}}</li></ul>",
                ],
            ],
            'variables' => ['customer_name', 'booking_code', 'court_name', 'start_time', 'end_time', 'total_amount'],
        ],
        [
            'code'      => 'welcome_user',
            'channel'   => 'in_app',
            'locales'   => [
                'vi' => [
                    'subject' => 'Chào mừng {{full_name}}',
                    'body'    => 'Tài khoản của bạn đã được tạo trên {{site_name}}.',
                ],
                'en' => [
                    'subject' => 'Welcome {{full_name}}',
                    'body'    => 'Your account has been created on {{site_name}}.',
                ],
            ],
            'variables' => ['full_name', 'site_name'],
        ],
        [
            'code'      => 'booking_reminder',
            'channel'   => 'in_app',
            'locales'   => [
                'vi' => [
                    'subject' => 'Nhắc nhở đặt sân {{booking_code}}',
                    'body'    => 'Bạn có lịch đặt sân {{court_name}} vào {{start_time}}.',
                ],
                'en' => [
                    'subject' => 'Booking reminder {{booking_code}}',
                    'body'    => 'You have a court booking at {{court_name}} on {{start_time}}.',
                ],
            ],
            'variables' => ['booking_code', 'court_name', 'start_time'],
        ],
        [
            'code'      => 'coaching_player_approved',
            'channel'   => 'in_app',
            'locales'   => [
                'vi' => ['subject' => 'Coaching đã được duyệt', 'body' => 'Bạn đã được duyệt vào session {{session_title}}.'],
                'en' => ['subject' => 'Coaching approved', 'body' => 'You were approved for {{session_title}}.'],
            ],
            'variables' => ['full_name', 'session_title'],
        ],
        [
            'code'      => 'ladder_challenge_received',
            'channel'   => 'in_app',
            'locales'   => [
                'vi' => ['subject' => 'Bạn nhận được ladder challenge', 'body' => '{{challenger_name}} đã thách đấu bạn trong {{event_name}}.'],
                'en' => ['subject' => 'New ladder challenge', 'body' => '{{challenger_name}} challenged you in {{event_name}}.'],
            ],
            'variables' => ['challenger_name', 'event_name'],
        ],
        [
            'code'      => 'ladder_challenge_accepted',
            'channel'   => 'in_app',
            'locales'   => [
                'vi' => ['subject' => 'Ladder challenge được chấp nhận', 'body' => '{{opponent_name}} đã chấp nhận challenge của bạn.'],
                'en' => ['subject' => 'Ladder challenge accepted', 'body' => '{{opponent_name}} accepted your challenge.'],
            ],
            'variables' => ['opponent_name'],
        ],
        [
            'code'      => 'ladder_challenge_rejected',
            'channel'   => 'in_app',
            'locales'   => [
                'vi' => ['subject' => 'Ladder challenge bị từ chối', 'body' => '{{opponent_name}} đã từ chối challenge của bạn.'],
                'en' => ['subject' => 'Ladder challenge rejected', 'body' => '{{opponent_name}} rejected your challenge.'],
            ],
            'variables' => ['opponent_name'],
        ],
    ];

    public function run()
    {
        $db   = $this->db;
        $now  = date('Y-m-d H:i:s');
        $rows = 0;

        foreach (self::TEMPLATES as $template) {
            foreach ($template['locales'] as $locale => $data) {
                $existing = $db->table('notification_templates')
                    ->where('code', $template['code'])
                    ->where('channel', $template['channel'])
                    ->where('locale', $locale)
                    ->get()->getRowArray();

                $record = [
                    'code'        => $template['code'],
                    'channel'     => $template['channel'],
                    'locale'      => $locale,
                    'subject'     => $data['subject'],
                    'body'        => $data['body'],
                    'variables'   => json_encode($template['variables']),
                    'is_active'   => 1,
                    'created_by'  => 1,
                    'updated_by'  => 1,
                    'updated_at'  => $now,
                ];

                if ($existing) {
                    $db->table('notification_templates')->where('id', $existing['id'])->update($record);
                } else {
                    $record['created_at'] = $now;
                    $db->table('notification_templates')->insert($record);
                }
                $rows++;
            }
        }

        echo "Notification templates: {$rows} mẫu đã đồng bộ.\n";
    }
}
