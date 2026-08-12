<?php

namespace App\Services;

use App\Models\CrmCampaignModel;

class CrmCampaignService
{
    private CrmCampaignModel $campaigns;

    public function __construct()
    {
        $this->campaigns = new CrmCampaignModel();
    }

    public function list(int $tenantId): array
    {
        return $this->campaigns->where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->findAll();
    }

    public function createDraft(int $tenantId, array $data, ?int $userId): ?int
    {
        $data['tenant_id'] = $tenantId;
        $data['status'] = 'draft';
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        $id = $this->campaigns->insert($data);
        return $id ? (int) $id : null;
    }

    public function recipients(int $tenantId, int $campaignId): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('crm_campaign_recipients')) return [];
        return $db->table('crm_campaign_recipients r')->select('r.*, c.full_name, c.phone, c.email')
            ->join('customers c', 'c.id = r.customer_id', 'left')->where('r.tenant_id', $tenantId)
            ->where('r.campaign_id', $campaignId)->orderBy('c.full_name', 'ASC')->get()->getResult();
    }

    public function launch(int $tenantId, int $campaignId, ?int $userId): int
    {
        $campaign = $this->campaigns->where('tenant_id', $tenantId)->find($campaignId);
        if (! $campaign) return -1;
        $db = \Config\Database::connect();
        $customers = $this->segmentCustomers($db, $tenantId, (string) $campaign->segment);
        $now = date('Y-m-d H:i:s');
        foreach ($customers as $customer) {
            $exists = $db->table('crm_campaign_recipients')->where(['campaign_id' => $campaignId, 'customer_id' => $customer->id, 'channel' => $campaign->channel])->countAllResults();
            if (! $exists) {
                $db->table('crm_campaign_recipients')->insert([
                    'tenant_id' => $tenantId, 'campaign_id' => $campaignId, 'customer_id' => $customer->id,
                    'channel' => $campaign->channel, 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        $this->campaigns->update($campaignId, ['status' => 'running', 'updated_by' => $userId]);
        return count($customers);
    }

    private function segmentCustomers($db, int $tenantId, string $segment): array
    {
        $query = $db->table('customers c')->select('c.*')->where('c.tenant_id', $tenantId)->where('c.status', 'active')->where('c.deleted_at', null);
        if ($segment === 'inactive') {
            $query->groupStart()->where('c.last_visit_at <', date('Y-m-d H:i:s', strtotime('-30 days')))->orWhere('c.last_visit_at', null)->groupEnd();
        } elseif ($segment === 'high_value') {
            $query->where('c.total_spend >=', 5000000);
        } elseif ($segment === 'expiring_membership' && $db->tableExists('memberships')) {
            $query->join('memberships m', 'm.player_id = c.player_id AND m.tenant_id = c.tenant_id', 'inner')
                ->where('m.status', 'active')->where('m.end_date <=', date('Y-m-d', strtotime('+30 days')))->where('m.end_date >=', date('Y-m-d'));
        }
        return $query->groupBy('c.id')->orderBy('c.full_name', 'ASC')->get()->getResult();
    }
}
