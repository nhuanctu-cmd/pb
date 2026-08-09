<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ProductCategory extends Entity
{
    protected $attributes = [
        'id' => null,
        'tenant_id' => null,
        'name_vi' => null,
        'name_en' => null,
        'status' => 'active',
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'name_vi' => 'string',
        'name_en' => 'string',
        'status' => 'string',
    ];
}
