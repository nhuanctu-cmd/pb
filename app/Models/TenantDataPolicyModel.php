<?php

namespace App\Models;

use CodeIgniter\Model;

class TenantDataPolicyModel extends Model
{
    protected $table = 'tenant_data_policies';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'resource_type', 'access_scope', 'effect', 'visibility',
        'requires_consent', 'version', 'status', 'configuration', 'created_by',
    ];
    protected $useTimestamps = true;
}
