<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSocialTeamMatchingModule extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255],
            'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'logo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'description_vi' => ['type' => 'TEXT', 'null' => true],
            'description_en' => ['type' => 'TEXT', 'null' => true],
            'owner_player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'pending'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'status']);
        $this->forge->createTable('clubs', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'club_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'team_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'captain_player_id' => ['type' => 'INT', 'unsigned' => true],
            'team_type' => ['type' => 'ENUM', 'constraint' => ['male_double', 'female_double', 'mixed_double', 'group'], 'default' => 'group'],
            'rating_avg' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'disbanded'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'club_id']);
        $this->forge->addKey(['tenant_id', 'captain_player_id']);
        $this->forge->createTable('teams', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'team_id' => ['type' => 'INT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'role' => ['type' => 'ENUM', 'constraint' => ['captain', 'member'], 'default' => 'member'],
            'status' => ['type' => 'ENUM', 'constraint' => ['invited', 'accepted', 'rejected', 'removed'], 'default' => 'invited'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'team_id']);
        $this->forge->addUniqueKey(['team_id', 'player_id']);
        $this->forge->createTable('team_members', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'unsigned' => true],
            'preferred_date' => ['type' => 'DATE'],
            'preferred_start_time' => ['type' => 'TIME'],
            'preferred_end_time' => ['type' => 'TIME'],
            'level_from' => ['type' => 'INT', 'default' => 0],
            'level_to' => ['type' => 'INT', 'default' => 9999],
            'match_type' => ['type' => 'ENUM', 'constraint' => ['single', 'double', 'mixed'], 'default' => 'double'],
            'need_players' => ['type' => 'INT', 'default' => 1],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'matched', 'cancelled', 'expired'], 'default' => 'open'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'branch_id', 'preferred_date', 'status']);
        $this->forge->addKey(['tenant_id', 'player_id']);
        $this->forge->createTable('match_requests', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'branch_id' => ['type' => 'INT', 'unsigned' => true],
            'booking_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'match_request_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'match_date' => ['type' => 'DATE'],
            'start_time' => ['type' => 'TIME'],
            'end_time' => ['type' => 'TIME'],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'confirmed', 'booked', 'cancelled', 'completed'], 'default' => 'pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'branch_id', 'match_date', 'status']);
        $this->forge->createTable('social_matches', true);

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'social_match_id' => ['type' => 'INT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'team_side' => ['type' => 'ENUM', 'constraint' => ['A', 'B'], 'default' => 'A'],
            'status' => ['type' => 'ENUM', 'constraint' => ['invited', 'confirmed', 'declined', 'removed'], 'default' => 'confirmed'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'social_match_id']);
        $this->forge->addUniqueKey(['social_match_id', 'player_id']);
        $this->forge->createTable('social_match_players', true);
    }

    public function down()
    {
        $this->forge->dropTable('social_match_players', true);
        $this->forge->dropTable('social_matches', true);
        $this->forge->dropTable('match_requests', true);
        $this->forge->dropTable('team_members', true);
        $this->forge->dropTable('teams', true);
        $this->forge->dropTable('clubs', true);
    }
}
