<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompetitionEntryFees extends Migration
{
    public function up()
    {
        $this->forge->addColumn('competition_events', [
            'entry_fee' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'format'],
        ]);
        $this->forge->addColumn('competition_participants', [
            'invoice_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'player_id'],
        ]);
        $this->forge->addKey('invoice_id', false, false, 'idx_competition_participant_invoice');
        $this->forge->addForeignKey('invoice_id', 'invoices', 'id', 'CASCADE', 'SET NULL', 'fk_competition_participant_invoice');
    }

    public function down()
    {
        $this->forge->dropColumn('competition_participants', 'invoice_id');
        $this->forge->dropColumn('competition_events', 'entry_fee');
    }
}
