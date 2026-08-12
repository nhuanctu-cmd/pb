<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMatchGovernance extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('match_disputes')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'opened_by' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'reason_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'reason' => ['type' => 'TEXT', 'null' => false],
                'evidence' => ['type' => 'JSON', 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['open', 'reviewing', 'upheld', 'rejected', 'resolved'], 'default' => 'open'],
                'resolution' => ['type' => 'TEXT', 'null' => true],
                'resolved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'resolved_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['match_id', 'status']);
            $this->forge->addKey(['tenant_id', 'created_at']);
            $this->forge->createTable('match_disputes', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('match_disputes', true);
    }
}
