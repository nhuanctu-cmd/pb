<?php

namespace App\Models;

use CodeIgniter\Model;

class PlatformClubModel extends Model
{
    protected $table = 'platform_clubs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'public_id', 'code', 'name', 'slug', 'province', 'city', 'logo_url', 'website_url',
        'status', 'verification_status', 'metadata',
    ];
}
