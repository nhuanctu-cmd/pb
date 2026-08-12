<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLivestreamChannels extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tournament_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 180],
            'provider' => ['type' => 'ENUM', 'constraint' => ['youtube', 'facebook', 'custom'], 'default' => 'custom'],
            'stream_url' => ['type' => 'VARCHAR', 'constraint' => 500],
            'embed_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'scheduled', 'live', 'ended', 'disabled'], 'default' => 'draft'],
            'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
            'started_at' => ['type' => 'DATETIME', 'null' => true],
            'ended_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status', 'scheduled_at']);
        $this->forge->addKey(['tenant_id', 'branch_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('tournament_id', 'tournaments', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('updated_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('livestream_channels');
    }

    public function down()
    {
        $this->forge->dropTable('livestream_channels', true);
    }
}
