<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQueueDeadLetterMetadata extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('jobs')) return;
        if (! $this->db->fieldExists('dead_lettered_at', 'jobs')) $this->forge->addColumn('jobs', ['dead_lettered_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'failed_at']]);
        if (! $this->db->fieldExists('dead_letter_reason', 'jobs')) $this->forge->addColumn('jobs', ['dead_letter_reason' => ['type' => 'TEXT', 'null' => true, 'after' => 'error_message']]);
        $this->db->query('CREATE INDEX idx_jobs_dead_letter ON jobs (tenant_id, dead_lettered_at, failed_at)');
    }

    public function down()
    {
        if (! $this->db->tableExists('jobs')) return;
        $this->db->query('DROP INDEX idx_jobs_dead_letter ON jobs');
        if ($this->db->fieldExists('dead_letter_reason', 'jobs')) $this->forge->dropColumn('jobs', 'dead_letter_reason');
        if ($this->db->fieldExists('dead_lettered_at', 'jobs')) $this->forge->dropColumn('jobs', 'dead_lettered_at');
    }
}
