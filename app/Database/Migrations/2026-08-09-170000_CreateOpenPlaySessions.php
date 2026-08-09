<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpenPlaySessions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'host_player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'session_date' => ['type' => 'DATE'],
            'start_time' => ['type' => 'TIME'],
            'end_time' => ['type' => 'TIME'],
            'capacity' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true, 'default' => 4],
            'min_level' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'max_level' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'price_per_player' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'visibility' => ['type' => 'ENUM', 'constraint' => ['public', 'private', 'club_only'], 'default' => 'public'],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'open', 'full', 'started', 'completed', 'cancelled'], 'default' => 'draft'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'session_date', 'status']);
        $this->forge->addKey(['tenant_id', 'branch_id', 'session_date']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('host_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('open_play_sessions');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'session_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['requested', 'approved', 'waitlisted', 'rejected', 'cancelled'], 'default' => 'requested'],
            'requested_at' => ['type' => 'DATETIME', 'null' => true],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['session_id', 'player_id']);
        $this->forge->addKey(['tenant_id', 'session_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('session_id', 'open_play_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('open_play_session_players');
    }

    public function down()
    {
        $this->forge->dropTable('open_play_session_players', true);
        $this->forge->dropTable('open_play_sessions', true);
    }
}
