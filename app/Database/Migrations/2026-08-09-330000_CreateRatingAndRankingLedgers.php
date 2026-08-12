<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRatingAndRankingLedgers extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('rating_providers')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => false],
                'provider_type' => ['type' => 'ENUM', 'constraint' => ['internal', 'external'], 'default' => 'internal'],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'paused', 'disabled'], 'default' => 'active'],
                'config' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('rating_providers', true);
            $this->db->table('rating_providers')->insert([
                'code' => 'internal-elo',
                'name' => 'Pickleball Internal ELO',
                'provider_type' => 'internal',
                'status' => 'active',
                'config' => json_encode(['k_factor' => 24, 'initial_rating' => 1000]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (! $this->db->tableExists('rating_ledgers')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'rating_provider_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'side' => ['type' => 'TINYINT', 'unsigned' => true, 'null' => false],
                'outcome' => ['type' => 'ENUM', 'constraint' => ['win', 'loss', 'draw', 'not_rated'], 'default' => 'not_rated'],
                'rating_before' => ['type' => 'DECIMAL', 'constraint' => '8,3', 'null' => false],
                'rating_after' => ['type' => 'DECIMAL', 'constraint' => '8,3', 'null' => false],
                'rating_delta' => ['type' => 'DECIMAL', 'constraint' => '8,3', 'null' => false],
                'reliability_before' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'reliability_after' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'calculation_version' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'elo-v1'],
                'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
                'metadata' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('idempotency_key');
            $this->forge->addKey(['player_id', 'rating_provider_id', 'created_at']);
            $this->forge->addKey(['match_id', 'rating_provider_id']);
            $this->forge->createTable('rating_ledgers', true);
        }

        if (! $this->db->tableExists('rating_rebuild_jobs')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'rating_provider_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'from_match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['queued', 'running', 'completed', 'failed'], 'default' => 'queued'],
                'payload' => ['type' => 'JSON', 'null' => true],
                'error_message' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['status', 'created_at']);
            $this->forge->createTable('rating_rebuild_jobs', true);
        }

        if (! $this->db->tableExists('ranking_authorities')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
                'scope' => ['type' => 'ENUM', 'constraint' => ['tenant', 'regional', 'national'], 'default' => 'national'],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'paused', 'archived'], 'default' => 'active'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('code');
            $this->forge->createTable('ranking_authorities', true);
            $this->db->table('ranking_authorities')->insert([
                'code' => 'national-pickleball',
                'name' => 'National Pickleball Ranking',
                'scope' => 'national',
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (! $this->db->tableExists('ranking_policies')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'authority_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'code' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => false],
                'name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
                'season' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
                'rules' => ['type' => 'JSON', 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'archived'], 'default' => 'draft'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['authority_id', 'code']);
            $this->forge->addKey(['authority_id', 'season', 'status']);
            $this->forge->createTable('ranking_policies', true);
            $authority = $this->db->table('ranking_authorities')->where('code', 'national-pickleball')->get()->getRow();
            if ($authority) {
                $this->db->table('ranking_policies')->insert([
                    'authority_id' => $authority->id,
                    'code' => 'official-match-v1',
                    'name' => 'Official Match Points v1',
                    'season' => date('Y'),
                    'rules' => json_encode(['win' => 10, 'loss' => 1, 'draw' => 5, 'walkover_win' => 8]),
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        if (! $this->db->tableExists('ranking_point_ledgers')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'authority_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'policy_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'match_id' => ['type' => 'INT', 'unsigned' => false, 'null' => false],
                'points' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
                'reason' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => false],
                'metadata' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('idempotency_key');
            $this->forge->addKey(['authority_id', 'policy_id', 'player_id', 'created_at']);
            $this->forge->addKey(['match_id', 'policy_id']);
            $this->forge->createTable('ranking_point_ledgers', true);
        }

        if (! $this->db->tableExists('ranking_snapshots')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'authority_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'policy_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'snapshot_date' => ['type' => 'DATE', 'null' => false],
                'rank_position' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'points' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
                'match_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'metadata' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['authority_id', 'policy_id', 'tenant_id', 'player_id', 'snapshot_date']);
            $this->forge->addKey(['authority_id', 'policy_id', 'snapshot_date', 'rank_position']);
            $this->forge->createTable('ranking_snapshots', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ranking_snapshots', true);
        $this->forge->dropTable('ranking_point_ledgers', true);
        $this->forge->dropTable('ranking_policies', true);
        $this->forge->dropTable('ranking_authorities', true);
        $this->forge->dropTable('rating_rebuild_jobs', true);
        $this->forge->dropTable('rating_ledgers', true);
        $this->forge->dropTable('rating_providers', true);
    }
}
