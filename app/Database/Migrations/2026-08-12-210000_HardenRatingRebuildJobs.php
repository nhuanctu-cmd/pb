<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add idempotency + replay observability fields for deterministic rebuild queueing.
 */
class HardenRatingRebuildJobs extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('rating_rebuild_jobs')) {
            return;
        }

        $columns = [];
        if (! $this->db->fieldExists('idempotency_key', 'rating_rebuild_jobs')) {
            $columns['idempotency_key'] = ['type' => 'CHAR', 'constraint' => 40, 'null' => true, 'after' => 'from_match_id'];
        }
        if (! $this->db->fieldExists('attempt_count', 'rating_rebuild_jobs')) {
            $columns['attempt_count'] = ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'status'];
        }
        if (! $this->db->fieldExists('started_at', 'rating_rebuild_jobs')) {
            $columns['started_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'error_message'];
        }
        if (! $this->db->fieldExists('completed_at', 'rating_rebuild_jobs')) {
            $columns['completed_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'started_at'];
        }
        if (! $this->db->fieldExists('failed_at', 'rating_rebuild_jobs')) {
            $columns['failed_at'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'completed_at'];
        }
        if ($columns) {
            $this->forge->addColumn('rating_rebuild_jobs', $columns);
        }

        $this->ensureIndexExists('rating_rebuild_jobs', 'idx_rating_rebuild_jobs_tenant_status', 'tenant_id, status, from_match_id, created_at');
        $this->ensureIndexExists('rating_rebuild_jobs', 'idx_rating_rebuild_jobs_status', 'status, created_at');
    }

    public function down()
    {
        if (! $this->db->tableExists('rating_rebuild_jobs')) {
            return;
        }

        $this->dropIndexIfExists('rating_rebuild_jobs', 'idx_rating_rebuild_jobs_tenant_status');
        $this->dropIndexIfExists('rating_rebuild_jobs', 'idx_rating_rebuild_jobs_status');

        foreach (['idempotency_key', 'attempt_count', 'started_at', 'completed_at', 'failed_at'] as $column) {
            if ($this->db->fieldExists($column, 'rating_rebuild_jobs')) {
                $this->forge->dropColumn('rating_rebuild_jobs', $column);
            }
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

    private function ensureIndexExists(string $table, string $name, string $columns): void
    {
        if ($this->hasIndex($table, $name)) {
            return;
        }

        $this->db->query('CREATE INDEX ' . $name . ' ON ' . $table . ' (' . $columns . ')');
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! $this->hasIndex($table, $name)) {
            return;
        }

        $this->db->query('DROP INDEX ' . $name . ' ON ' . $table);
    }
}
