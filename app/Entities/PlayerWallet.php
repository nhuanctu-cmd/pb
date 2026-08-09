<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PlayerWallet extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'        => 'int',
        'tenant_id' => 'int',
        'player_id' => 'int',
        'balance'   => 'float',
    ];

    public function getBalanceFormatted()
    {
        return number_format($this->attributes['balance'], 0, ',', '.') . 'đ';
    }
}
