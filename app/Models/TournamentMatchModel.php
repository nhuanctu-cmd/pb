<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentMatchModel extends Model
{
    protected $table = 'tournament_matches';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'tournament_id', 'category_id', 'group_id', 'round_name', 'match_no',
        'court_id', 'scheduled_date', 'start_time', 'end_time', 'team_a_id', 'team_b_id',
        'winner_team_id', 'status', 'is_locked',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    public function findForTenant(int $matchId, int $tenantId): ?object
    {
        return $this->where('id', $matchId)->where('tenant_id', $tenantId)->first();
    }
}
