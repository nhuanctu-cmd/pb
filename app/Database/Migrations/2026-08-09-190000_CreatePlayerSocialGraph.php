<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlayerSocialGraph extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'follower_player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'following_player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'blocked'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'follower_player_id', 'following_player_id']);
        $this->forge->addKey(['tenant_id', 'follower_player_id', 'status']);
        $this->forge->addKey(['tenant_id', 'following_player_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('follower_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('following_player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_follows');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'entity_type' => ['type' => 'ENUM', 'constraint' => ['club', 'court', 'open_play']],
            'entity_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'player_id', 'entity_type', 'entity_id']);
        $this->forge->addKey(['tenant_id', 'player_id', 'entity_type']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('player_favorites');
    }

    public function down()
    {
        $this->forge->dropTable('player_favorites', true);
        $this->forge->dropTable('player_follows', true);
    }
}
