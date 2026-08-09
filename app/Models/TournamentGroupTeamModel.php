<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentGroupTeamModel extends Model
{
    protected $table = 'tournament_group_teams';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $protectFields = true;
    protected $allowedFields = ['tenant_id', 'group_id', 'team_id', 'seed_no'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
}
