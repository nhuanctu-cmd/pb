<?php

namespace App\Models;

use CodeIgniter\Model;

class RulesetModel extends Model
{
    protected $table = 'rulesets';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'name', 'discipline', 'authority_id', 'status'];
}
