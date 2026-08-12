<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTournamentCheckinStatus extends Migration
{
    public function up()
    {
        if (! in_array('checkin_status', $this->db->getFieldNames('tournament_registrations'), true)) {
            $this->forge->addColumn('tournament_registrations', [
                'checkin_status' => [
                    'type' => 'ENUM',
                    'constraint' => ['pending', 'checked_in', 'no_show'],
                    'default' => 'pending',
                    'after' => 'checked_in_at',
                ],
            ]);
        }
    }

    public function down()
    {
        if (in_array('checkin_status', $this->db->getFieldNames('tournament_registrations'), true)) {
            $this->forge->dropColumn('tournament_registrations', 'checkin_status');
        }
    }
}
