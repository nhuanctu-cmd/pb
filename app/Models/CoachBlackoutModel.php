<?php

namespace App\Models;

use CodeIgniter\Model;

class CoachBlackoutModel extends Model
{
    protected $table = 'coach_blackouts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'coach_id', 'start_at', 'end_at', 'reason', 'status', 'created_by'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
