<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerCrmFoundation extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('customers')) {
            $this->forge->addField([
                'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'           => ['type' => 'INT', 'unsigned' => true],
                'player_id'           => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'full_name'           => ['type' => 'VARCHAR', 'constraint' => 255],
                'phone'               => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                'email'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status'              => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'merged'], 'default' => 'active'],
                'source'              => ['type' => 'ENUM', 'constraint' => ['booking', 'player', 'admin', 'import', 'api', 'other'], 'default' => 'booking'],
                'first_seen_at'       => ['type' => 'DATETIME', 'null' => true],
                'last_seen_at'        => ['type' => 'DATETIME', 'null' => true],
                'last_booking_at'     => ['type' => 'DATETIME', 'null' => true],
                'last_visit_at'       => ['type' => 'DATETIME', 'null' => true],
                'total_bookings'      => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'completed_bookings'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'no_show_count'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'total_spend'         => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'favorite_court_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'metadata'            => ['type' => 'JSON', 'null' => true],
                'created_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'updated_by'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at'          => ['type' => 'DATETIME', 'null' => true],
                'updated_at'          => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'status']);
            $this->forge->addKey(['tenant_id', 'phone']);
            $this->forge->addKey(['tenant_id', 'email']);
            $this->forge->addKey(['tenant_id', 'player_id']);
            $this->forge->createTable('customers', true);
        }

        if (! $this->db->tableExists('customer_timeline_events')) {
            $this->forge->addField([
                'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'    => ['type' => 'INT', 'unsigned' => true],
                'customer_id'  => ['type' => 'BIGINT', 'unsigned' => true],
                'event_type'   => ['type' => 'VARCHAR', 'constraint' => 80],
                'title'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'description'  => ['type' => 'TEXT', 'null' => true],
                'source_type'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'source_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
                'actor_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'payload'     => ['type' => 'JSON', 'null' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'customer_id', 'created_at']);
            $this->forge->addKey(['tenant_id', 'event_type']);
            $this->forge->createTable('customer_timeline_events', true);
        }

        if (! $this->db->tableExists('customer_tags')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'  => ['type' => 'INT', 'unsigned' => true],
                'code'       => ['type' => 'VARCHAR', 'constraint' => 80],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 150],
                'color'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'status'     => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tenant_id', 'code']);
            $this->forge->createTable('customer_tags', true);
        }

        if (! $this->db->tableExists('customer_tag_links')) {
            $this->forge->addField([
                'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id'   => ['type' => 'INT', 'unsigned' => true],
                'customer_id' => ['type' => 'BIGINT', 'unsigned' => true],
                'tag_id'      => ['type' => 'INT', 'unsigned' => true],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tenant_id', 'customer_id', 'tag_id']);
            $this->forge->createTable('customer_tag_links', true);
        }

        if ($this->db->tableExists('bookings') && ! $this->db->fieldExists('customer_id', 'bookings')) {
            $this->forge->addColumn('bookings', [
                'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'player_id'],
            ]);
            $this->db->query('ALTER TABLE bookings ADD INDEX idx_bookings_tenant_customer (tenant_id, customer_id)');
        }

        if ($this->db->tableExists('invoices') && ! $this->db->fieldExists('customer_id', 'invoices')) {
            $this->forge->addColumn('invoices', [
                'customer_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'player_id'],
            ]);
            $this->db->query('ALTER TABLE invoices ADD INDEX idx_invoices_tenant_customer (tenant_id, customer_id)');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('invoices') && $this->db->fieldExists('customer_id', 'invoices')) {
            $this->forge->dropColumn('invoices', 'customer_id');
        }
        if ($this->db->tableExists('bookings') && $this->db->fieldExists('customer_id', 'bookings')) {
            $this->forge->dropColumn('bookings', 'customer_id');
        }
        $this->forge->dropTable('customer_tag_links', true);
        $this->forge->dropTable('customer_tags', true);
        $this->forge->dropTable('customer_timeline_events', true);
        $this->forge->dropTable('customers', true);
    }
}
