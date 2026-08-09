<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentScheduleLockModel extends Model
{
    protected $table = 'tournament_schedule_locks';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $protectFields = true;
    protected $allowedFields = ['tenant_id', 'tournament_id', 'lock_type', 'ref_id', 'reason'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
}
