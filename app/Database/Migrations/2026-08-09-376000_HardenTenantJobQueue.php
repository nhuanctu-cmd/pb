<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class HardenTenantJobQueue extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('jobs')) return;
        $columns = [];
        if (! $this->db->fieldExists('tenant_id', 'jobs')) $columns['tenant_id'] = ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'id'];
        if (! $this->db->fieldExists('idempotency_key', 'jobs')) $columns['idempotency_key'] = ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true, 'after' => 'queue'];
        if ($columns) $this->forge->addColumn('jobs', $columns);
        // Non-unique index is intentionally used for compatibility with old
        // duplicate rows; JobModel enforces idempotency before insert.
        $this->db->query('CREATE INDEX idx_jobs_tenant_queue ON jobs (tenant_id, queue, reserved_at, available_at)');
        $this->db->query('CREATE INDEX idx_jobs_idempotency ON jobs (queue, idempotency_key)');
    }

    public function down()
    {
        if (! $this->db->tableExists('jobs')) return;
        $this->db->query('DROP INDEX idx_jobs_tenant_queue ON jobs');
        $this->db->query('DROP INDEX idx_jobs_idempotency ON jobs');
        if ($this->db->fieldExists('idempotency_key', 'jobs')) $this->forge->dropColumn('jobs', 'idempotency_key');
        if ($this->db->fieldExists('tenant_id', 'jobs')) $this->forge->dropColumn('jobs', 'tenant_id');
    }
}
