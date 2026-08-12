<?php

namespace App\Models;

use CodeIgniter\Model;

class SanctionConditionModel extends Model
{
    protected $table = 'sanction_conditions';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['sanction_id', 'code', 'requirement', 'status', 'verified_by', 'verified_at', 'created_at'];
}
