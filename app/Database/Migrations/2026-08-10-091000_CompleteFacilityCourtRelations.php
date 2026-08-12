<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Completes legacy facility relations that may be absent in older installs. */
class CompleteFacilityCourtRelations extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('branches') && ! $this->db->fieldExists('facility_id', 'branches')) {
            $this->forge->addColumn('branches', [
                'facility_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tenant_id'],
            ]);
            $this->db->query('ALTER TABLE `branches` ADD INDEX `idx_branches_tenant_facility` (`tenant_id`, `facility_id`)');
        }

        if ($this->db->tableExists('courts') && ! $this->db->fieldExists('facility_id', 'courts')) {
            $this->forge->addColumn('courts', [
                'facility_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tenant_id'],
            ]);
            $this->db->query('ALTER TABLE `courts` ADD INDEX `idx_courts_tenant_facility` (`tenant_id`, `facility_id`)');
        }
    }

    public function down()
    {
        // Preserve relation columns during rollback for legacy compatibility.
    }
}
