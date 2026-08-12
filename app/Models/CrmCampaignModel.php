<?php

namespace App\Models;

use CodeIgniter\Model;

class CrmCampaignModel extends Model
{
    protected $table = 'crm_campaigns';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id', 'name', 'channel', 'segment', 'status', 'subject', 'message', 'scheduled_at',
        'sent_at', 'throttle_per_minute', 'max_retries', 'created_by', 'updated_by',
    ];
}
