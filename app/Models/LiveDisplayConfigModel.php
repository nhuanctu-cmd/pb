<?php

namespace App\Models;

use CodeIgniter\Model;

class LiveDisplayConfigModel extends Model
{
    protected $table = 'live_display_configs';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'tournament_id', 'display_name', 'mode', 'show_sponsor',
        'show_next_matches', 'refresh_seconds', 'status',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
