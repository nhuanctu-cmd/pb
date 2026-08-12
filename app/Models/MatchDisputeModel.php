<?php

namespace App\Models;

use CodeIgniter\Model;

class MatchDisputeModel extends Model
{
    protected $table = 'match_disputes';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id', 'match_id', 'opened_by', 'reason_code', 'reason', 'evidence',
        'status', 'resolution', 'resolved_by', 'resolved_at',
    ];
}
