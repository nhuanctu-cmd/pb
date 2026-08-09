<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Invoice extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'branch_id' => null,
        'invoice_code' => null,
        'customer_type' => 'guest',
        'player_id' => null,
        'ref_type' => null,
        'ref_id' => null,
        'subtotal' => 0,
        'discount_amount' => 0,
        'total_amount' => 0,
        'paid_amount' => 0,
        'status' => 'unpaid',
        'note' => null,
        'created_by' => null,
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'branch_id' => 'integer',
        'invoice_code' => 'string',
        'customer_type' => 'string',
        'player_id' => 'integer',
        'ref_type' => 'string',
        'ref_id' => 'integer',
        'subtotal' => 'decimal',
        'discount_amount' => 'decimal',
        'total_amount' => 'decimal',
        'paid_amount' => 'decimal',
        'status' => 'string',
        'note' => 'string',
        'created_by' => 'integer',
    ];
}
