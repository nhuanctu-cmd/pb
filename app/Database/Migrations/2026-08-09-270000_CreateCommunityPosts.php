<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommunityPosts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'type' => ['type' => 'ENUM', 'constraint' => ['announcement', 'tip', 'event'], 'default' => 'tip'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 180],
            'body' => ['type' => 'TEXT'],
            'status' => ['type' => 'ENUM', 'constraint' => ['published', 'hidden'], 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status', 'created_at']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('community_posts');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'post_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'body' => ['type' => 'TEXT'],
            'status' => ['type' => 'ENUM', 'constraint' => ['published', 'hidden'], 'default' => 'published'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'post_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('post_id', 'community_posts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('community_comments');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'post_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'reaction' => ['type' => 'ENUM', 'constraint' => ['like', 'love', 'wow'], 'default' => 'like'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'post_id', 'player_id']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('post_id', 'community_posts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('community_reactions');
    }

    public function down()
    {
        $this->forge->dropTable('community_reactions', true);
        $this->forge->dropTable('community_comments', true);
        $this->forge->dropTable('community_posts', true);
    }
}
