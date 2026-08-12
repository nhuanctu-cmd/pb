<?php

namespace App\Models;

use CodeIgniter\Model;

class RulesetVersionModel extends Model
{
    protected $table = 'ruleset_versions';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['ruleset_id', 'version', 'content', 'content_hash', 'effective_from', 'effective_to', 'status', 'created_by', 'created_at'];
}
