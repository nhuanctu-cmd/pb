<?php

namespace App\Models;

use CodeIgniter\Model;

class MatchIntegrityFlagModel extends Model
{
    protected $table = 'match_integrity_flags';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['match_id', 'player_id', 'flag_code', 'risk_score', 'status', 'details', 'reviewed_by', 'reviewed_at'];
}
