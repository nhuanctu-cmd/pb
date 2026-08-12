<?php

namespace App\Models;

use CodeIgniter\Model;

class RatingLedgerModel extends Model
{
    protected $table = 'rating_ledgers';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'rating_provider_id', 'tenant_id', 'player_id', 'match_id', 'side', 'outcome',
        'rating_before', 'rating_after', 'rating_delta', 'reliability_before',
        'reliability_after', 'calculation_version', 'idempotency_key', 'metadata', 'created_at',
    ];
}
