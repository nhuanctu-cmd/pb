<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Central data-access contract for tenant, platform-public and restricted data. */
class CreateTenantDataPolicies extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tenant_data_policies')) return;

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'resource_type' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => false],
            'access_scope' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'effect' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'deny'],
            'visibility' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'private'],
            'requires_consent' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'version' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'v1'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'configuration' => ['type' => 'JSON', 'null' => true],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'resource_type', 'access_scope', 'status']);
        $this->forge->addKey(['access_scope', 'effect', 'status']);
        $this->forge->createTable('tenant_data_policies', true);
    }

    public function down()
    {
        $this->forge->dropTable('tenant_data_policies', true);
    }
}
