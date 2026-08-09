<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAttendanceAndCheckins extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'session_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'player_id' => ['type' => 'INT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['registered', 'attended', 'no_show', 'cancelled'], 'default' => 'registered'],
            'checkin_at' => ['type' => 'DATETIME', 'null' => true],
            'checkout_at' => ['type' => 'DATETIME', 'null' => true],
            'note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['session_id', 'player_id']);
        $this->forge->addKey(['tenant_id', 'session_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('session_id', 'coaching_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('player_id', 'players', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('coaching_attendance');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'tenant_id' => ['type' => 'INT', 'unsigned' => true],
            'event_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'participant_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'checked_in', 'no_show'], 'default' => 'pending'],
            'checkin_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['event_id', 'participant_id']);
        $this->forge->addKey(['tenant_id', 'event_id', 'status']);
        $this->forge->addForeignKey('tenant_id', 'tenants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('event_id', 'competition_events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('participant_id', 'competition_participants', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('competition_checkins');
    }

    public function down()
    {
        $this->forge->dropTable('competition_checkins', true);
        $this->forge->dropTable('coaching_attendance', true);
    }
}
