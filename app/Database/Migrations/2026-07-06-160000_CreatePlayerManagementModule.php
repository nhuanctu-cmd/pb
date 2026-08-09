<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlayerManagementModule extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'   => ['type' => 'INT', 'unsigned' => true],
            'code'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'min_rating'  => ['type' => 'INT', 'default' => 0],
            'max_rating'  => ['type' => 'INT', 'default' => 9999],
            'color'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => '#6c757d'],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['tenant_id', 'code']);
        $this->forge->createTable('player_levels', true);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'   => ['type' => 'INT', 'unsigned' => true],
            'player_id'   => ['type' => 'INT', 'unsigned' => true],
            'badge_code'  => ['type' => 'VARCHAR', 'constraint' => 80],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 120],
            'description' => ['type' => 'TEXT', 'null' => true],
            'rarity'      => ['type' => 'ENUM', 'constraint' => ['common', 'rare', 'epic', 'legendary'], 'default' => 'common'],
            'icon'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'source'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'earned_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'player_id']);
        $this->forge->addUniqueKey(['player_id', 'badge_code']);
        $this->forge->createTable('player_badges', true);

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'     => ['type' => 'INT', 'unsigned' => true],
            'player_id'     => ['type' => 'INT', 'unsigned' => true],
            'scope_type'    => ['type' => 'ENUM', 'constraint' => ['global', 'region', 'facility', 'tournament'], 'default' => 'global'],
            'scope_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'region'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'rating_type'   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'elo'],
            'rating'        => ['type' => 'INT', 'default' => 1000],
            'games_played'  => ['type' => 'INT', 'default' => 0],
            'wins'          => ['type' => 'INT', 'default' => 0],
            'losses'        => ['type' => 'INT', 'default' => 0],
            'rank_position' => ['type' => 'INT', 'null' => true],
            'last_match_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'scope_type', 'scope_id', 'rating']);
        $this->forge->addUniqueKey(['player_id', 'scope_type', 'scope_id', 'region'], 'unique_player_rating_scope');
        $this->forge->createTable('player_ratings', true);

        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'          => ['type' => 'INT', 'unsigned' => true],
            'player_id'          => ['type' => 'INT', 'unsigned' => true],
            'opponent_player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'branch_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'facility_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tournament_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'match_date'         => ['type' => 'DATETIME'],
            'result'             => ['type' => 'ENUM', 'constraint' => ['win', 'loss', 'draw'], 'default' => 'win'],
            'score'              => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'rating_before'      => ['type' => 'INT', 'default' => 1000],
            'rating_after'       => ['type' => 'INT', 'default' => 1000],
            'rating_delta'       => ['type' => 'INT', 'default' => 0],
            'is_mvp'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'notes'              => ['type' => 'TEXT', 'null' => true],
            'created_by'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tenant_id', 'player_id', 'match_date']);
        $this->forge->createTable('player_match_history', true);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id'   => ['type' => 'INT', 'unsigned' => true],
            'player_id'   => ['type' => 'INT', 'unsigned' => true],
            'code'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 120],
            'description' => ['type' => 'TEXT', 'null' => true],
            'points'      => ['type' => 'INT', 'default' => 0],
            'achieved_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['player_id', 'code']);
        $this->forge->createTable('player_achievements', true);

        $this->addColumnIfMissing('players', 'region', ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'avatar']);
        $this->addColumnIfMissing('players', 'home_branch_id', ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'region']);
        $this->addColumnIfMissing('players', 'level_id', ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'home_branch_id']);
        $this->addColumnIfMissing('players', 'checkin_streak', ['type' => 'INT', 'default' => 0, 'after' => 'rating_score']);
        $this->addColumnIfMissing('players', 'best_checkin_streak', ['type' => 'INT', 'default' => 0, 'after' => 'checkin_streak']);
        $this->addColumnIfMissing('players', 'last_checkin_date', ['type' => 'DATE', 'null' => true, 'after' => 'best_checkin_streak']);
        $this->addColumnIfMissing('players', 'mvp_count', ['type' => 'INT', 'default' => 0, 'after' => 'last_checkin_date']);

        $this->addColumnIfMissing('player_statistics', 'elo_rating', ['type' => 'INT', 'default' => 1000, 'after' => 'player_id']);
        $this->addColumnIfMissing('player_statistics', 'ranking_points', ['type' => 'INT', 'default' => 0, 'after' => 'elo_rating']);
        $this->addColumnIfMissing('player_statistics', 'total_bookings', ['type' => 'INT', 'default' => 0, 'after' => 'total_losses']);
        $this->addColumnIfMissing('player_statistics', 'checkin_count', ['type' => 'INT', 'default' => 0, 'after' => 'total_bookings']);
        $this->addColumnIfMissing('player_statistics', 'no_show_count', ['type' => 'INT', 'default' => 0, 'after' => 'checkin_count']);
        $this->addColumnIfMissing('player_statistics', 'mvp_count', ['type' => 'INT', 'default' => 0, 'after' => 'best_streak']);
        $this->addColumnIfMissing('player_statistics', 'achievements_count', ['type' => 'INT', 'default' => 0, 'after' => 'mvp_count']);

        $this->seedDefaults();
    }

    public function down()
    {
        $this->forge->dropTable('player_achievements', true);
        $this->forge->dropTable('player_match_history', true);
        $this->forge->dropTable('player_ratings', true);
        $this->forge->dropTable('player_badges', true);
        $this->forge->dropTable('player_levels', true);
    }

    private function addColumnIfMissing(string $table, string $column, array $definition): void
    {
        if (! $this->db->fieldExists($column, $table)) {
            $this->forge->addColumn($table, [$column => $definition]);
        }
    }

    private function seedDefaults(): void
    {
        $tenantRows = $this->db->table('tenants')->select('id')->get()->getResultArray();

        foreach ($tenantRows as $tenant) {
            $tenantId = (int) $tenant['id'];
            $levels = [
                ['code' => 'beginner', 'name' => 'Beginner', 'min_rating' => 0, 'max_rating' => 999, 'color' => '#6c757d', 'sort_order' => 1],
                ['code' => 'intermediate', 'name' => 'Intermediate', 'min_rating' => 1000, 'max_rating' => 1299, 'color' => '#0dcaf0', 'sort_order' => 2],
                ['code' => 'advanced', 'name' => 'Advanced', 'min_rating' => 1300, 'max_rating' => 1599, 'color' => '#ffc107', 'sort_order' => 3],
                ['code' => 'pro', 'name' => 'Pro', 'min_rating' => 1600, 'max_rating' => 9999, 'color' => '#dc3545', 'sort_order' => 4],
            ];

            foreach ($levels as $level) {
                $exists = $this->db->table('player_levels')
                    ->where('tenant_id', $tenantId)
                    ->where('code', $level['code'])
                    ->countAllResults();

                if ($exists === 0) {
                    $level['tenant_id'] = $tenantId;
                    $level['is_active'] = 1;
                    $level['created_at'] = date('Y-m-d H:i:s');
                    $level['updated_at'] = date('Y-m-d H:i:s');
                    $this->db->table('player_levels')->insert($level);
                }
            }
        }
    }
}
