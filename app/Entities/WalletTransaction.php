<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class WalletTransaction extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'             => 'int',
        'tenant_id'      => 'int',
        'player_id'      => 'int',
        'wallet_id'      => 'int',
        'amount'         => 'float',
        'balance_before' => 'float',
        'balance_after'  => 'float',
        'ref_id'         => '?int',
    ];

    public function getAmountFormatted()
    {
        $prefix = in_array($this->attributes['type'], ['topup', 'refund']) ? '+' : '-';
        return $prefix . number_format(abs($this->attributes['amount']), 0, ',', '.') . 'đ';
    }

    public function getTypeBadge()
    {
        $badges = [
            'topup'   => 'success',
            'payment' => 'danger',
            'refund'  => 'info',
            'adjust'  => 'warning',
        ];
        $color = $badges[$this->attributes['type']] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . lang('App.wallet_' . $this->attributes['type']) . '</span>';
    }
}
