<?php

namespace App\Models;

use CodeIgniter\Model;

class MatchResultModel extends Model
{
    protected $table = 'match_results';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'match_id', 'current_version_id', 'version_no', 'status', 'result_type',
        'winner_side', 'published_at',
    ];
}
