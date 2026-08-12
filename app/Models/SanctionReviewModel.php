<?php

namespace App\Models;

use CodeIgniter\Model;

class SanctionReviewModel extends Model
{
    protected $table = 'sanction_reviews';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['sanction_id', 'reviewer_id', 'status', 'decision', 'reason', 'evidence', 'reviewed_at', 'created_at'];
}
