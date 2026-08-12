<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** P8 — registry integration and scoped partner credentials. */
class CreatePartnerIntegrations extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('platform_integrations')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 80], 'name' => ['type' => 'VARCHAR', 'constraint' => 180],
                'provider_type' => ['type' => 'ENUM', 'constraint' => ['rating', 'payment', 'livestream', 'crm', 'sms', 'other'], 'default' => 'other'],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'paused', 'disabled'], 'default' => 'active'],
                'config' => ['type' => 'JSON', 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey('code'); $this->forge->createTable('platform_integrations', true);
        }
        if (! $this->db->tableExists('tenant_integrations')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'tenant_id' => ['type' => 'INT', 'unsigned' => true], 'integration_id' => ['type' => 'INT', 'unsigned' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'paused', 'disabled'], 'default' => 'active'], 'credentials_ciphertext' => ['type' => 'TEXT', 'null' => true],
                'last_sync_at' => ['type' => 'DATETIME', 'null' => true], 'last_error' => ['type' => 'TEXT', 'null' => true], 'metadata' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey(['tenant_id', 'integration_id']); $this->forge->addKey(['tenant_id', 'status']);
            $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE'); $this->forge->addForeignKey('integration_id', 'platform_integrations', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('tenant_integrations', true);
        }
        if (! $this->db->tableExists('partner_api_keys')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'tenant_id' => ['type' => 'INT', 'unsigned' => true], 'name' => ['type' => 'VARCHAR', 'constraint' => 180],
                'key_prefix' => ['type' => 'VARCHAR', 'constraint' => 32], 'key_hash' => ['type' => 'CHAR', 'constraint' => 64], 'scopes' => ['type' => 'JSON'],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'revoked'], 'default' => 'active'], 'expires_at' => ['type' => 'DATETIME', 'null' => true], 'last_used_at' => ['type' => 'DATETIME', 'null' => true], 'revoked_at' => ['type' => 'DATETIME', 'null' => true],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey('key_hash'); $this->forge->addKey(['tenant_id', 'status']);
            $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE'); $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('partner_api_keys', true);
        }
        $now = date('Y-m-d H:i:s');
        foreach ([['internal-webhooks', 'Webhook sự kiện Pickleball', 'other'], ['external-rating', 'Nhà cung cấp rating bên ngoài', 'rating'], ['partner-api', 'Partner API', 'other']] as [$code, $name, $type]) {
            if (! $this->db->table('platform_integrations')->where('code', $code)->countAllResults()) $this->db->table('platform_integrations')->insert(['code' => $code, 'name' => $name, 'provider_type' => $type, 'status' => 'active', 'config' => json_encode(['managed_by' => 'platform']), 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('partner_api_keys', true); $this->forge->dropTable('tenant_integrations', true); $this->forge->dropTable('platform_integrations', true);
    }
}
