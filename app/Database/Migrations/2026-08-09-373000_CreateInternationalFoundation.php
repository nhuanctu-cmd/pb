<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** P1/P10 foundation: country context, organization membership and rulesets. */
class CreateInternationalFoundation extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('platform_countries')) {
            $this->forge->addField([
                'id' => ['type' => 'SMALLINT', 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'CHAR', 'constraint' => 2], 'name_en' => ['type' => 'VARCHAR', 'constraint' => 120], 'name_vi' => ['type' => 'VARCHAR', 'constraint' => 120],
                'default_currency' => ['type' => 'CHAR', 'constraint' => 3], 'default_timezone' => ['type' => 'VARCHAR', 'constraint' => 64],
                'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey('code'); $this->forge->createTable('platform_countries', true);
        }
        if (! $this->db->tableExists('platform_regions')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true], 'country_id' => ['type' => 'SMALLINT', 'unsigned' => true], 'parent_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 40], 'name_en' => ['type' => 'VARCHAR', 'constraint' => 160], 'name_local' => ['type' => 'VARCHAR', 'constraint' => 160], 'region_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'administrative_area'],
                'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey(['country_id', 'code']); $this->forge->addKey(['country_id', 'region_type']); $this->forge->createTable('platform_regions', true);
        }
        if (! $this->db->tableExists('organization_memberships')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'tenant_id' => ['type' => 'INT', 'unsigned' => true], 'user_id' => ['type' => 'INT', 'unsigned' => true], 'branch_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'role_code' => ['type' => 'VARCHAR', 'constraint' => 60], 'status' => ['type' => 'ENUM', 'constraint' => ['invited', 'active', 'suspended', 'left'], 'default' => 'active'], 'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'starts_at' => ['type' => 'DATETIME', 'null' => true], 'ends_at' => ['type' => 'DATETIME', 'null' => true], 'metadata' => ['type' => 'JSON', 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey(['tenant_id', 'user_id', 'role_code']); $this->forge->addKey(['tenant_id', 'status']);
            $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE'); $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE'); $this->forge->createTable('organization_memberships', true);
        }
        if (! $this->db->tableExists('competition_rulesets')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true], 'code' => ['type' => 'VARCHAR', 'constraint' => 80], 'name_en' => ['type' => 'VARCHAR', 'constraint' => 180], 'name_vi' => ['type' => 'VARCHAR', 'constraint' => 180],
                'discipline' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'pickleball'], 'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'archived'], 'default' => 'active'], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey('code'); $this->forge->createTable('competition_rulesets', true);
        }
        if (! $this->db->tableExists('competition_ruleset_versions')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'ruleset_id' => ['type' => 'INT', 'unsigned' => true], 'version' => ['type' => 'VARCHAR', 'constraint' => 30], 'configuration' => ['type' => 'JSON'],
                'effective_from' => ['type' => 'DATETIME', 'null' => true], 'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'locked', 'archived'], 'default' => 'draft'], 'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true], 'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addUniqueKey(['ruleset_id', 'version']); $this->forge->addKey(['ruleset_id', 'status']);
            $this->forge->addForeignKey('ruleset_id', 'competition_rulesets', 'id', 'CASCADE', 'CASCADE'); $this->forge->createTable('competition_ruleset_versions', true);
        }
        if (! $this->db->tableExists('data_provenance')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true], 'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true], 'entity_type' => ['type' => 'VARCHAR', 'constraint' => 80], 'entity_id' => ['type' => 'BIGINT', 'unsigned' => true],
                'source_type' => ['type' => 'VARCHAR', 'constraint' => 60], 'source_id' => ['type' => 'VARCHAR', 'constraint' => 160, 'null' => true], 'verification_status' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'unverified'], 'actor_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true], 'evidence' => ['type' => 'JSON', 'null' => true], 'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true); $this->forge->addKey(['entity_type', 'entity_id']); $this->forge->addKey(['tenant_id', 'source_type']); $this->forge->createTable('data_provenance', true);
        }
        $this->addColumnIfMissing('tenants', 'country_code', ['type' => 'CHAR', 'constraint' => 2, 'default' => 'VN', 'after' => 'code']);
        $this->addColumnIfMissing('tenants', 'default_timezone', ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Asia/Ho_Chi_Minh', 'after' => 'country_code']);
        $this->addColumnIfMissing('tenants', 'default_currency', ['type' => 'CHAR', 'constraint' => 3, 'default' => 'VND', 'after' => 'default_timezone']);
        $this->addColumnIfMissing('tenants', 'default_locale', ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'vi-VN', 'after' => 'default_currency']);
        $this->addColumnIfMissing('branches', 'country_code', ['type' => 'CHAR', 'constraint' => 2, 'default' => 'VN', 'after' => 'city']);
        $this->addColumnIfMissing('branches', 'timezone', ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Asia/Ho_Chi_Minh', 'after' => 'country_code']);
        $this->addColumnIfMissing('branches', 'currency', ['type' => 'CHAR', 'constraint' => 3, 'default' => 'VND', 'after' => 'timezone']);
        foreach (['ruleset_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'branch_id'], 'ruleset_version_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'ruleset_id'], 'tier_code' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'ruleset_version_id'], 'country_code' => ['type' => 'CHAR', 'constraint' => 2, 'null' => true, 'after' => 'tier_code']] as $field => $definition) $this->addColumnIfMissing('tournaments', $field, $definition);
        $now = date('Y-m-d H:i:s');
        foreach ([['VN', 'Vietnam', 'Việt Nam', 'VND', 'Asia/Ho_Chi_Minh'], ['US', 'United States', 'Hoa Kỳ', 'USD', 'America/New_York'], ['TH', 'Thailand', 'Thái Lan', 'THB', 'Asia/Bangkok'], ['SG', 'Singapore', 'Singapore', 'SGD', 'Asia/Singapore']] as [$code, $en, $vi, $currency, $timezone]) {
            if (! $this->db->table('platform_countries')->where('code', $code)->countAllResults()) $this->db->table('platform_countries')->insert(['code' => $code, 'name_en' => $en, 'name_vi' => $vi, 'default_currency' => $currency, 'default_timezone' => $timezone, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }
        $ruleset = $this->db->table('competition_rulesets')->where('code', 'pickleball-standard')->get()->getRow();
        if (! $ruleset) { $this->db->table('competition_rulesets')->insert(['code' => 'pickleball-standard', 'name_en' => 'Pickleball Standard Rules', 'name_vi' => 'Luật Pickleball tiêu chuẩn', 'discipline' => 'pickleball', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]); $ruleset = $this->db->table('competition_rulesets')->where('code', 'pickleball-standard')->get()->getRow(); }
        if ($ruleset && ! $this->db->table('competition_ruleset_versions')->where('ruleset_id', $ruleset->id)->where('version', '1.0')->countAllResults()) $this->db->table('competition_ruleset_versions')->insert(['ruleset_id' => $ruleset->id, 'version' => '1.0', 'configuration' => json_encode(['best_of' => 1, 'game_to' => 11, 'win_by' => 2, 'score_cap' => 15, 'timeouts' => 2, 'retirement_eligible' => true, 'walkover_eligible' => false]), 'effective_from' => $now, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
    }

    private function addColumnIfMissing(string $table, string $column, array $definition): void
    {
        if (! $this->db->fieldExists($column, $table)) $this->forge->addColumn($table, [$column => $definition]);
    }

    public function down()
    {
        foreach (['data_provenance', 'competition_ruleset_versions', 'competition_rulesets', 'organization_memberships', 'platform_regions', 'platform_countries'] as $table) $this->forge->dropTable($table, true);
    }
}
