<?php

namespace App\Models;

use CodeIgniter\Model;

class UnifiedMatchModel extends Model
{
    protected $table = 'matches';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'public_id', 'tenant_id', 'source_type', 'source_id', 'discipline',
        'status', 'result_type', 'verification_status', 'scheduled_at', 'completed_at',
        'created_by', 'verified_by', 'metadata', 'source_code', 'competition_type', 'source_organization_id', 'venue_id', 'court_id', 'started_at', 'finished_at', 'ruleset_version_id', 'provenance_id',
    ];
}
