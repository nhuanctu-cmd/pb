<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Product extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'category_id' => null,
        'sku' => null,
        'name_vi' => null,
        'name_en' => null,
        'unit' => 'pcs',
        'cost_price' => 0,
        'sale_price' => 0,
        'image' => null,
        'status' => 'active',
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'category_id' => 'integer',
        'sku' => 'string',
        'name_vi' => 'string',
        'name_en' => 'string',
        'unit' => 'string',
        'cost_price' => 'decimal',
        'sale_price' => 'decimal',
        'image' => 'string',
        'status' => 'string',
    ];
}
