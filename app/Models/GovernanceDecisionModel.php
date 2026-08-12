<?php

namespace App\Models;

use CodeIgniter\Model;

class GovernanceDecisionModel extends Model
{
    protected $table = 'governance_decisions';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['uuid', 'subject_type', 'subject_id', 'authority_id', 'policy_id', 'actor_id', 'decision', 'reason', 'evidence', 'audit_log_id', 'created_at'];
}
