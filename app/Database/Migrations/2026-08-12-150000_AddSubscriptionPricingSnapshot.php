<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSubscriptionPricingSnapshot extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('tenant_subscriptions')) {
            return;
        }

        if (! $this->db->fieldExists('plan_snapshot', 'tenant_subscriptions')) {
            $this->forge->addColumn('tenant_subscriptions', [
                'plan_snapshot' => [
                    'type' => 'JSON',
                    'null' => true,
                    'comment' => 'Snapshot plan metadata at subscribe time',
                    'after' => 'ends_at',
                ],
            ]);
        }

        if (! $this->db->fieldExists('pricing_term', 'tenant_subscriptions')) {
            $this->forge->addColumn('tenant_subscriptions', [
                'pricing_term' => [
                    'type' => 'ENUM',
                    'constraint' => ['monthly', 'yearly', 'trial'],
                    'default' => 'monthly',
                    'null' => false,
                    'after' => 'plan_snapshot',
                ],
            ]);
        }

        if (! $this->db->fieldExists('currency_snapshot', 'tenant_subscriptions')) {
            $this->forge->addColumn('tenant_subscriptions', [
                'currency_snapshot' => [
                    'type' => 'CHAR',
                    'constraint' => 3,
                    'default' => 'VND',
                    'null' => false,
                    'after' => 'pricing_term',
                ],
            ]);
        }

        if (! $this->db->fieldExists('price_snapshot_amount', 'tenant_subscriptions')) {
            $this->forge->addColumn('tenant_subscriptions', [
                'price_snapshot_amount' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                    'null' => false,
                    'after' => 'currency_snapshot',
                ],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('tenant_subscriptions')) {
            return;
        }

        if ($this->db->fieldExists('plan_snapshot', 'tenant_subscriptions')) {
            $this->forge->dropColumn('tenant_subscriptions', 'plan_snapshot');
        }
        if ($this->db->fieldExists('pricing_term', 'tenant_subscriptions')) {
            $this->forge->dropColumn('tenant_subscriptions', 'pricing_term');
        }
        if ($this->db->fieldExists('currency_snapshot', 'tenant_subscriptions')) {
            $this->forge->dropColumn('tenant_subscriptions', 'currency_snapshot');
        }
        if ($this->db->fieldExists('price_snapshot_amount', 'tenant_subscriptions')) {
            $this->forge->dropColumn('tenant_subscriptions', 'price_snapshot_amount');
        }
    }
}

