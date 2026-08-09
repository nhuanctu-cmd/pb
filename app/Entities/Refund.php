<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Refund extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'payment_id' => null,
        'invoice_id' => null,
        'amount' => 0,
        'reason' => null,
        'status' => 'pending',
        'processed_by' => null,
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'payment_id' => 'integer',
        'invoice_id' => 'integer',
        'amount' => 'float',
        'reason' => 'string',
        'status' => 'string',
        'processed_by' => 'integer',
    ];
}
