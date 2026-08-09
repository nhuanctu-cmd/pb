<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGrowthModule extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'discount_type' => ['type' => 'ENUM', 'constraint' => ['percent', 'fixed'], 'default' => 'percent'],
            'discount_value' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'max_discount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'min_order_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'usage_limit' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'per_customer_limit' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'starts_at' => ['type' => 'DATETIME', 'null' => true],
            'ends_at' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'paused', 'expired'], 'default' => 'draft'],
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addKey(['tenant_id', 'status', 'starts_at', 'ends_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('promotions');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'promotion_id' => ['type' => 'INT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'booking_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'order_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'discount_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'idempotency_key' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'promotion_id', 'idempotency_key']);
        $this->forge->addKey(['tenant_id', 'promotion_id', 'player_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('promotion_id', 'promotions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('promotion_redemptions');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'reward_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'uses_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'max_uses' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'paused'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->addUniqueKey(['tenant_id', 'player_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('referral_codes');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'referrer_player_id' => ['type' => 'INT', 'unsigned' => true],
            'referred_player_id' => ['type' => 'INT', 'unsigned' => true],
            'code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'reward_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'qualified', 'rewarded', 'cancelled'], 'default' => 'pending'],
            'qualified_at' => ['type' => 'DATETIME', 'null' => true],
            'rewarded_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'referred_player_id']);
        $this->forge->addKey(['tenant_id', 'referrer_player_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('referrer_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('referred_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('referrals');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'entity_type' => ['type' => 'ENUM', 'constraint' => ['booking', 'court', 'coach', 'coaching_session', 'competition'], 'default' => 'booking'],
            'entity_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'rating' => ['type' => 'TINYINT', 'unsigned' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'body' => ['type' => 'TEXT', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'published', 'hidden'], 'default' => 'pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'player_id', 'entity_type', 'entity_id']);
        $this->forge->addKey(['tenant_id', 'entity_type', 'entity_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('reviews');
    }

    public function down()
    {
        $this->forge->dropTable('reviews', true);
        $this->forge->dropTable('referrals', true);
        $this->forge->dropTable('referral_codes', true);
        $this->forge->dropTable('promotion_redemptions', true);
        $this->forge->dropTable('promotions', true);
    }
}
