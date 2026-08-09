<?php

namespace App\Models;

use CodeIgniter\Model;

class CoachAvailabilityModel extends Model
{
    protected $table = 'coach_availabilities';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'coach_id', 'branch_id', 'day_of_week', 'start_time', 'end_time', 'status'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
}
