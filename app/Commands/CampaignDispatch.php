<?php

namespace App\Commands;

use App\Services\CrmCampaignService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\JobModel;

class CampaignDispatch extends BaseCommand
{
    protected $group = 'crm';
    protected $name = 'crm:campaigns:dispatch';
    protected $description = 'Dispatch CRM campaigns by schedule, with throttle + retry backoff.';
    protected $usage = 'crm:campaigns:dispatch [tenant_id]';

    public function run(array $params)
    {
        $tenantId = (int) ($params[0] ?? 0);
        $service = new CrmCampaignService();

        if ($tenantId > 0) {
            $processed = $service->dispatchDue($tenantId, null);
            CLI::write("CRM dispatch tenant {$tenantId}: processed {$processed} row(s).");
            return;
        }

        $jobModel = new JobModel();
        $queues = $jobModel->where('queue', 'campaign_dispatch')->findAll();
        if (! empty($queues)) {
            foreach ($queues as $queueItem) {
                $payload = json_decode((string) ($queueItem->payload ?? '{}'), true) ?: [];
                $tenant = (int) ($payload['tenant_id'] ?? 0);
                if ($tenant > 0) {
                    $service->dispatchDue($tenant, null);
                }
                $jobModel->delete((int) $queueItem->id);
            }
            CLI::write('CRM dispatch command processed queued jobs.');
            return;
        }

        CLI::write('No queued CRM tenants. Run crm:campaigns:dispatch <tenant_id> instead.');
    }
}
