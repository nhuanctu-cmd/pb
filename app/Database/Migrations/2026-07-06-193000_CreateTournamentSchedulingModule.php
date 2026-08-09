<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTournamentSchedulingModule extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tournament_groups')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'tournament_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'category_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'group_name'    => ['type' => 'VARCHAR', 'constraint' => 100],
                'sort_order'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'tournament_id', 'category_id']);
            $this->forge->createTable('tournament_groups', true);
        }

        if (! $this->db->tableExists('tournament_group_teams')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'group_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'team_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'seed_no'    => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'group_id']);
            $this->forge->addUniqueKey(['group_id', 'team_id']);
            $this->forge->createTable('tournament_group_teams', true);
        }

        if (! $this->db->tableExists('tournament_matches')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'tournament_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'category_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'group_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'round_name'     => ['type' => 'VARCHAR', 'constraint' => 100],
                'match_no'       => ['type' => 'INT', 'constraint' => 11],
                'court_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'scheduled_date' => ['type' => 'DATE', 'null' => true],
                'start_time'     => ['type' => 'TIME', 'null' => true],
                'end_time'       => ['type' => 'TIME', 'null' => true],
                'team_a_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'team_b_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'winner_team_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'status'         => ['type' => 'ENUM', 'constraint' => ['scheduled', 'running', 'completed', 'cancelled'], 'default' => 'scheduled'],
                'is_locked'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'tournament_id', 'category_id']);
            $this->forge->addKey(['court_id', 'scheduled_date', 'start_time']);
            $this->forge->createTable('tournament_matches', true);
        }

        if (! $this->db->tableExists('tournament_brackets')) {
            $this->forge->addField([
                'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'tournament_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'category_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'match_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'parent_match_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'next_match_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'bracket_position' => ['type' => 'VARCHAR', 'constraint' => 50],
                'round_no'         => ['type' => 'INT', 'constraint' => 11],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
                'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'tournament_id', 'category_id']);
            $this->forge->createTable('tournament_brackets', true);
        }

        if (! $this->db->tableExists('tournament_schedule_locks')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'tournament_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'lock_type'     => ['type' => 'ENUM', 'constraint' => ['match', 'court', 'team', 'time']],
                'ref_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'reason'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'tournament_id', 'lock_type']);
            $this->forge->createTable('tournament_schedule_locks', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('tournament_schedule_locks', true);
        $this->forge->dropTable('tournament_brackets', true);
        $this->forge->dropTable('tournament_matches', true);
        $this->forge->dropTable('tournament_group_teams', true);
        $this->forge->dropTable('tournament_groups', true);
    }
}
