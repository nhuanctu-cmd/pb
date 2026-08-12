<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Append-only staging for club/external rating imports; official ledgers are never imported directly. */
class CreateRatingImportWorkflow extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('rating_import_jobs')) {
            $this->forge->addField(['id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false], 'source_type' => ['type' => 'ENUM', 'constraint' => ['club', 'coach', 'external_provider'], 'null' => false], 'status' => ['type' => 'ENUM', 'constraint' => ['uploaded', 'previewed', 'matching', 'validated', 'verified', 'imported', 'rejected'], 'default' => 'uploaded'], 'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true], 'source_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true], 'metadata' => ['type' => 'JSON', 'null' => true], 'error_message' => ['type' => 'TEXT', 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true]]);
            $this->forge->addKey('id', true); $this->forge->addKey(['tenant_id', 'status', 'created_at']); $this->forge->createTable('rating_import_jobs', true);
        }
        if (! $this->db->tableExists('rating_import_rows')) {
            $this->forge->addField(['id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'job_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false], 'row_number' => ['type' => 'INT', 'unsigned' => true, 'null' => false], 'raw_data' => ['type' => 'JSON', 'null' => false], 'normalized_data' => ['type' => 'JSON', 'null' => true], 'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true], 'identity_status' => ['type' => 'ENUM', 'constraint' => ['unmatched', 'matched', 'ambiguous', 'duplicate'], 'default' => 'unmatched'], 'validation_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'valid', 'invalid'], 'default' => 'pending'], 'verification_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'verified', 'rejected'], 'default' => 'pending'], 'validation_errors' => ['type' => 'JSON', 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true]]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey(['job_id', 'row_number']); $this->forge->addKey(['job_id', 'identity_status', 'validation_status']); $this->forge->createTable('rating_import_rows', true);
        }
    }

    public function down() { $this->forge->dropTable('rating_import_rows', true); $this->forge->dropTable('rating_import_jobs', true); }
}
