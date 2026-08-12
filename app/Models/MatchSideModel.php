<?php

namespace App\Models;

use CodeIgniter\Model;

class MatchSideModel extends Model
{
    protected $table = 'match_sides';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['match_id', 'side_code', 'side_order', 'result', 'metadata'];
}
