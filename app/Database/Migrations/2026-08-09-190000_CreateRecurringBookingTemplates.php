<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecurringBookingTemplates extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'start_date'            => ['type' => 'DATE'],
            'end_date'              => ['type' => 'DATE', 'null' => true],
            'start_time'            => ['type' => 'TIME'],
            'end_time'              => ['type' => 'TIME'],
            'duration_minutes'      => ['type' => 'INT', 'constraint' => 11],
            'repeat_type'           => ['type' => 'ENUM', 'constraint' => ['daily', 'weekly', 'biweekly', 'monthly', 'custom']],
            'repeat_interval'       => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'repeat_days'           => ['type' => 'JSON', 'null' => true],
            'exclude_dates'         => ['type' => 'JSON', 'null' => true],
            'status'                => ['type' => 'ENUM', 'constraint' => ['active', 'paused', 'completed', 'cancelled'], 'default' => 'active'],
            'total_occurrences'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'completed_occurrences' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'next_occurrence'       => ['type' => 'DATE', 'null' => true],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'            => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status', 'next_occurrence']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_recurring_templates', true);
    }

    public function down()
    {
        $this->forge->dropTable('booking_recurring_templates', true);
    }
}
