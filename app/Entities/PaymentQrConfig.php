<?php

namespace App\Entities;

use CodeIgniter\Entity;

class PaymentQrConfig extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'bank_name' => null,
        'bank_account' => null,
        'account_name' => null,
        'qr_template' => null,
        'status' => 'active',
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'bank_name' => 'string',
        'bank_account' => 'string',
        'account_name' => 'string',
        'qr_template' => 'string',
        'status' => 'string',
    ];
}
