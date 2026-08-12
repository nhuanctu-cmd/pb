<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMatchIdentityReview extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('match_identity_conflicts')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true],
                'source_type' => ['type' => 'VARCHAR', 'constraint' => 50],
                'source_id' => ['type' => 'BIGINT', 'unsigned' => true],
                'canonical_match_id' => ['type' => 'INT', 'unsigned' => true],
                'duplicate_match_id' => ['type' => 'INT', 'unsigned' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['open', 'resolved', 'ignored'], 'default' => 'open'],
                'reason' => ['type' => 'TEXT', 'null' => true],
                'resolved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'resolved_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tenant_id', 'source_type', 'source_id', 'duplicate_match_id']);
            $this->forge->addKey(['tenant_id', 'status']);
            $this->forge->createTable('match_identity_conflicts', true);
        }

        if ($this->db->tableExists('matches') && ! $this->hasIndex('matches', 'idx_matches_source_identity')) {
            $this->db->query('ALTER TABLE matches ADD INDEX idx_matches_source_identity (tenant_id, source_type, source_id)');
        }

        if (! $this->db->tableExists('matches')) return;
        $groups = $this->db->query('SELECT tenant_id, source_type, source_id, MIN(id) AS canonical_match_id, COUNT(*) AS match_count FROM matches WHERE source_id IS NOT NULL GROUP BY tenant_id, source_type, source_id HAVING COUNT(*) > 1')->getResult();
        foreach ($groups as $group) {
            $duplicates = $this->db->table('matches')->where('tenant_id', $group->tenant_id)->where('source_type', $group->source_type)->where('source_id', $group->source_id)->where('id !=', $group->canonical_match_id)->get()->getResult();
            foreach ($duplicates as $duplicate) {
                $exists = $this->db->table('match_identity_conflicts')->where('tenant_id', $group->tenant_id)->where('source_type', $group->source_type)->where('source_id', $group->source_id)->where('duplicate_match_id', $duplicate->id)->countAllResults();
                if (! $exists) {
                    $this->db->table('match_identity_conflicts')->insert([
                        'tenant_id' => (int) $group->tenant_id,
                        'source_type' => $group->source_type,
                        'source_id' => (int) $group->source_id,
                        'canonical_match_id' => (int) $group->canonical_match_id,
                        'duplicate_match_id' => (int) $duplicate->id,
                        'status' => 'open',
                        'reason' => 'Duplicate source identity detected during non-destructive audit.',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('matches') && $this->hasIndex('matches', 'idx_matches_source_identity')) {
            $this->db->query('ALTER TABLE matches DROP INDEX idx_matches_source_identity');
        }
        $this->forge->dropTable('match_identity_conflicts', true);
    }

    private function hasIndex(string $table, string $index): bool
    {
        return (bool) $this->db->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index])->getRow();
    }
}
