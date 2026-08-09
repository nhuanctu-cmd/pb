<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Booking extends Entity
{
    protected $datamap = [];
    protected $dates   = [
        'created_at', 'updated_at', 'deleted_at',
        'cancelled_at', 'checked_in_at', 'completed_at', 'expires_at',
    ];
    protected $casts   = [
        'id'               => 'int',
        'tenant_id'        => 'int',
        'branch_id'        => 'int',
        'player_id'        => '?int',
        'duration_minutes' => 'int',
        'total_amount'     => 'float',
        'deposit_amount'   => 'float',
        'paid_amount'      => 'float',
        'created_by'       => '?int',
        'updated_by'       => '?int',
    ];

    public function getStatusBadge(): string
    {
        $badges = [
            'pending'    => 'warning',
            'reserved'   => 'info',
            'paid'       => 'primary',
            'checked_in' => 'success',
            'completed'  => 'secondary',
            'cancelled'  => 'danger',
            'refunded'   => 'dark',
            'no_show'    => 'danger',
        ];
        $color = $badges[$this->attributes['status']] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . lang('App.status_' . $this->attributes['status']) . '</span>';
    }

    public function getPaymentBadge(): string
    {
        $badges = [
            'unpaid'   => 'danger',
            'partial'  => 'warning',
            'paid'     => 'success',
            'refunded' => 'dark',
        ];
        $color = $badges[$this->attributes['payment_status']] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . lang('App.payment_' . $this->attributes['payment_status']) . '</span>';
    }

    public function getTotalAmountFormatted(): string
    {
        return number_format($this->attributes['total_amount'] ?? 0, 0, ',', '.') . '₫';
    }

    public function getDepositAmountFormatted(): string
    {
        return number_format($this->attributes['deposit_amount'] ?? 0, 0, ',', '.') . '₫';
    }

    public function getPaidAmountFormatted(): string
    {
        return number_format($this->attributes['paid_amount'] ?? 0, 0, ',', '.') . '₫';
    }

    public function getTimeRange(): string
    {
        $start = substr($this->attributes['start_time'] ?? '', 0, 5);
        $end = substr($this->attributes['end_time'] ?? '', 0, 5);
        return $start . ' - ' . $end;
    }
}
