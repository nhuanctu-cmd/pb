<?php

namespace App\Entities;

use CodeIgniter\Entity;

class PosOrderItem extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'order_id' => null,
        'product_id' => null,
        'quantity' => 0,
        'price' => 0,
        'total' => 0,
        'created_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'price' => 'decimal',
        'total' => 'decimal',
    ];
}
