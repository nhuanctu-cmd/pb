<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * M2 — Notification Engine + Job queue cho email/tác vụ nền
 */
class CreateNotificationsAndJobs extends Migration
{
    public function up()
    {
        // Mẫu thông báo (vi/en, đa kênh)
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'              => ['type' => 'VARCHAR', 'constraint' => 100],
            'channel'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'email'], // email, in_app, sms
            'locale'            => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'vi'],
            'subject'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'body'              => ['type' => 'TEXT'],
            'variables'         => ['type' => 'JSON', 'null' => true],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['code', 'channel', 'locale']);
        $this->forge->createTable('notification_templates');

        // Thông báo in-app của user
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'template_code'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'title'             => ['type' => 'VARCHAR', 'constraint' => 255],
            'message'           => ['type' => 'TEXT'],
            'channel'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'in_app'],
            'data'              => ['type' => 'JSON', 'null' => true],
            'is_read'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'read_at'           => ['type' => 'DATETIME', 'null' => true],
            'action_url'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'is_read']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('notifications');

        // Job queue đơn giản cho email / tác vụ nền
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'queue'             => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'default'],
            'payload'           => ['type' => 'JSON'],
            'attempts'          => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'max_attempts'      => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 3],
            'reserved_at'       => ['type' => 'DATETIME', 'null' => true],
            'available_at'      => ['type' => 'DATETIME', 'null' => true],
            'failed_at'         => ['type' => 'DATETIME', 'null' => true],
            'error_message'     => ['type' => 'TEXT', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['queue', 'reserved_at', 'available_at']);
        $this->forge->createTable('jobs');
    }

    public function down()
    {
        $this->forge->dropTable('jobs', true);
        $this->forge->dropTable('notifications', true);
        $this->forge->dropTable('notification_templates', true);
    }
}
