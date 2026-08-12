<?php

namespace App\Models;

use CodeIgniter\Model;

class AppealEvidenceModel extends Model
{
    protected $table = 'appeal_evidence';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['appeal_id', 'submitted_by', 'evidence_type', 'reference', 'notes', 'created_at'];
}
