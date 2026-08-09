<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWalkInSessions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'booking_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'customer_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'customer_phone' => ['type' => 'VARCHAR', 'constraint' => 20],
            'customer_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'session_key' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'checked_in', 'completed', 'cancelled'], 'default' => 'open'],
            'note' => ['type' => 'TEXT', 'null' => true],
            'checked_in_at' => ['type' => 'DATETIME', 'null' => true],
            'checked_out_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('booking_id');
        $this->forge->addUniqueKey(['tenant_id', 'session_key']);
        $this->forge->addKey(['tenant_id', 'status', 'created_at']);
        $this->forge->addKey(['tenant_id', 'branch_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('walk_in_sessions');
    }

    public function down()
    {
        $this->forge->dropTable('walk_in_sessions', true);
    }
}
