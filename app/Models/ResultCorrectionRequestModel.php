<?php

namespace App\Models;

use CodeIgniter\Model;

class ResultCorrectionRequestModel extends Model
{
    protected $table = 'result_correction_requests';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['match_id', 'original_result_version_id', 'requested_result', 'reason', 'evidence', 'requester_id', 'reviewer_id', 'status', 'decision_reason', 'new_result_version_id', 'created_at', 'reviewed_at'];
}
