<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOpenPlayRotations extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'session_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'round_no' => ['type' => 'SMALLINT', 'constraint' => 5, 'unsigned' => true],
            'start_time' => ['type' => 'TIME'],
            'end_time' => ['type' => 'TIME'],
            'status' => ['type' => 'ENUM', 'constraint' => ['planned', 'active', 'completed', 'cancelled'], 'default' => 'planned'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['session_id', 'round_no']);
        $this->forge->addKey(['tenant_id', 'session_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('session_id', 'open_play_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('open_play_rotation_rounds');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'round_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'team_side' => ['type' => 'ENUM', 'constraint' => ['A', 'B'], 'default' => 'A'],
            'partner_player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'opponent_player_ids' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['round_id', 'player_id']);
        $this->forge->addKey(['tenant_id', 'player_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('round_id', 'open_play_rotation_rounds', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('partner_player_id', 'players', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('open_play_rotation_players');
    }

    public function down()
    {
        $this->forge->dropTable('open_play_rotation_players', true);
        $this->forge->dropTable('open_play_rotation_rounds', true);
    }
}
