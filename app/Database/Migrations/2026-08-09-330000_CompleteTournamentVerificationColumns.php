<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bù schema cho các database đã chạy migration tournament core trước khi
 * phần mở rộng eligibility/verification được bổ sung vào codebase.
 */
class CompleteTournamentVerificationColumns extends Migration
{
    public function up()
    {
        $this->addMissing('tournaments', [
            'verification_level' => ['type' => 'ENUM', 'constraint' => ['community', 'club', 'verified', 'official', 'national'], 'default' => 'community', 'after' => 'status'],
            'organizer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'branch_id'],
            'organizer_reputation_score' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 50, 'after' => 'verification_level'],
        ]);

        $this->addMissing('tournament_categories', [
            'discipline' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'singles', 'after' => 'category_type'],
            'gender_category' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'men', 'after' => 'discipline'],
            'age_category' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'open', 'after' => 'gender_category'],
            'team_size' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1, 'after' => 'age_category'],
            'entry_capacity' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'max_teams'],
            'waitlist_capacity' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'entry_capacity'],
            'eligibility_rules' => ['type' => 'JSON', 'null' => true, 'after' => 'waitlist_capacity'],
        ]);

        $this->addMissing('tournament_registrations', [
            'partner_player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'team_id'],
            'registration_status' => ['type' => 'ENUM', 'constraint' => ['draft', 'pending', 'confirmed', 'waitlisted', 'cancelled'], 'default' => 'draft', 'after' => 'approval_status'],
            'eligibility_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'passed', 'flagged'], 'default' => 'pending', 'after' => 'registration_status'],
            'checked_in_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'eligibility_status'],
            'checkin_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'checked_in', 'no_show'], 'default' => 'pending', 'after' => 'checked_in_at'],
            'no_show' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'checked_in_at'],
            'waitlist_position' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'no_show'],
        ]);
    }

    public function down()
    {
        foreach ([
            'tournaments' => ['verification_level', 'organizer_id', 'organizer_reputation_score'],
            'tournament_categories' => ['discipline', 'gender_category', 'age_category', 'team_size', 'entry_capacity', 'waitlist_capacity', 'eligibility_rules'],
            'tournament_registrations' => ['partner_player_id', 'registration_status', 'eligibility_status', 'checked_in_at', 'checkin_status', 'no_show', 'waitlist_position'],
        ] as $table => $columns) {
            $existing = array_values(array_intersect($columns, $this->db->getFieldNames($table)));
            if ($existing) {
                $this->forge->dropColumn($table, $existing);
            }
        }
    }

    private function addMissing(string $table, array $columns): void
    {
        $existing = $this->db->getFieldNames($table);
        foreach ($columns as $name => $definition) {
            if (! in_array($name, $existing, true)) {
                $this->forge->addColumn($table, [$name => $definition]);
                $existing[] = $name;
            }
        }
    }
}
