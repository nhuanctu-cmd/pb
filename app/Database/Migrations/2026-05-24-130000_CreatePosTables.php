<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePosTables extends Migration
{
    public function up()
    {
        // 1. product_categories
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->createTable('product_categories', false, ['ENGINE' => 'InnoDB']);

        // 2. products
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'category_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'sku' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'unit' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'pcs'],
            'cost_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'sale_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'image' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'category_id', 'status']);
        $this->forge->addForeignKey('category_id', 'product_categories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('products', false, ['ENGINE' => 'InnoDB']);

        // 3. inventories
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'quantity' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tenant_id', 'branch_id', 'product_id']);
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('inventories', false, ['ENGINE' => 'InnoDB']);

        // 4. inventory_movements
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'movement_type' => ['type' => 'ENUM', 'constraint' => ['import', 'sale', 'return', 'adjust'], 'null' => false],
            'quantity' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'before_qty' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'after_qty' => ['type' => 'INT', 'constraint' => 11, 'null' => false, 'default' => 0],
            'ref_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ref_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'note' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['tenant_id', 'branch_id', 'movement_type', 'created_at']);
        $this->forge->createTable('inventory_movements', false, ['ENGINE' => 'InnoDB']);

        // 5. pos_orders
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'branch_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'booking_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'order_code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'total_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'paid_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0],
            'payment_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'paid', 'refunded'], 'default' => 'pending'],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'completed', 'cancelled'], 'default' => 'pending'],
            'note' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tenant_id', 'branch_id', 'order_code']);
        $this->forge->createTable('pos_orders', false, ['ENGINE' => 'InnoDB']);

        // 6. pos_order_items
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'order_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'quantity' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'total' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('order_id', 'pos_orders', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pos_order_items', false, ['ENGINE' => 'InnoDB']);
    }

    public function down()
    {
        $this->forge->dropTable('pos_order_items', true);
        $this->forge->dropTable('pos_orders', true);
        $this->forge->dropTable('inventory_movements', true);
        $this->forge->dropTable('inventories', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('product_categories', true);
    }
}
