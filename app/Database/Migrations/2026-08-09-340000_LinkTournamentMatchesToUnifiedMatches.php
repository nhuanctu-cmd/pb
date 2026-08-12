<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class LinkTournamentMatchesToUnifiedMatches extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('tournament_matches') && ! $this->db->fieldExists('unified_match_id', 'tournament_matches')) {
            $this->forge->addColumn('tournament_matches', [
                'unified_match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'winner_team_id'],
            ]);
            $this->forge->addKey('unified_match_id', false, false, 'idx_tournament_matches_unified');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('tournament_matches') && $this->db->fieldExists('unified_match_id', 'tournament_matches')) {
            $this->forge->dropColumn('tournament_matches', 'unified_match_id');
        }
    }
}
