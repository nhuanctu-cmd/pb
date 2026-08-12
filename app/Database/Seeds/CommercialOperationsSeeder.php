<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/** Idempotent fixtures for the daily commercial operating loop. */
class CommercialOperationsSeeder extends Seeder
{
    public function run()
    {
        if (! $this->db->tableExists('daily_closings') || ! $this->db->tableExists('crm_campaigns')) return;
        $now = date('Y-m-d H:i:s');
        $tenants = $this->db->table('tenants')->where('status', 'active')->where('deleted_at', null)->get()->getResult();
        foreach ($tenants as $tenant) {
            $tenantId = (int) $tenant->id;
            $branch = $this->db->table('branches')->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('id', 'ASC')->get()->getRow();
            $branchId = $branch ? (int) $branch->id : null;
            $exists = $this->db->table('daily_closings')->where(['tenant_id' => $tenantId, 'closing_date' => date('Y-m-d')])->countAllResults();
            if (! $exists) $this->db->table('daily_closings')->insert(['tenant_id' => $tenantId, 'branch_id' => $branchId, 'closing_date' => date('Y-m-d'), 'status' => 'open', 'created_at' => $now, 'updated_at' => $now]);

            $campaign = $this->db->table('crm_campaigns')->where('tenant_id', $tenantId)->where('name', 'Nhắc gia hạn hội viên tháng này')->get()->getRow();
            if (! $campaign) {
                $user = $this->db->table('users')->where('tenant_id', $tenantId)->where('is_active', 1)->orderBy('id', 'ASC')->get()->getRow();
                $this->db->table('crm_campaigns')->insert([
                    'tenant_id' => $tenantId, 'name' => 'Nhắc gia hạn hội viên tháng này', 'channel' => 'in_app',
                    'segment' => 'expiring_membership', 'status' => 'draft', 'subject' => 'Hội viên sắp hết hạn',
                    'message' => 'Xin chào {{customer_name}}, gói hội viên của bạn sắp hết hạn. Hãy gia hạn để tiếp tục giữ ưu đãi sân.',
                    'created_by' => $user?->id, 'updated_by' => $user?->id, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }
}
