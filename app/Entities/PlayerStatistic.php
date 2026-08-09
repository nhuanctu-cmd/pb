<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PlayerStatistic extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'             => 'int',
        'tenant_id'      => 'int',
        'player_id'      => 'int',
        'elo_rating'     => 'int',
        'ranking_points' => 'int',
        'total_matches'  => 'int',
        'total_wins'     => 'int',
        'total_losses'   => 'int',
        'total_bookings' => 'int',
        'checkin_count'  => 'int',
        'no_show_count'  => 'int',
        'win_rate'       => 'float',
        'current_streak' => 'int',
        'best_streak'    => 'int',
        'mvp_count'      => 'int',
        'achievements_count' => 'int',
    ];

    public function getWinRateFormatted()
    {
        return number_format($this->attributes['win_rate'], 1) . '%';
    }
}
