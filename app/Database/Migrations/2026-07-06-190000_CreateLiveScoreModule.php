<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLiveScoreModule extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tournament_match_scores')) {
            $this->forge->addField([
                'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'match_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'set_no'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'team_a_score'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'team_b_score'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'winner_team_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
                'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'match_id']);
            $this->forge->addUniqueKey(['match_id', 'set_no']);
            $this->forge->createTable('tournament_match_scores', true);
        }

        if (! $this->db->tableExists('tournament_score_logs')) {
            $this->forge->addField([
                'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'match_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'old_score_json' => ['type' => 'JSON', 'null' => true],
                'new_score_json' => ['type' => 'JSON', 'null' => true],
                'changed_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'reason'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'     => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'match_id', 'created_at']);
            $this->forge->createTable('tournament_score_logs', true);
        }

        if (! $this->db->tableExists('live_display_configs')) {
            $this->forge->addField([
                'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'tournament_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'display_name'      => ['type' => 'VARCHAR', 'constraint' => 255],
                'mode'              => ['type' => 'ENUM', 'constraint' => ['tv', 'kiosk', 'public'], 'default' => 'public'],
                'show_sponsor'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'show_next_matches' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'refresh_seconds'   => ['type' => 'INT', 'constraint' => 11, 'default' => 5],
                'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
                'created_at'        => ['type' => 'DATETIME', 'null' => true],
                'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'tournament_id', 'mode', 'status']);
            $this->forge->createTable('live_display_configs', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('live_display_configs', true);
        $this->forge->dropTable('tournament_score_logs', true);
        $this->forge->dropTable('tournament_match_scores', true);
    }
}
