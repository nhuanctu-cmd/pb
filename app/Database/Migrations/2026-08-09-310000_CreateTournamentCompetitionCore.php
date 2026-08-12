<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTournamentCompetitionCore extends Migration
{
    public function up()
    {
        // Tournament verification levels and sanctioning
        if (! $this->db->tableExists('tournament_tiers')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'null for platform-global tiers'],
                'ranking_authority_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'name_vi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'name_en' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'point_multiplier' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 1.00],
                'default_rating_weight' => ['type' => 'DECIMAL', 'constraint' => '4,2', 'default' => 1.00],
                'sort_order' => ['type' => 'INT', 'default' => 0],
                'is_active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
                'deleted_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'ranking_authority_id']);
            $this->forge->createTable('tournament_tiers', true);
        }

        // Tournament sanctions (ranking eligibility approval)
        if (! $this->db->tableExists('tournament_sanctions')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tournament_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'ranking_authority_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'sanction_id' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'comment' => 'e.g. VNPKL-2027-HCM-00128'],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
                'tier_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'point_multiplier' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'default' => 1.00],
                'approved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'approved_at' => ['type' => 'DATETIME', 'null' => true],
                'expires_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tournament_id', 'ranking_authority_id']);
            $this->forge->addUniqueKey('sanction_id');
            $this->forge->createTable('tournament_sanctions', true);
        }

        // Extend tournaments with verification level
        if (! $this->db->fieldExists('verification_level', 'tournaments')) {
            $this->forge->addColumn('tournaments', [
                'verification_level' => ['type' => 'ENUM', 'constraint' => ['community', 'club', 'verified', 'official', 'national'], 'default' => 'community', 'after' => 'status'],
                'organizer_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'branch_id', 'comment' => 'organization/tenant acting as organizer'],
                'organizer_reputation_score' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 50, 'after' => 'verification_level'],
            ]);
        }

        // Extend tournament categories with eligibility rules
        if (! $this->db->fieldExists('discipline', 'tournament_categories')) {
            $this->forge->addColumn('tournament_categories', [
                'discipline' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'singles', 'after' => 'category_type'],
                'gender_category' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'men', 'after' => 'discipline'],
                'age_category' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'open', 'after' => 'gender_category'],
                'team_size' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1, 'after' => 'age_category'],
                'entry_capacity' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'max_teams'],
                'waitlist_capacity' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'entry_capacity'],
                'eligibility_rules' => ['type' => 'JSON', 'null' => true, 'after' => 'waitlist_capacity'],
            ]);
        }

        // Extend tournament registrations with partner, eligibility, check-in
        if (! $this->db->fieldExists('partner_player_id', 'tournament_registrations')) {
            $this->forge->addColumn('tournament_registrations', [
                'partner_player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'team_id'],
                'registration_status' => ['type' => 'ENUM', 'constraint' => ['draft', 'pending', 'confirmed', 'waitlisted', 'cancelled'], 'default' => 'draft', 'after' => 'approval_status'],
                'eligibility_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'passed', 'flagged'], 'default' => 'pending', 'after' => 'registration_status'],
                'checked_in_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'eligibility_status'],
                'no_show' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'checked_in_at'],
                'waitlist_position' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'no_show'],
            ]);
        }

        // Tournament check-ins
        if (! $this->db->tableExists('tournament_checkins')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'tournament_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'category_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'registration_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'player_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'qr_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['checked_in', 'no_show', 'override'], 'default' => 'checked_in'],
                'checked_in_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'checked_in_at' => ['type' => 'DATETIME', 'null' => true],
                'note' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['registration_id', 'player_id']);
            $this->forge->addKey(['tournament_id', 'category_id', 'status']);
            $this->forge->addKey('qr_code');
            $this->forge->createTable('tournament_checkins', true);
        }

        // Tournament disputes
        if (! $this->db->tableExists('tournament_disputes')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'tournament_id' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'match_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'raised_by' => ['type' => 'INT', 'unsigned' => true, 'null' => false],
                'reason' => ['type' => 'TEXT', 'null' => true],
                'status' => ['type' => 'ENUM', 'constraint' => ['open', 'reviewing', 'upheld', 'corrected'], 'default' => 'open'],
                'resolution' => ['type' => 'TEXT', 'null' => true],
                'resolved_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'resolved_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tournament_id', 'status']);
            $this->forge->createTable('tournament_disputes', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('tournament_disputes', true);
        $this->forge->dropTable('tournament_checkins', true);

        if ($this->db->fieldExists('partner_player_id', 'tournament_registrations')) {
            $this->forge->dropColumn('tournament_registrations', [
                'partner_player_id', 'registration_status', 'eligibility_status',
                'checked_in_at', 'no_show', 'waitlist_position',
            ]);
        }

        if ($this->db->fieldExists('discipline', 'tournament_categories')) {
            $this->forge->dropColumn('tournament_categories', [
                'discipline', 'gender_category', 'age_category', 'team_size',
                'entry_capacity', 'waitlist_capacity', 'eligibility_rules',
            ]);
        }

        if ($this->db->fieldExists('verification_level', 'tournaments')) {
            $this->forge->dropColumn('tournaments', [
                'verification_level', 'organizer_id', 'organizer_reputation_score',
            ]);
        }

        $this->forge->dropTable('tournament_sanctions', true);
        $this->forge->dropTable('tournament_tiers', true);
    }
}
