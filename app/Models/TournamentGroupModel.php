<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentGroupModel extends Model
{
    protected $table = 'tournament_groups';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $protectFields = true;
    protected $allowedFields = ['tenant_id', 'tournament_id', 'category_id', 'group_name', 'sort_order'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
}
