<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTournamentModule extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'unsigned' => true],
            'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'slug_vi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'slug_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description_vi' => ['type' => 'TEXT', 'null' => true],
            'description_en' => ['type' => 'TEXT', 'null' => true],
            'banner' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'start_date' => ['type' => 'DATE', 'null' => true],
            'end_date' => ['type' => 'DATE', 'null' => true],
            'registration_start' => ['type' => 'DATETIME', 'null' => true],
            'registration_end' => ['type' => 'DATETIME', 'null' => true],
            'max_teams' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'registration_fee' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'open', 'closed', 'running', 'completed', 'cancelled'], 'default' => 'draft'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'branch_id', 'status']);
        $this->forge->addUniqueKey(['tenant_id', 'slug_vi']);
        $this->forge->createTable('tournaments', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'tournament_id' => ['type' => 'INT', 'unsigned' => true],
            'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'category_type' => ['type' => 'ENUM', 'constraint' => ['single_male', 'single_female', 'double_male', 'double_female', 'mixed_double', 'team_battle']],
            'max_teams' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'min_rating' => ['type' => 'INT', 'null' => true],
            'max_rating' => ['type' => 'INT', 'null' => true],
            'registration_fee' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'tournament_id', 'status']);
        $this->forge->createTable('tournament_categories', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'tournament_id' => ['type' => 'INT', 'unsigned' => true],
            'rule_content_vi' => ['type' => 'TEXT', 'null' => true],
            'rule_content_en' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'tournament_id']);
        $this->forge->createTable('tournament_rules', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'tournament_id' => ['type' => 'INT', 'unsigned' => true],
            'sponsor_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'logo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'website' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'tournament_id', 'status']);
        $this->forge->createTable('tournament_sponsors', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'tournament_id' => ['type' => 'INT', 'unsigned' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'team_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'contact_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'contact_phone' => ['type' => 'VARCHAR', 'constraint' => 30],
            'payment_status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'unpaid'],
            'approval_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'note' => ['type' => 'TEXT', 'null' => true],
            'invoice_code' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'invoice_amount' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'tournament_id', 'category_id']);
        $this->forge->createTable('tournament_registrations', true);
    }

    public function down()
    {
        $this->forge->dropTable('tournament_registrations', true);
        $this->forge->dropTable('tournament_sponsors', true);
        $this->forge->dropTable('tournament_rules', true);
        $this->forge->dropTable('tournament_categories', true);
        $this->forge->dropTable('tournaments', true);
    }
}
