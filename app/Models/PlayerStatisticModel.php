<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerStatisticModel extends Model
{
    protected $table            = 'player_statistics';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\PlayerStatistic::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'player_id', 'elo_rating', 'ranking_points',
        'total_matches', 'total_wins', 'total_losses', 'total_bookings',
        'checkin_count', 'no_show_count', 'win_rate', 'current_streak',
        'best_streak', 'mvp_count', 'achievements_count',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'tenant_id'     => 'required|integer',
        'player_id'     => 'required|integer',
        'total_matches' => 'permit_empty|integer',
        'total_wins'    => 'permit_empty|integer',
        'total_losses'  => 'permit_empty|integer',
        'win_rate'      => 'permit_empty|decimal',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function getByPlayer(int $playerId, int $tenantId)
    {
        return $this->where('player_id', $playerId)
                    ->where('tenant_id', $tenantId)
                    ->first();
    }

    public function findOrCreate(int $playerId, int $tenantId)
    {
        $stats = $this->getByPlayer($playerId, $tenantId);
        if (!$stats) {
            $this->insert([
                'tenant_id'      => $tenantId,
                'player_id'      => $playerId,
                'elo_rating'     => 1000,
                'ranking_points' => 200,
                'total_matches'  => 0,
                'total_wins'     => 0,
                'total_losses'   => 0,
                'total_bookings' => 0,
                'checkin_count'  => 0,
                'no_show_count'  => 0,
                'win_rate'       => 0,
                'current_streak' => 0,
                'best_streak'    => 0,
                'mvp_count'      => 0,
                'achievements_count' => 0,
            ]);
            $stats = $this->getByPlayer($playerId, $tenantId);
        }
        return $stats;
    }
}
