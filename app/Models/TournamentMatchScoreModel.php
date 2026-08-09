<?php

namespace App\Models;

use CodeIgniter\Model;

class TournamentMatchScoreModel extends Model
{
    protected $table = 'tournament_match_scores';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'match_id', 'set_no', 'team_a_score', 'team_b_score', 'winner_team_id',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getByMatch(int $matchId): array
    {
        return $this->where('match_id', $matchId)->orderBy('set_no', 'ASC')->findAll();
    }
}
