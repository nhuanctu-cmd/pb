<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePaymentTables extends Migration
{
    public function up()
    {
        // 1. invoices
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'invoice_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'customer_type' => ['type' => 'ENUM', 'constraint' => ['player', 'guest'], 'default' => 'guest'],
            'player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ref_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ref_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'subtotal' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'paid_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['unpaid', 'partial', 'paid', 'cancelled', 'refunded'], 'default' => 'unpaid'],
            'note' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tenant_id', 'invoice_code']);
        $this->forge->createTable('invoices', false, ['ENGINE' => 'InnoDB']);

        // 2. payments
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'invoice_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'payment_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'method' => ['type' => 'ENUM', 'constraint' => ['cash', 'bank_qr', 'wallet', 'momo', 'stripe'], 'null' => false],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'transaction_ref' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'success', 'failed', 'cancelled'], 'default' => 'pending'],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'paid_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tenant_id', 'payment_code']);
        $this->forge->addKey(['invoice_id', 'status']);
        $this->forge->addKey('idempotency_key', false, true);
        $this->forge->createTable('payments', false, ['ENGINE' => 'InnoDB']);

        // 3. refunds
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'payment_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'invoice_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'reason' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected', 'completed'], 'default' => 'pending'],
            'processed_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'payment_id']);
        $this->forge->createTable('refunds', false, ['ENGINE' => 'InnoDB']);

        // 4. payment_qr_configs
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'bank_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'bank_account' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'account_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'qr_template' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->createTable('payment_qr_configs', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('payment_qr_configs', true);
        $this->forge->dropTable('refunds', true);
        $this->forge->dropTable('payments', true);
        $this->forge->dropTable('invoices', true);
    }
}
