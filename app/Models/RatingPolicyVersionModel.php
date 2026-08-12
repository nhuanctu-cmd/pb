<?php

namespace App\Models;

use CodeIgniter\Model;

class RatingPolicyVersionModel extends Model
{
    protected $table = 'rating_policy_versions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['provider_id', 'discipline_id', 'name', 'version', 'effective_from', 'effective_to', 'configuration', 'status', 'created_by'];
    protected $useTimestamps = true;
}
