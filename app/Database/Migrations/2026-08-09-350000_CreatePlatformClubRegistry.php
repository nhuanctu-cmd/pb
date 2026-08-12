<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlatformClubRegistry extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('platform_clubs')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'public_id' => ['type' => 'CHAR', 'constraint' => 36, 'null' => false],
                'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'slug' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'province' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'city' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'logo_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'website_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'suspended', 'archived'], 'default' => 'draft'],
                'verification_status' => ['type' => 'ENUM', 'constraint' => ['unverified', 'claimed', 'verified', 'official'], 'default' => 'unverified'],
                'metadata' => ['type' => 'JSON', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
                'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('public_id');
            $this->forge->addUniqueKey('slug');
            $this->forge->addKey(['province', 'city', 'status']);
            $this->forge->createTable('platform_clubs', true);
        }

        if (! $this->db->tableExists('platform_club_aliases')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'platform_club_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'club_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'verified', 'rejected'], 'default' => 'pending'],
                'linked_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'verified_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tenant_id', 'club_id']);
            $this->forge->addKey(['platform_club_id', 'status']);
            $this->forge->createTable('platform_club_aliases', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('platform_club_aliases', true);
        $this->forge->dropTable('platform_clubs', true);
    }
}
