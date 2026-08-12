<?php

namespace App\Models;

use CodeIgniter\Model;

class MatchParticipantModel extends Model
{
    protected $table = 'match_participants';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'match_id', 'player_id', 'side', 'participant_role', 'result', 'score', 'sort_order', 'metadata',
    ];
}
