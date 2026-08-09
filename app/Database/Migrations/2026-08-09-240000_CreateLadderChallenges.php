<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLadderChallenges extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'event_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'challenger_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'opponent_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'fixture_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'scheduled_date' => ['type' => 'DATE', 'null' => true],
            'start_time' => ['type' => 'TIME', 'null' => true],
            'score_challenger' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'score_opponent' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'winner_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['requested', 'accepted', 'rejected', 'completed', 'cancelled'], 'default' => 'requested'],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'event_id', 'status']);
        $this->forge->addUniqueKey(['event_id', 'challenger_id', 'opponent_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('event_id', 'competition_events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('challenger_id', 'competition_participants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('opponent_id', 'competition_participants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('fixture_id', 'competition_fixtures', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('winner_id', 'competition_participants', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('competition_ladder_challenges');
    }

    public function down()
    {
        $this->forge->dropTable('competition_ladder_challenges', true);
    }
}
