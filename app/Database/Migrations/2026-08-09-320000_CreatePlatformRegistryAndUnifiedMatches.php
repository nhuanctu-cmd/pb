<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlatformRegistryAndUnifiedMatches extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('player_identity_candidates')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'candidate_player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'match_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'confidence_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'status' => ['type' => 'ENUM', 'constraint' => ['open', 'confirmed', 'dismissed'], 'default' => 'open'],
                'evidence' => ['type' => 'JSON', 'null' => true],
                'reviewed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['player_id', 'candidate_player_id']);
            $this->forge->addKey(['status', 'confidence_score']);
            $this->forge->createTable('player_identity_candidates', true);
        }

        if (! $this->db->tableExists('player_club_memberships')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'club_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'role' => ['type' => 'ENUM', 'constraint' => ['member', 'coach', 'captain', 'manager', 'owner'], 'default' => 'member'],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'active', 'suspended', 'left'], 'default' => 'pending'],
                'source' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'manual'],
                'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'joined_at' => ['type' => 'DATETIME', 'null' => true],
                'left_at' => ['type' => 'DATETIME', 'null' => true],
                'verified_at' => ['type' => 'DATETIME', 'null' => true],
                'verified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'metadata' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tenant_id', 'club_id', 'player_id']);
            $this->forge->addKey(['tenant_id', 'player_id', 'status']);
            $this->forge->addKey(['club_id', 'status']);
            $this->forge->createTable('player_club_memberships', true);
        }

        if (! $this->db->tableExists('matches')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'public_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'source_type' => ['type' => 'ENUM', 'constraint' => ['tournament', 'league', 'open_play', 'club_match', 'challenge', 'friendly', 'manual'], 'default' => 'manual'],
                'source_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'discipline' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'pickleball'],
                'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'submitted', 'confirmed', 'disputed', 'official', 'cancelled'], 'default' => 'draft'],
                'result_type' => ['type' => 'ENUM', 'constraint' => ['normal', 'walkover', 'retirement', 'disqualification', 'cancelled'], 'default' => 'normal'],
                'verification_status' => ['type' => 'ENUM', 'constraint' => ['unverified', 'pending', 'verified', 'official'], 'default' => 'unverified'],
                'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'verified_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'metadata' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('public_id');
            $this->forge->addKey(['tenant_id', 'status']);
            $this->forge->addKey(['source_type', 'source_id']);
            $this->forge->addKey('scheduled_at');
            $this->forge->createTable('matches', true);
        }

        if (! $this->db->tableExists('match_participants')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'side' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false],
                'participant_role' => ['type' => 'ENUM', 'constraint' => ['player', 'captain', 'substitute'], 'default' => 'player'],
                'result' => ['type' => 'ENUM', 'constraint' => ['pending', 'won', 'lost', 'draw', 'retired', 'disqualified'], 'default' => 'pending'],
                'score' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'sort_order' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
                'metadata' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['match_id', 'player_id']);
            $this->forge->addKey(['match_id', 'side']);
            $this->forge->createTable('match_participants', true);
        }

        if (! $this->db->tableExists('match_games')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'game_no' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false],
                'side_a_score' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false],
                'side_b_score' => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false],
                'raw_score' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['match_id', 'game_no']);
            $this->forge->createTable('match_games', true);
        }

        if (! $this->db->tableExists('match_results')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'current_version_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'version_no' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
                'status' => ['type' => 'ENUM', 'constraint' => ['submitted', 'confirmed', 'official', 'disputed', 'cancelled'], 'default' => 'submitted'],
                'result_type' => ['type' => 'ENUM', 'constraint' => ['normal', 'walkover', 'retirement', 'disqualification', 'cancelled'], 'default' => 'normal'],
                'winner_side' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
                'published_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('match_id');
            $this->forge->createTable('match_results', true);
        }

        if (! $this->db->tableExists('match_result_versions')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'version_no' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['submitted', 'confirmed', 'official', 'disputed', 'cancelled'], 'default' => 'submitted'],
                'result_type' => ['type' => 'ENUM', 'constraint' => ['normal', 'walkover', 'retirement', 'disqualification', 'cancelled'], 'default' => 'normal'],
                'winner_side' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => true],
                'payload' => ['type' => 'JSON', 'null' => false],
                'submitted_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'confirmed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'change_reason' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['match_id', 'version_no']);
            $this->forge->addKey(['match_id', 'status']);
            $this->forge->createTable('match_result_versions', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('match_result_versions', true);
        $this->forge->dropTable('match_results', true);
        $this->forge->dropTable('match_games', true);
        $this->forge->dropTable('match_participants', true);
        $this->forge->dropTable('matches', true);
        $this->forge->dropTable('player_club_memberships', true);
        $this->forge->dropTable('player_identity_candidates', true);
    }
}
