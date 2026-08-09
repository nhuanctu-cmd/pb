<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentBracketModel extends Model
{
    protected $table = 'tournament_brackets';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'tournament_id', 'category_id', 'match_id', 'parent_match_id',
        'next_match_id', 'bracket_position', 'round_no',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
}
