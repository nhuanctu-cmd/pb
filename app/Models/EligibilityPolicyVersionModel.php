<?php

namespace App\Models;

use CodeIgniter\Model;

class EligibilityPolicyVersionModel extends Model
{
    protected $table = 'eligibility_policy_versions';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['code', 'version', 'policy', 'content_hash', 'effective_from', 'effective_to', 'status', 'created_by', 'created_at'];
}
