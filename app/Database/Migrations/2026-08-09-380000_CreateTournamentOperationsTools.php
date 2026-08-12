<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTournamentOperationsTools extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tournament_templates')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true],
                'source_tournament_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 255],
                'description' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'snapshot' => ['type' => 'JSON'],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'is_active']);
            $this->forge->createTable('tournament_templates', true);
        }

        // The match-day control room needs operational states in addition to
        // the original scheduling states. Keep this guarded for old installs.
        if ($this->db->tableExists('tournament_matches')) {
            $this->db->query("ALTER TABLE tournament_matches MODIFY status ENUM('scheduled','called','on_court','running','in_progress','delayed','completed','no_show','walkover','cancelled') NOT NULL DEFAULT 'scheduled'");
            foreach ([
                'called_at' => "ALTER TABLE tournament_matches ADD called_at DATETIME NULL AFTER status",
                'actual_start_time' => "ALTER TABLE tournament_matches ADD actual_start_time DATETIME NULL AFTER called_at",
                'completed_at' => "ALTER TABLE tournament_matches ADD completed_at DATETIME NULL AFTER actual_start_time",
                'operations_note' => "ALTER TABLE tournament_matches ADD operations_note VARCHAR(500) NULL AFTER completed_at",
            ] as $field => $sql) {
                if (! $this->db->fieldExists($field, 'tournament_matches')) {
                    $this->db->query($sql);
                }
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tournament_matches')) {
            foreach (['operations_note', 'completed_at', 'actual_start_time', 'called_at'] as $field) {
                if ($this->db->fieldExists($field, 'tournament_matches')) {
                    $this->forge->dropColumn('tournament_matches', $field);
                }
            }
            $this->db->query("ALTER TABLE tournament_matches MODIFY status ENUM('scheduled','running','completed','cancelled') NOT NULL DEFAULT 'scheduled'");
        }
        if ($this->db->tableExists('tournament_templates')) {
            $this->forge->dropTable('tournament_templates', true);
        }
    }
}
