<?php

namespace App\Models;

use CodeIgniter\Model;

class RankingPointLedgerModel extends Model
{
    protected $table = 'ranking_point_ledgers';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'authority_id', 'policy_id', 'tenant_id', 'player_id', 'match_id', 'points',
        'reason', 'idempotency_key', 'metadata', 'created_at', 'event_id', 'placement', 'policy_version_id', 'provenance_id',
    ];
}
