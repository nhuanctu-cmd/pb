<?php

namespace App\Models;

use CodeIgniter\Model;

class WebhookDeliveryModel extends Model
{
    protected $table = 'webhook_deliveries';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'endpoint_id', 'event_type', 'payload_json', 'signature', 'status', 'attempts',
        'max_attempts', 'next_attempt_at', 'response_code', 'response_body', 'error_message', 'delivered_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query(
            'SELECT * FROM webhook_deliveries WHERE id = ? AND tenant_id = ? LIMIT 1 FOR UPDATE',
            [$id, $tenantId]
        )->getRowArray();
        return $row ? (object) $row : null;
    }
}
