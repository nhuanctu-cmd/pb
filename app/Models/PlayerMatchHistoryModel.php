<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerMatchHistoryModel extends Model
{
    protected $table = 'player_match_history';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'player_id', 'opponent_player_id', 'branch_id',
        'facility_id', 'tournament_id', 'match_date', 'result', 'score',
        'rating_before', 'rating_after', 'rating_delta', 'is_mvp',
        'notes', 'created_by',
    ];
    protected $useTimestamps = true;

    public function getByPlayer(int $playerId, int $limit = 30): array
    {
        return $this->select('player_match_history.*, opponents.full_name as opponent_name, branches.name as branch_name')
            ->join('players opponents', 'opponents.id = player_match_history.opponent_player_id', 'left')
            ->join('branches', 'branches.id = player_match_history.branch_id', 'left')
            ->where('player_match_history.player_id', $playerId)
            ->orderBy('player_match_history.match_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
