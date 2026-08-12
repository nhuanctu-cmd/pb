<?php

namespace App\Models;

use CodeIgniter\Model;

class PartnerApiKeyModel extends Model
{
    protected $table = 'partner_api_keys';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'name', 'key_prefix', 'key_hash', 'scopes', 'status',
        'expires_at', 'last_used_at', 'revoked_at', 'created_by',
    ];
    protected $useTimestamps = true;
}
