<?php

namespace App\Models;

use CodeIgniter\Model;

class PlatformClubAliasModel extends Model
{
    protected $table = 'platform_club_aliases';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = ['platform_club_id', 'tenant_id', 'club_id', 'status', 'linked_by', 'verified_at'];
}
