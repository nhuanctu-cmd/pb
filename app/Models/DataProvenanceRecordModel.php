<?php

namespace App\Models;

use CodeIgniter\Model;

class DataProvenanceRecordModel extends Model
{
    protected $table = 'data_provenance_records';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['entity_type', 'entity_id', 'source_type', 'source_id', 'source_organization_id', 'created_by', 'verified_by', 'verification_level', 'import_batch_id', 'external_reference', 'policy_version_id', 'parent_provenance_id', 'metadata', 'created_at', 'verified_at'];
}
