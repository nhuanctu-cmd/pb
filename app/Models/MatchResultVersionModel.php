<?php

namespace App\Models;

use CodeIgniter\Model;

class MatchResultVersionModel extends Model
{
    protected $table = 'match_result_versions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'match_id', 'version_no', 'status', 'result_type', 'winner_side', 'payload',
        'submitted_by', 'confirmed_by', 'ruleset_version_id', 'authority_id', 'verified_by', 'verified_at', 'source', 'provenance_id', 'change_reason', 'created_at',
    ];
}
