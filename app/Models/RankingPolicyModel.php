<?php

namespace App\Models;

use CodeIgniter\Model;

class RankingPolicyModel extends Model
{
    protected $table = 'ranking_policies';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['authority_id', 'code', 'name', 'season', 'rules', 'status'];
}
