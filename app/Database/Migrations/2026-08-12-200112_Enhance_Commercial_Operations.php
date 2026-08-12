<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceCommercialOperations extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('daily_closings')) {
            if (! $this->db->fieldExists('is_locked', 'daily_closings')) {
                $this->forge->addColumn('daily_closings', [
                    'is_locked' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 0,
                        'null' => false,
                        'after' => 'status',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('digital_signature_name', 'daily_closings')) {
                $this->forge->addColumn('daily_closings', [
                    'digital_signature_name' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => true,
                        'after' => 'is_locked',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('digital_signature_at', 'daily_closings')) {
                $this->forge->addColumn('daily_closings', [
                    'digital_signature_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'digital_signature_name',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('digital_signature_by', 'daily_closings')) {
                $this->forge->addColumn('daily_closings', [
                    'digital_signature_by' => [
                        'type' => 'INT',
                        'unsigned' => true,
                        'null' => true,
                        'after' => 'digital_signature_at',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('locked_at', 'daily_closings')) {
                $this->forge->addColumn('daily_closings', [
                    'locked_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'digital_signature_by',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('locked_by', 'daily_closings')) {
                $this->forge->addColumn('daily_closings', [
                    'locked_by' => [
                        'type' => 'INT',
                        'unsigned' => true,
                        'null' => true,
                        'after' => 'locked_at',
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('crm_campaigns')) {
            if (! $this->db->fieldExists('throttle_per_minute', 'crm_campaigns')) {
                $this->forge->addColumn('crm_campaigns', [
                    'throttle_per_minute' => [
                        'type' => 'INT',
                        'null' => false,
                        'default' => 60,
                        'after' => 'scheduled_at',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('max_retries', 'crm_campaigns')) {
                $this->forge->addColumn('crm_campaigns', [
                    'max_retries' => [
                        'type' => 'TINYINT',
                        'unsigned' => true,
                        'default' => 3,
                        'null' => false,
                        'after' => 'throttle_per_minute',
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('crm_campaign_recipients')) {
            if (! $this->db->fieldExists('attempts', 'crm_campaign_recipients')) {
                $this->forge->addColumn('crm_campaign_recipients', [
                    'attempts' => [
                        'type' => 'TINYINT',
                        'unsigned' => true,
                        'null' => false,
                        'default' => 0,
                        'after' => 'error_message',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('next_retry_at', 'crm_campaign_recipients')) {
                $this->forge->addColumn('crm_campaign_recipients', [
                    'next_retry_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'attempts',
                    ],
                ]);
            }

            if (! $this->db->fieldExists('last_attempt_at', 'crm_campaign_recipients')) {
                $this->forge->addColumn('crm_campaign_recipients', [
                    'last_attempt_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'after' => 'next_retry_at',
                    ],
                ]);
            }
        }

        if (! $this->db->tableExists('membership_renewal_histories')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'tenant_id' => ['type' => 'INT', 'unsigned' => true],
                'membership_id' => ['type' => 'INT', 'unsigned' => true],
                'player_id' => ['type' => 'INT', 'unsigned' => true],
                'package_id_before' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'package_id_after' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'start_date_before' => ['type' => 'DATE', 'null' => true],
                'end_date_before' => ['type' => 'DATE', 'null' => true],
                'start_date_after' => ['type' => 'DATE', 'null' => true],
                'end_date_after' => ['type' => 'DATE', 'null' => true],
                'action' => ['type' => 'VARCHAR', 'constraint' => 50],
                'actor_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['tenant_id', 'membership_id']);
            $this->forge->createTable('membership_renewal_histories', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('membership_renewal_histories')) {
            $this->forge->dropTable('membership_renewal_histories', true);
        }

        if ($this->db->tableExists('crm_campaign_recipients')) {
            if ($this->db->fieldExists('last_attempt_at', 'crm_campaign_recipients')) {
                $this->forge->dropColumn('crm_campaign_recipients', 'last_attempt_at');
            }
            if ($this->db->fieldExists('next_retry_at', 'crm_campaign_recipients')) {
                $this->forge->dropColumn('crm_campaign_recipients', 'next_retry_at');
            }
            if ($this->db->fieldExists('attempts', 'crm_campaign_recipients')) {
                $this->forge->dropColumn('crm_campaign_recipients', 'attempts');
            }
        }

        if ($this->db->tableExists('crm_campaigns')) {
            if ($this->db->fieldExists('max_retries', 'crm_campaigns')) {
                $this->forge->dropColumn('crm_campaigns', 'max_retries');
            }
            if ($this->db->fieldExists('throttle_per_minute', 'crm_campaigns')) {
                $this->forge->dropColumn('crm_campaigns', 'throttle_per_minute');
            }
        }

        if ($this->db->tableExists('daily_closings')) {
            if ($this->db->fieldExists('locked_by', 'daily_closings')) {
                $this->forge->dropColumn('daily_closings', 'locked_by');
            }
            if ($this->db->fieldExists('locked_at', 'daily_closings')) {
                $this->forge->dropColumn('daily_closings', 'locked_at');
            }
            if ($this->db->fieldExists('digital_signature_by', 'daily_closings')) {
                $this->forge->dropColumn('daily_closings', 'digital_signature_by');
            }
            if ($this->db->fieldExists('digital_signature_at', 'daily_closings')) {
                $this->forge->dropColumn('daily_closings', 'digital_signature_at');
            }
            if ($this->db->fieldExists('digital_signature_name', 'daily_closings')) {
                $this->forge->dropColumn('daily_closings', 'digital_signature_name');
            }
            if ($this->db->fieldExists('is_locked', 'daily_closings')) {
                $this->forge->dropColumn('daily_closings', 'is_locked');
            }
        }
    }
}
