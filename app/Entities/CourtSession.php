<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CourtSession extends Entity
{
    protected $dates = ['created_at', 'updated_at', 'start_time', 'expected_end_time', 'actual_end_time'];
    protected $casts = [
        'id'               => 'int',
        'tenant_id'        => 'int',
        'branch_id'        => 'int',
        'court_id'         => 'int',
        'booking_id'       => '?int',
        'player_count'     => 'int',
        'player_names'     => 'json',
        'is_overtime'      => 'bool',
        'overtime_minutes' => 'int',
        'delay_minutes'    => 'int',
        'checked_in_by'    => '?int',
    ];

    public function getRemainingMinutes(): int
    {
        $remaining = strtotime((string) $this->attributes['expected_end_time']) - time();
        return max(0, (int) floor($remaining / 60));
    }

    public function isOvertime(): bool
    {
        return empty($this->attributes['actual_end_time'])
            && strtotime((string) $this->attributes['expected_end_time']) < time();
    }
}
