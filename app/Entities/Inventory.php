<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Inventory extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'branch_id' => null,
        'product_id' => null,
        'quantity' => 0,
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'branch_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
    ];
}
