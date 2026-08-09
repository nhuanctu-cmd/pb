<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class PosOrder extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'branch_id' => null,
        'player_id' => null,
        'booking_id' => null,
        'order_code' => null,
        'total_amount' => 0,
        'discount_amount' => 0,
        'paid_amount' => 0,
        'payment_status' => 'pending',
        'status' => 'pending',
        'note' => null,
        'created_by' => null,
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'branch_id' => 'integer',
        'player_id' => 'integer',
        'booking_id' => 'integer',
        'order_code' => 'string',
        'total_amount' => 'decimal',
        'discount_amount' => 'decimal',
        'paid_amount' => 'decimal',
        'payment_status' => 'string',
        'status' => 'string',
        'note' => 'string',
        'created_by' => 'integer',
    ];
}
