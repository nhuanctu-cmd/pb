<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommercialOperations extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('daily_closings')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true],
                'branch_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'closing_date' => ['type' => 'DATE'],
                'status' => ['type' => 'ENUM', 'constraint' => ['open', 'closed', 'reopened'], 'default' => 'open'],
                'cash_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'qr_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'wallet_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'other_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'billed_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'collected_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'refund_total' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'discrepancy_amount' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'closed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'closed_at' => ['type' => 'DATETIME', 'null' => true],
                'reopened_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'reopened_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['tenant_id', 'branch_id', 'closing_date']);
            $this->forge->addKey(['tenant_id', 'closing_date']);
            $this->forge->createTable('daily_closings', true);
        }

        if (! $this->db->tableExists('crm_campaigns')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 180],
                'channel' => ['type' => 'ENUM', 'constraint' => ['in_app', 'email', 'sms', 'zalo'], 'default' => 'in_app'],
                'segment' => ['type' => 'ENUM', 'constraint' => ['all', 'expiring_membership', 'inactive', 'high_value', 'manual'], 'default' => 'all'],
                'status' => ['type' => 'ENUM', 'constraint' => ['draft', 'scheduled', 'running', 'completed', 'cancelled'], 'default' => 'draft'],
                'subject' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'message' => ['type' => 'TEXT', 'null' => true],
                'scheduled_at' => ['type' => 'DATETIME', 'null' => true],
                'sent_at' => ['type' => 'DATETIME', 'null' => true],
                'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'status']);
            $this->forge->createTable('crm_campaigns', true);
        }

        if (! $this->db->tableExists('crm_campaign_recipients')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true],
                'campaign_id' => ['type' => 'INT', 'unsigned' => true],
                'customer_id' => ['type' => 'BIGINT', 'unsigned' => true],
                'channel' => ['type' => 'ENUM', 'constraint' => ['in_app', 'email', 'sms', 'zalo'], 'default' => 'in_app'],
                'status' => ['type' => 'ENUM', 'constraint' => ['pending', 'sent', 'failed', 'skipped'], 'default' => 'pending'],
                'sent_at' => ['type' => 'DATETIME', 'null' => true],
                'error_message' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['campaign_id', 'customer_id', 'channel']);
            $this->forge->addKey(['tenant_id', 'status']);
            $this->forge->createTable('crm_campaign_recipients', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('crm_campaign_recipients', true);
        $this->forge->dropTable('crm_campaigns', true);
        $this->forge->dropTable('daily_closings', true);
    }
}
