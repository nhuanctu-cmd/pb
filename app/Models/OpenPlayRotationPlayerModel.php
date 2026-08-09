<?php

namespace App\Models;

use CodeIgniter\Model;

class OpenPlayRotationPlayerModel extends Model
{
    protected $table = 'open_play_rotation_players';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'round_id', 'player_id', 'team_side', 'partner_player_id', 'opponent_player_ids'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByRound(int $roundId, int $tenantId): array
    {
        return $this->where('round_id', $roundId)->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('team_side', 'ASC')->orderBy('player_id', 'ASC')->findAll();
    }
}
