<?php

namespace App\Models;

use CodeIgniter\Model;

class RankingSnapshotModel extends Model
{
    protected $table = 'ranking_snapshots';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'authority_id', 'policy_id', 'tenant_id', 'player_id', 'snapshot_date',
        'rank_position', 'points', 'match_count', 'metadata', 'created_at',
    ];
}
