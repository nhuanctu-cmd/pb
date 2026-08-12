<?php

namespace App\Models;

use CodeIgniter\Model;

class RatingProviderModel extends Model
{
    protected $table = 'rating_providers';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'name', 'provider_type', 'status', 'config'];
}
