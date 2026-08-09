<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCoachingInvoices extends Migration
{
    public function up()
    {
        $this->forge->addColumn('coaching_session_players', ['invoice_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'player_id']]);
        $this->forge->addKey('invoice_id', false, false, 'idx_coaching_player_invoice');
        $this->forge->addForeignKey('invoice_id', 'invoices', 'id', 'CASCADE', 'SET NULL', 'fk_coaching_player_invoice');
    }

    public function down()
    {
        $this->forge->dropColumn('coaching_session_players', 'invoice_id');
    }
}
