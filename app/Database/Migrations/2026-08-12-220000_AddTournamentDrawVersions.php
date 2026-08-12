<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTournamentDrawVersions extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tournament_draw_versions')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true],
                'tournament_id' => ['type' => 'INT', 'unsigned' => true],
                'category_id' => ['type' => 'INT', 'unsigned' => true],
                'draw_policy_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
                'draw_policy_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
                'draw_policy_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
                'draw_signature' => ['type' => 'CHAR', 'constraint' => 64, 'null' => false],
                'draw_seed' => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
                'participant_count' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'participant_snapshot' => ['type' => 'JSON', 'null' => true],
                'draw_config' => ['type' => 'JSON', 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'replaced', 'archived'], 'default' => 'draft'],
                'reason' => ['type' => 'TEXT', 'null' => true],
                'metadata' => ['type' => 'JSON', 'null' => true],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'tournament_id', 'category_id']);
            $this->forge->addKey(['tournament_id', 'category_id', 'status']);
            $this->forge->addKey('draw_signature');
            $this->forge->createTable('tournament_draw_versions', true);
        }

        if ($this->db->tableExists('tournament_matches') && ! $this->db->fieldExists('draw_version_id', 'tournament_matches')) {
            $this->forge->addColumn('tournament_matches', [
                'draw_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'match_no'],
            ]);
            if (! $this->hasIndex('tournament_matches', 'idx_tournament_matches_draw_version')) {
                $this->db->query('CREATE INDEX idx_tournament_matches_draw_version ON tournament_matches (draw_version_id)');
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tournament_matches') && $this->db->fieldExists('draw_version_id', 'tournament_matches')) {
            if ($this->hasIndex('tournament_matches', 'idx_tournament_matches_draw_version')) {
                $this->db->query('DROP INDEX idx_tournament_matches_draw_version ON tournament_matches');
            }
            $this->forge->dropColumn('tournament_matches', 'draw_version_id');
        }
        if ($this->db->tableExists('tournament_draw_versions')) {
            $this->forge->dropTable('tournament_draw_versions', true);
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        if (! $this->db->tableExists($table)) {
            return false;
        }

        $row = $this->db->query(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $indexName]
        )->getRow();

        return (bool) $row;
    }
}
