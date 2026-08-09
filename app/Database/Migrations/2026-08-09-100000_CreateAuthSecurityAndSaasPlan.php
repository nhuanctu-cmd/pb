<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * M1 — Core: bảo mật xác thực + gói dịch vụ SaaS
 *
 * - login_attempts:        chống brute-force (ghi lại mọi lần đăng nhập)
 * - password_histories:    lịch sử mật khẩu (chống dùng lại)
 * - password_reset_tokens: token quên mật khẩu
 * - user_sessions:         theo dõi phiên đăng nhập
 * - tenant_plans:          gói dịch vụ (Free/Pro/Enterprise)
 * - tenant_subscriptions:  đăng ký gói của tenant
 * - tenant_usage:          theo dõi hạn mức sử dụng theo tháng
 */
class CreateAuthSecurityAndSaasPlan extends Migration
{
    public function up()
    {
        // 1. login_attempts
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'success'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0],
            'attempted_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['email', 'success', 'attempted_at']);
        $this->forge->addKey(['ip_address', 'attempted_at']);
        $this->forge->createTable('login_attempts', true, ['ENGINE' => 'InnoDB']);

        // 2. password_histories
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'password'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->createTable('password_histories', true, ['ENGINE' => 'InnoDB']);

        // 3. password_reset_tokens
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'token'      => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'expires_at' => ['type' => 'DATETIME', 'null' => false],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_ip' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['email', 'token']);
        $this->forge->addKey(['token', 'expires_at']);
        $this->forge->createTable('password_reset_tokens', true, ['ENGINE' => 'InnoDB']);

        // 4. user_sessions
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'session_id'    => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_activity' => ['type' => 'DATETIME', 'null' => false],
            'created_at'    => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('session_id');
        $this->forge->addKey(['user_id', 'last_activity']);
        $this->forge->createTable('user_sessions', true, ['ENGINE' => 'InnoDB']);

        // 5. tenant_plans
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'name_vi'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'name_en'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'description_vi' => ['type' => 'TEXT', 'null' => true],
            'description_en' => ['type' => 'TEXT', 'null' => true],
            'max_branches'   => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 1],
            'max_courts'     => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 5],
            'max_players'    => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 100],
            'max_staff'      => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 5],
            'price_monthly'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'price_yearly'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'features'       => ['type' => 'TEXT', 'null' => true, 'comment' => 'JSON: tournament,pos,ai_scheduling,api_access...'],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 1],
            'created_at'     => ['type' => 'DATETIME', 'null' => false],
            'updated_at'     => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('tenant_plans', true, ['ENGINE' => 'InnoDB']);

        // 6. tenant_subscriptions
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'plan_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'status'        => ['type' => 'ENUM', 'constraint' => ['trial', 'active', 'expired', 'cancelled'], 'null' => false, 'default' => 'trial'],
            'starts_at'     => ['type' => 'DATE', 'null' => false],
            'ends_at'       => ['type' => 'DATE', 'null' => true],
            'trial_ends_at' => ['type' => 'DATE', 'null' => true],
            'cancelled_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => false],
            'updated_at'    => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addKey(['status', 'ends_at']);
        $this->forge->createTable('tenant_subscriptions', true, ['ENGINE' => 'InnoDB']);

        // 7. tenant_usage
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'metric'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'comment' => 'bookings,api_calls,storage_mb'],
            'period'     => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => false, 'comment' => 'YYYY-MM'],
            'used_count' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tenant_id', 'metric', 'period']);
        $this->forge->createTable('tenant_usage', true, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_usage', true);
        $this->forge->dropTable('tenant_subscriptions', true);
        $this->forge->dropTable('tenant_plans', true);
        $this->forge->dropTable('user_sessions', true);
        $this->forge->dropTable('password_reset_tokens', true);
        $this->forge->dropTable('password_histories', true);
        $this->forge->dropTable('login_attempts', true);
    }
}
