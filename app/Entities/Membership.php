<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Membership extends Entity
{
    protected $datamap = [];
    protected $dates   = ['start_date', 'end_date', 'created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'player_id'  => 'int',
        'package_id' => 'int',
    ];

    public function getStatusBadge()
    {
        $badges = [
            'active'    => 'success',
            'expired'   => 'danger',
            'cancelled' => 'warning',
        ];
        $color = $badges[$this->attributes['status']] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . lang('App.membership_' . $this->attributes['status']) . '</span>';
    }

    public function isExpired(): bool
    {
        return strtotime($this->attributes['end_date']) < time();
    }

    public function getRemainingDays(): int
    {
        $now = time();
        $end = strtotime($this->attributes['end_date']);
        if ($end <= $now) return 0;
        return (int) ceil(($end - $now) / 86400);
    }
}
