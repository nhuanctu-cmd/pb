<?php

namespace App\Models;

use CodeIgniter\Model;

class RatingTransactionModel extends Model
{
    protected $table = 'rating_transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'player_id', 'provider_id', 'discipline_id', 'match_id', 'match_result_version_id', 'rating_policy_version_id', 'transaction_type',
        'before_rating', 'after_rating', 'rating_delta', 'expected_performance', 'actual_performance', 'reliability_before', 'reliability_after', 'match_weight',
        'reason', 'status', 'idempotency_key', 'processed_at', 'metadata', 'provenance_id',
    ];
    protected $useTimestamps = false;
}
