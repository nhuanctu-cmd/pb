<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWebhookIntegrations extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 180],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500],
            'secret_ciphertext' => ['type' => 'TEXT'],
            'event_types' => ['type' => 'JSON'],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'disabled'], 'default' => 'active'],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('webhook_endpoints');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'endpoint_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 120],
            'payload_json' => ['type' => 'JSON'],
            'signature' => ['type' => 'VARCHAR', 'constraint' => 128],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'sending', 'succeeded', 'failed'], 'default' => 'pending'],
            'attempts' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'max_attempts' => ['type' => 'INT', 'unsigned' => true, 'default' => 5],
            'next_attempt_at' => ['type' => 'DATETIME', 'null' => true],
            'response_code' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'response_body' => ['type' => 'TEXT', 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'delivered_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status', 'next_attempt_at']);
        $this->forge->addKey(['endpoint_id', 'event_type', 'created_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('endpoint_id', 'webhook_endpoints', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('webhook_deliveries');
    }

    public function down()
    {
        $this->forge->dropTable('webhook_deliveries', true);
        $this->forge->dropTable('webhook_endpoints', true);
    }
}
