<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentDrawVersionModel extends Model
{
    protected $table = 'tournament_draw_versions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id',
        'tournament_id',
        'category_id',
        'draw_policy_version_id',
        'draw_policy_hash',
        'draw_policy_code',
        'draw_signature',
        'draw_seed',
        'participant_count',
        'participant_snapshot',
        'draw_config',
        'status',
        'reason',
        'metadata',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';
}
