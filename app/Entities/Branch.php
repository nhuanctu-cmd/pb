<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Branch extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'is_main'    => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function getName(): string
    {
        return (string) ($this->attributes['name'] ?? '');
    }
}
