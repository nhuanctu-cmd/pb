<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceFacilityClubAssignmentFields extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('facility_club_assignments')) {
            return;
        }

        if (! $this->db->fieldExists('start_date', 'facility_club_assignments')) {
            $this->forge->addColumn('facility_club_assignments', [
                'start_date' => ['type' => 'DATE', 'null' => true, 'after' => 'is_primary'],
            ]);
        }

        if (! $this->db->fieldExists('end_date', 'facility_club_assignments')) {
            $this->forge->addColumn('facility_club_assignments', [
                'end_date' => ['type' => 'DATE', 'null' => true, 'after' => 'start_date'],
            ]);
        }

        if (! $this->db->fieldExists('revenue_share', 'facility_club_assignments')) {
            $this->forge->addColumn('facility_club_assignments', [
                'revenue_share' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true, 'default' => null, 'after' => 'end_date'],
            ]);
        }

        if (! $this->db->fieldExists('booking_priority', 'facility_club_assignments')) {
            $this->forge->addColumn('facility_club_assignments', [
                'booking_priority' => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0, 'after' => 'revenue_share'],
            ]);
        }

        if (! $this->db->fieldExists('allowed_courts', 'facility_club_assignments')) {
            $this->forge->addColumn('facility_club_assignments', [
                'allowed_courts' => ['type' => 'JSON', 'null' => true, 'after' => 'booking_priority'],
            ]);
        }

        if (! $this->db->fieldExists('allowed_hours', 'facility_club_assignments')) {
            $this->forge->addColumn('facility_club_assignments', [
                'allowed_hours' => ['type' => 'JSON', 'null' => true, 'after' => 'allowed_courts'],
            ]);
        }
    }

    public function down()
    {
        // keep data and do not force drop for backward-compatible upgrades
    }
}

