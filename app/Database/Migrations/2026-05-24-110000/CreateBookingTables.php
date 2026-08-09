<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingTables extends Migration
{
    public function up()
    {
        // ========== BOOKINGS ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'customer_name'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'customer_phone'    => ['type' => 'VARCHAR', 'constraint' => 20],
            'customer_email'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'booking_code'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'booking_date'      => ['type' => 'DATE'],
            'start_time'        => ['type' => 'TIME'],
            'end_time'          => ['type' => 'TIME'],
            'duration_minutes'  => ['type' => 'INT', 'constraint' => 11],
            'total_amount'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'deposit_amount'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'paid_amount'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'status'            => ['type' => 'ENUM', 'constraint' => ['pending', 'reserved', 'paid', 'checked_in', 'completed', 'cancelled', 'refunded', 'no_show'], 'default' => 'pending'],
            'payment_status'    => ['type' => 'ENUM', 'constraint' => ['unpaid', 'partial', 'paid', 'refunded'], 'default' => 'unpaid'],
            'source'            => ['type' => 'ENUM', 'constraint' => ['admin', 'player_portal', 'public_web', 'zalo', 'phone'], 'default' => 'admin'],
            'note'              => ['type' => 'TEXT', 'null' => true],
            'cancelled_at'      => ['type' => 'DATETIME', 'null' => true],
            'cancelled_reason'  => ['type' => 'TEXT', 'null' => true],
            'checked_in_at'     => ['type' => 'DATETIME', 'null' => true],
            'completed_at'      => ['type' => 'DATETIME', 'null' => true],
            'expires_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('booking_code');
        $this->forge->addKey(['tenant_id', 'branch_id', 'booking_date']);
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('bookings', true);

        // ========== BOOKING_ITEMS ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'booking_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'start_time'        => ['type' => 'TIME'],
            'end_time'          => ['type' => 'TIME'],
            'price'             => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'cancelled', 'refunded'], 'default' => 'active'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['booking_id', 'court_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_items', true);

        // ========== BOOKING_QR_CODES ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'booking_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'qr_token'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'qr_path'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'expired_at'        => ['type' => 'DATETIME', 'null' => true],
            'used_at'           => ['type' => 'DATETIME', 'null' => true],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'used', 'expired', 'revoked'], 'default' => 'active'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('qr_token');
        $this->forge->addKey(['booking_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_qr_codes', true);

        // ========== BOOKING_LOGS ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'booking_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'action'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'old_status'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'new_status'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'message'           => ['type' => 'TEXT', 'null' => true],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['booking_id', 'created_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('booking_id', 'bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_logs', true);

        // ========== PRICE_TIERS (for pricing config) ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'court_type_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name_vi'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'day_of_week'       => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true],
            'start_time'        => ['type' => 'TIME', 'null' => true],
            'end_time'          => ['type' => 'TIME', 'null' => true],
            'price_per_hour'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'price_per_slot'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'min_deposit_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'is_active'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_type_id', 'court_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('price_tiers', true);

        // ========== BOOKING_SETTINGS ==========
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'allow_online_booking'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'require_deposit'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deposit_percent'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'min_advance_minutes'   => ['type' => 'INT', 'constraint' => 11, 'default' => 60],
            'max_advance_days'      => ['type' => 'INT', 'constraint' => 11, 'default' => 14],
            'slot_duration_minutes'  => ['type' => 'INT', 'constraint' => 11, 'default' => 60],
            'max_slots_per_booking' => ['type' => 'INT', 'constraint' => 11, 'default' => 4],
            'booking_expiry_minutes' => ['type' => 'INT', 'constraint' => 11, 'default' => 15],
            'cancel_before_minutes'  => ['type' => 'INT', 'constraint' => 11, 'default' => 120],
            'created_by'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'            => ['type' => 'DATETIME', 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('booking_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('booking_settings', true);
        $this->forge->dropTable('price_tiers', true);
        $this->forge->dropTable('booking_logs', true);
        $this->forge->dropTable('booking_qr_codes', true);
        $this->forge->dropTable('booking_items', true);
        $this->forge->dropTable('bookings', true);
    }
}
