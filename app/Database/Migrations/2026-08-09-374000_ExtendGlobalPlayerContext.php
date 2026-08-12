<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExtendGlobalPlayerContext extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('country_code', 'players')) $this->forge->addColumn('players', ['country_code' => ['type' => 'CHAR', 'constraint' => 2, 'null' => true, 'after' => 'region']]);
        if (! $this->db->fieldExists('country_code', 'player_competitive_profiles')) $this->forge->addColumn('player_competitive_profiles', ['country_code' => ['type' => 'CHAR', 'constraint' => 2, 'default' => 'VN', 'after' => 'display_name']]);
        if (! $this->db->fieldExists('administrative_area_code', 'player_competitive_profiles')) $this->forge->addColumn('player_competitive_profiles', ['administrative_area_code' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'country_code']]);
        if ($this->db->fieldExists('country_code', 'players')) $this->db->query("UPDATE players p LEFT JOIN tenants t ON t.id = p.tenant_id SET p.country_code = COALESCE(t.country_code, 'VN') WHERE p.country_code IS NULL OR p.country_code = ''");
        if ($this->db->fieldExists('country_code', 'player_competitive_profiles')) $this->db->table('player_competitive_profiles')->where('country_code', null)->update(['country_code' => 'VN']);
    }

    public function down()
    {
        if ($this->db->fieldExists('administrative_area_code', 'player_competitive_profiles')) $this->forge->dropColumn('player_competitive_profiles', 'administrative_area_code');
        if ($this->db->fieldExists('country_code', 'player_competitive_profiles')) $this->forge->dropColumn('player_competitive_profiles', 'country_code');
        if ($this->db->fieldExists('country_code', 'players')) $this->forge->dropColumn('players', 'country_code');
    }
}
