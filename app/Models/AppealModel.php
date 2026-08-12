<?php

namespace App\Models;

use CodeIgniter\Model;

class AppealModel extends Model
{
    protected $table = 'appeals';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['uuid', 'tenant_id', 'subject_type', 'subject_id', 'opened_by', 'authority_id', 'status', 'reason', 'decision_id', 'parent_appeal_id', 'decided_by', 'decided_at'];
}
