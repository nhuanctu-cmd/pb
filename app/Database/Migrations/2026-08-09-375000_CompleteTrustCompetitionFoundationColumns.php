<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Completes additive columns when the foundation migration was already applied. */
class CompleteTrustCompetitionFoundationColumns extends Migration
{
    public function up()
    {
        $this->addColumns('matches', [
            'source_code' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
        ]);

        $this->db->query("UPDATE matches SET source_code = source_type WHERE source_code IS NULL");
    }

    public function down()
    {
        if ($this->db->tableExists('matches') && $this->db->fieldExists('source_code', 'matches')) {
            $this->forge->dropColumn('matches', 'source_code');
        }
    }

    private function addColumns(string $table, array $columns): void
    {
        if (! $this->db->tableExists($table)) return;

        $missing = [];
        foreach ($columns as $name => $definition) {
            if (! $this->db->fieldExists($name, $table)) $missing[$name] = $definition;
        }
        if ($missing) $this->forge->addColumn($table, $missing);
    }
}
