<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlayerMembershipTables extends Migration
{
    public function up()
    {
        // ========== PLAYERS ==========
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'player_code'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'full_name'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'phone'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'gender'         => ['type' => 'ENUM', 'constraint' => ['male', 'female', 'other'], 'default' => 'other'],
            'birthday'       => ['type' => 'DATE', 'null' => true],
            'avatar'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'level'          => ['type' => 'ENUM', 'constraint' => ['beginner', 'intermediate', 'advanced', 'pro'], 'default' => 'beginner'],
            'rating_score'   => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0.00],
            'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'banned'], 'default' => 'active'],
            'created_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('player_code');
        $this->forge->addUniqueKey(['tenant_id', 'phone']);
        $this->forge->addUniqueKey(['tenant_id', 'email']);
        $this->forge->addKey(['tenant_id', 'level']);
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('players', true);

        // ========== MEMBERSHIP_PACKAGES ==========
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name_vi'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'duration_days'    => ['type' => 'INT', 'constraint' => 11],
            'price'            => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'discount_percent' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'booking_priority' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'           => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_by'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('membership_packages', true);

        // ========== MEMBERSHIPS ==========
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'package_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'start_date'     => ['type' => 'DATE'],
            'end_date'       => ['type' => 'DATE'],
            'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'expired', 'cancelled'], 'default' => 'active'],
            'created_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'updated_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['player_id', 'status']);
        $this->forge->addKey(['tenant_id', 'status', 'end_date']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('package_id', 'membership_packages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('memberships', true);

        // ========== PLAYER_WALLETS ==========
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'balance'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['player_id', 'tenant_id'], false, true, 'unique_player_wallet');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_wallets', true);

        // ========== WALLET_TRANSACTIONS ==========
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'wallet_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'           => ['type' => 'ENUM', 'constraint' => ['topup', 'payment', 'refund', 'adjust'], 'default' => 'topup'],
            'amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'balance_before' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'balance_after'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'ref_type'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ref_id'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'note'           => ['type' => 'TEXT', 'null' => true],
            'created_by'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['wallet_id', 'created_at']);
        $this->forge->addKey(['player_id', 'type']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('wallet_id', 'player_wallets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('wallet_transactions', true);

        // ========== PLAYER_STATISTICS ==========
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'total_matches'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'total_wins'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'total_losses'    => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'win_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'current_streak'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'best_streak'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['player_id', 'tenant_id'], false, true, 'unique_player_stats');
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_statistics', true);
    }

    public function down()
    {
        $this->forge->dropTable('player_statistics', true);
        $this->forge->dropTable('wallet_transactions', true);
        $this->forge->dropTable('player_wallets', true);
        $this->forge->dropTable('memberships', true);
        $this->forge->dropTable('membership_packages', true);
        $this->forge->dropTable('players', true);
    }
}
