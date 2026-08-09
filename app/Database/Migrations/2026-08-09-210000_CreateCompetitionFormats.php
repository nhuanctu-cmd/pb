<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompetitionFormats extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'tournament_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'format' => ['type' => 'ENUM', 'constraint' => ['round_robin', 'league', 'ladder'], 'default' => 'round_robin'],
            'scoring_rules' => ['type' => 'JSON', 'null' => true],
            'start_date' => ['type' => 'DATE', 'null' => true],
            'end_date' => ['type' => 'DATE', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'open', 'running', 'completed', 'cancelled'], 'default' => 'draft'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'format', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('tournament_id', 'tournaments', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('competition_events');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'event_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'team_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'display_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'seed' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'withdrawn'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'event_id', 'status']);
        $this->forge->addUniqueKey(['event_id', 'team_id', 'player_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('event_id', 'competition_events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('team_id', 'teams', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('competition_participants');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'event_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'round_no' => ['type' => 'SMALLINT', 'unsigned' => true],
            'match_no' => ['type' => 'SMALLINT', 'unsigned' => true],
            'participant_a_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'participant_b_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'scheduled_date' => ['type' => 'DATE', 'null' => true],
            'start_time' => ['type' => 'TIME', 'null' => true],
            'court_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'score_a' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'score_b' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
            'winner_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['scheduled', 'in_progress', 'completed', 'cancelled'], 'default' => 'scheduled'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['event_id', 'round_no', 'match_no']);
        $this->forge->addKey(['tenant_id', 'event_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('event_id', 'competition_events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('participant_a_id', 'competition_participants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('participant_b_id', 'competition_participants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('winner_id', 'competition_participants', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('competition_fixtures');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'event_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'participant_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'played' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'wins' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'draws' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'losses' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'points_for' => ['type' => 'INT', 'default' => 0],
            'points_against' => ['type' => 'INT', 'default' => 0],
            'points' => ['type' => 'INT', 'default' => 0],
            'rank_no' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['event_id', 'participant_id']);
        $this->forge->addKey(['tenant_id', 'event_id', 'rank_no']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('event_id', 'competition_events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('participant_id', 'competition_participants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('competition_standings');
    }

    public function down()
    {
        $this->forge->dropTable('competition_standings', true);
        $this->forge->dropTable('competition_fixtures', true);
        $this->forge->dropTable('competition_participants', true);
        $this->forge->dropTable('competition_events', true);
    }
}
