<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingWaitlist extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'booking_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'customer_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'customer_phone' => ['type' => 'VARCHAR', 'constraint' => 50],
            'customer_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'booking_date' => ['type' => 'DATE'],
            'start_time' => ['type' => 'TIME'],
            'end_time' => ['type' => 'TIME'],
            'duration_minutes' => ['type' => 'INT', 'constraint' => 11],
            'priority' => ['type' => 'INT', 'constraint' => 11, 'default' => 100],
            'status' => ['type' => 'ENUM', 'constraint' => ['waiting', 'notified', 'claimed', 'expired', 'cancelled'], 'default' => 'waiting'],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'notified_at' => ['type' => 'DATETIME', 'null' => true],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'claimed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status', 'booking_date', 'start_time']);
        $this->forge->addKey(['tenant_id', 'idempotency_key'], false, true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('booking_waitlist', true);
    }

    public function down()
    {
        $this->forge->dropTable('booking_waitlist', true);
    }
}
