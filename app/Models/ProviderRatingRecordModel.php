<?php

namespace App\Models;

use CodeIgniter\Model;

class ProviderRatingRecordModel extends Model
{
    protected $table = 'provider_rating_records';
    protected $returnType = 'object';
    protected $useTimestamps = false;
    protected $allowedFields = ['player_id', 'provider_id', 'discipline', 'rating_value', 'rating_label', 'external_record_id', 'observed_at', 'synced_at', 'payload', 'created_at'];
}
