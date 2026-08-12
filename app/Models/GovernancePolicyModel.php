<?php

namespace App\Models;

use CodeIgniter\Model;

class GovernancePolicyModel extends Model
{
    protected $table = 'governance_policies';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['authority_id', 'code', 'version', 'policy_type', 'rules', 'content_hash', 'effective_from', 'effective_to', 'status', 'created_by'];
}
