<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDynamicPricingModule extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('pricing_rules')) {
            $this->forge->addField([
                'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'branch_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'court_type_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'court_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'code'                => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'name_vi'             => ['type' => 'VARCHAR', 'constraint' => 255],
                'name_en'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'description'         => ['type' => 'TEXT', 'null' => true],
                'priority'            => ['type' => 'INT', 'constraint' => 11, 'default' => 10],
                'price_type'          => ['type' => 'ENUM', 'constraint' => ['fixed', 'hourly'], 'default' => 'hourly'],
                'price_amount'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'member_price_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
                'start_date'          => ['type' => 'DATE', 'null' => true],
                'end_date'            => ['type' => 'DATE', 'null' => true],
                'start_time'          => ['type' => 'TIME', 'null' => true],
                'end_time'            => ['type' => 'TIME', 'null' => true],
                'day_of_week'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'is_holiday'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'status'              => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
                'created_by'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'updated_by'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
                'created_at'          => ['type' => 'DATETIME', 'null' => true],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'status', 'priority']);
            $this->forge->createTable('pricing_rules', true);
        }

        if (! $this->db->tableExists('pricing_rule_conditions')) {
            $this->forge->addField([
                'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'pricing_rule_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'condition_type'  => ['type' => 'ENUM', 'constraint' => ['branch', 'court_type', 'court', 'weekday', 'time_range', 'holiday', 'member_level']],
                'operator'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'equals'],
                'value'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'value_to'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at'      => ['type' => 'DATETIME', 'null' => true],
                'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'pricing_rule_id']);
            $this->forge->createTable('pricing_rule_conditions', true);
        }

        if (! $this->db->tableExists('dynamic_price_logs')) {
            $this->forge->addField([
                'id'               => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'booking_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'court_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'branch_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'court_type_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'input_json'       => ['type' => 'JSON', 'null' => true],
                'matched_rule_ids' => ['type' => 'JSON', 'null' => true],
                'final_price'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'applied_rules'    => ['type' => 'JSON', 'null' => true],
                'created_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'court_id']);
            $this->forge->createTable('dynamic_price_logs', true);
        }

        $this->addColumnIfMissing('bookings', 'pricing_rule_id', ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'note']);
        $this->addColumnIfMissing('bookings', 'price_breakdown', ['type' => 'JSON', 'null' => true, 'after' => 'pricing_rule_id']);
        $this->addColumnIfMissing('booking_items', 'base_price', ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'price']);
        $this->addColumnIfMissing('booking_items', 'dynamic_price', ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'base_price']);
        $this->addColumnIfMissing('booking_items', 'pricing_detail', ['type' => 'JSON', 'null' => true, 'after' => 'dynamic_price']);
    }

    public function down()
    {
        $this->forge->dropTable('dynamic_price_logs', true);
        $this->forge->dropTable('pricing_rule_conditions', true);
        $this->forge->dropTable('pricing_rules', true);
    }

    private function addColumnIfMissing(string $table, string $field, array $definition): void
    {
        if ($this->db->tableExists($table) && ! $this->db->fieldExists($field, $table)) {
            $this->forge->addColumn($table, [$field => $definition]);
        }
    }
}
