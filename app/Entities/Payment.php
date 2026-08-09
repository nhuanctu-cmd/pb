<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Payment extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'invoice_id' => null,
        'payment_code' => null,
        'method' => null,
        'amount' => 0,
        'transaction_ref' => null,
        'status' => 'pending',
        'idempotency_key' => null,
        'paid_at' => null,
        'created_by' => null,
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'invoice_id' => 'integer',
        'payment_code' => 'string',
        'method' => 'string',
        'amount' => 'float',
        'transaction_ref' => 'string',
        'status' => 'string',
        'idempotency_key' => 'string',
        'paid_at' => 'datetime',
        'created_by' => 'integer',
    ];
}
