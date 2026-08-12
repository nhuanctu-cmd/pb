<?php

namespace App\Commands;

use App\Models\JobModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WebhookDeliver extends BaseCommand
{
    protected $group = 'webhooks';
    protected $name = 'webhooks:deliver';
    protected $description = 'Deliver queued tenant webhooks with signed payloads.';
    protected $usage = 'webhooks:deliver [limit]';
    protected $arguments = ['limit' => 'Maximum deliveries to reserve (default 20).'];

    public function run(array $params)
    {
        $limit = max(1, min(100, (int) ($params[0] ?? 20)));
        $jobs = (new JobModel())->reserve('webhook_delivery', $limit);
        $service = service('webhookService');
        $completed = 0;
        $failed = 0;
        foreach ($jobs as $job) {
            $payload = json_decode((string) $job->payload, true) ?: [];
            $result = $service->deliver((int) ($payload['delivery_id'] ?? 0), (int) ($payload['tenant_id'] ?? 0));
            $jobModel = new JobModel();
            if (! empty($result['success'])) {
                $jobModel->markCompleted((int) $job->id);
                $completed++;
                continue;
            }
            $failed++;
            if ((int) $job->attempts + 1 >= (int) $job->max_attempts) {
                $jobModel->markFailed((int) $job->id, (string) ($result['message'] ?? 'Webhook delivery failed.'));
            } else {
                $delay = min(3600, 2 ** min((int) $job->attempts + 1, 10));
                $jobModel->release((int) $job->id, $delay);
            }
        }
        CLI::write(sprintf('Webhook deliveries: %d completed, %d failed.', $completed, $failed));
    }
}
