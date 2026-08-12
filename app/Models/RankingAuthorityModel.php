<?php

namespace App\Models;

use CodeIgniter\Model;

class RankingAuthorityModel extends Model
{
    protected $table = 'ranking_authorities';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'name', 'scope', 'status'];
}
