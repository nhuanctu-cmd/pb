<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Adds tenant-safe club ownership/assignment to facility operations. */
class CreateFacilityClubOperations extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('courts') && ! $this->db->fieldExists('club_id', 'courts')) {
            $this->forge->addColumn('courts', [
                'club_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tenant_id'],
            ]);
            $this->db->query('ALTER TABLE `courts` ADD INDEX `idx_courts_tenant_club` (`tenant_id`, `club_id`)');
        }

        if (! $this->db->tableExists('facility_club_assignments')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'facility_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'club_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'status'      => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
                'is_primary'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'notes'       => ['type' => 'TEXT', 'null' => true],
                'created_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'updated_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tenant_id', 'facility_id', 'club_id']);
            $this->forge->addKey(['tenant_id', 'facility_id', 'status']);
            $this->forge->addKey(['tenant_id', 'club_id', 'status']);
            $this->forge->createTable('facility_club_assignments', true);
        }
    }

    public function down()
    {
        // Keep operational relationships recoverable on rollback; this migration
        // is intentionally non-destructive for existing court ownership data.
    }
}
