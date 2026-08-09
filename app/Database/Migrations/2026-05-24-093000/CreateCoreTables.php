<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoreTables extends Migration
{
    public function up()
    {
        // ========== TENANTS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'            => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'address'         => ['type' => 'TEXT', 'null' => true],
            'logo'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'domain'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'db_name'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'suspended'], 'default' => 'active'],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tenants', true);

        // ========== BRANCHES ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'code'            => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 255],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'address'         => ['type' => 'TEXT', 'null' => true],
            'city'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'district'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'latitude'        => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'longitude'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'is_main'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'maintenance', 'closed'], 'default' => 'active'],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('branches', true);

        // ========== ROLES ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'is_system'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'slug']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('roles', true);

        // ========== PERMISSIONS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'parent_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'module'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('parent_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('permissions', true);

        // ========== ROLE_PERMISSIONS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'role_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'permission_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['role_id', 'permission_id']);
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('role_permissions', true);

        // ========== USERS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'username'        => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true],
            'password'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'first_name'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'last_name'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'phone'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'avatar'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'gender'          => ['type' => 'ENUM', 'constraint' => ['male', 'female', 'other'], 'null' => true],
            'birth_date'      => ['type' => 'DATE', 'null' => true],
            'last_login'      => ['type' => 'DATETIME', 'null' => true],
            'last_ip'         => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'is_superadmin'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'suspended', 'banned'], 'default' => 'active'],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('users', true);

        // ========== USER_ROLES ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'role_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_roles', true);

        // ========== SETTINGS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'key'             => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'           => ['type' => 'TEXT', 'null' => true],
            'type'            => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'text'],
            'group'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => 'general'],
            'is_json'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_public'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'key']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('settings', true);

        // ========== AUDIT_LOGS ==========
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'action'          => ['type' => 'VARCHAR', 'constraint' => 50],
            'module'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'table_name'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'record_id'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'old_values'      => ['type' => 'JSON', 'null' => true],
            'new_values'      => ['type' => 'JSON', 'null' => true],
            'ip_address'      => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('tenant_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('audit_logs', true);

        // ========== MEDIA_FILES ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'branch_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'file_name'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_path'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_type'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'file_size'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'mime_type'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'extension'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'alt_text'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'width'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'height'          => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'          => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'deleted'], 'default' => 'active'],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('branch_id', 'branches', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('media_files', true);
    }

    public function down()
    {
        $this->forge->dropTable('media_files', true);
        $this->forge->dropTable('audit_logs', true);
        $this->forge->dropTable('settings', true);
        $this->forge->dropTable('user_roles', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('roles', true);
        $this->forge->dropTable('branches', true);
        $this->forge->dropTable('tenants', true);
    }
}
