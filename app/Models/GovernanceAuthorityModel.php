<?php

namespace App\Models;

use CodeIgniter\Model;

class GovernanceAuthorityModel extends Model
{
    protected $table = 'governance_authorities';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['uuid', 'parent_id', 'name', 'authority_type', 'country_code', 'scope_reference', 'status', 'effective_from', 'effective_to', 'created_by'];
}
