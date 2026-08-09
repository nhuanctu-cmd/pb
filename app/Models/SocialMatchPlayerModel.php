<?php

namespace App\Models;

use CodeIgniter\Model;

class SocialMatchPlayerModel extends Model
{
    protected $table = 'social_match_players';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = ['tenant_id', 'social_match_id', 'player_id', 'team_side', 'status'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByMatch(int $socialMatchId): array
    {
        return $this->select('social_match_players.*, players.full_name, players.rating_score')
            ->join('players', 'players.id = social_match_players.player_id', 'left')
            ->where('social_match_players.social_match_id', $socialMatchId)
            ->where('social_match_players.deleted_at', null)
            ->orderBy('social_match_players.team_side', 'ASC')
            ->findAll();
    }
}
