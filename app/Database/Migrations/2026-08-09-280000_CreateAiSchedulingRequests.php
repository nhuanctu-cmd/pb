<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiSchedulingRequests extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tournament_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'requested_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'date_from' => ['type' => 'DATE'],
            'date_to' => ['type' => 'DATE'],
            'match_minutes' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 60],
            'rest_minutes' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 30],
            'provider' => ['type' => 'ENUM', 'constraint' => ['local', 'or_tools'], 'default' => 'local'],
            'constraints_json' => ['type' => 'JSON', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['queued', 'running', 'completed', 'failed', 'cancelled'], 'default' => 'queued'],
            'result_json' => ['type' => 'JSON', 'null' => true],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status', 'created_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('tournament_id', 'tournaments', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('ai_scheduling_requests');
    }

    public function down()
    {
        $this->forge->dropTable('ai_scheduling_requests', true);
    }
}
