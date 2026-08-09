<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCourtTables extends Migration
{
    public function up()
    {
        // ========== COURT_TYPES ==========
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name_vi'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description_vi'    => ['type' => 'TEXT', 'null' => true],
            'description_en'    => ['type' => 'TEXT', 'null' => true],
            'default_capacity'  => ['type' => 'INT', 'constraint' => 11, 'default' => 4],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('court_types', true);

        // ========== COURTS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_type_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'code'            => ['type' => 'VARCHAR', 'constraint' => 50],
            'name_vi'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'floor'           => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'area'            => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'is_indoor'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'has_light'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'has_fan'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'has_camera'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'          => ['type' => 'ENUM', 'constraint' => ['available', 'occupied', 'maintenance', 'inactive'], 'default' => 'available'],
            'sort_order'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'branch_id', 'code']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_type_id', 'court_types', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('courts', true);

        // ========== COURT_IMAGES ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'file_path'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'is_primary'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('court_images', true);

        // ========== BRANCH_OPENING_HOURS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'day_of_week'     => ['type' => 'TINYINT', 'constraint' => 1],
            'open_time'       => ['type' => 'TIME', 'null' => true],
            'close_time'      => ['type' => 'TIME', 'null' => true],
            'is_closed'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'branch_id', 'day_of_week']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('branch_opening_hours', true);

        // ========== BRANCH_HOLIDAYS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'holiday_date'    => ['type' => 'DATE'],
            'name_vi'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_closed'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'note'            => ['type' => 'TEXT', 'null' => true],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'branch_id', 'holiday_date']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('branch_holidays', true);

        // ========== COURT_MAINTENANCE ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'court_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'start_time'      => ['type' => 'DATETIME'],
            'end_time'        => ['type' => 'DATETIME', 'null' => true],
            'reason'          => ['type' => 'TEXT', 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['scheduled', 'doing', 'completed', 'cancelled'], 'default' => 'scheduled'],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('court_id', 'courts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('court_maintenance', true);
    }

    public function down()
    {
        $this->forge->dropTable('court_maintenance', true);
        $this->forge->dropTable('branch_holidays', true);
        $this->forge->dropTable('branch_opening_hours', true);
        $this->forge->dropTable('court_images', true);
        $this->forge->dropTable('courts', true);
        $this->forge->dropTable('court_types', true);
    }
}
