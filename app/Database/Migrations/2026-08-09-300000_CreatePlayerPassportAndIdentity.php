<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlayerPassportAndIdentity extends Migration
{
    public function up()
    {
        // Player competitive passport — single primary competitive identity
        if (! $this->db->tableExists('player_competitive_profiles')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'national_player_id' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
                'display_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'slug' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'avatar_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'province_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'city_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'club_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'gender_category' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'comment' => 'men, women, open per tournament rules'],
                'age_category_public' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'internal_rating_summary' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'cached derived summary'],
                'external_rating_summary' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'national_rank_summary' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'reliability_score' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
                'privacy_level' => ['type' => 'ENUM', 'constraint' => ['public', 'club', 'private'], 'default' => 'public'],
                'status' => ['type' => 'ENUM', 'constraint' => ['unverified', 'verified', 'official', 'suspended'], 'default' => 'unverified'],
                'verified_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
                'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('player_id');
            $this->forge->addUniqueKey('national_player_id');
            $this->forge->addKey(['status', 'province_id', 'club_id']);
            $this->forge->createTable('player_competitive_profiles', true);
        }

        // Identity claims used for duplicate detection
        if (! $this->db->tableExists('player_identity_claims')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'claim_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'comment' => 'phone, email, facebook_id, google_id, passport'],
                'claim_value' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'verified_at' => ['type' => 'DATETIME', 'null' => true],
                'verification_source' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['claim_type', 'claim_value']);
            $this->forge->addKey('player_id');
            $this->forge->createTable('player_identity_claims', true);
        }

        // Player merge requests with audit
        if (! $this->db->tableExists('player_merge_requests')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'source_player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'target_player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
                'reason' => ['type' => 'TEXT', 'null' => true],
                'requested_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'reviewed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['source_player_id', 'target_player_id']);
            $this->forge->createTable('player_merge_requests', true);
        }

        if (! $this->db->tableExists('player_merge_audits')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'merge_request_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'action' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'comment' => 'merge_data, undo'],
                'source_snapshot' => ['type' => 'JSON', 'null' => true],
                'target_snapshot' => ['type' => 'JSON', 'null' => true],
                'actor_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('merge_request_id');
            $this->forge->createTable('player_merge_audits', true);
        }

        // External rating providers (DUPR, etc.)
        if (! $this->db->tableExists('player_external_ratings')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'provider' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'comment' => 'DUPR, IPTPA, etc.'],
                'external_player_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'rating' => ['type' => 'DECIMAL', 'constraint' => '5,3', 'null' => true],
                'reliability' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
                'match_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'last_synced_at' => ['type' => 'DATETIME', 'null' => true],
                'sync_payload' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['player_id', 'provider']);
            $this->forge->createTable('player_external_ratings', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('player_external_ratings', true);
        $this->forge->dropTable('player_merge_audits', true);
        $this->forge->dropTable('player_merge_requests', true);
        $this->forge->dropTable('player_identity_claims', true);
        $this->forge->dropTable('player_competitive_profiles', true);
    }
}
