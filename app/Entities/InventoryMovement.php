<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class InventoryMovement extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'branch_id' => null,
        'product_id' => null,
        'movement_type' => null,
        'quantity' => 0,
        'before_qty' => 0,
        'after_qty' => 0,
        'ref_type' => null,
        'ref_id' => null,
        'note' => null,
        'created_by' => null,
        'created_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'branch_id' => 'integer',
        'product_id' => 'integer',
        'movement_type' => 'string',
        'quantity' => 'integer',
        'before_qty' => 'integer',
        'after_qty' => 'integer',
        'ref_type' => 'string',
        'ref_id' => 'integer',
        'note' => 'string',
        'created_by' => 'integer',
    ];
}
