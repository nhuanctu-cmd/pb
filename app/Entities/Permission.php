<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Permission extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'         => 'int',
        'parent_id'  => '?int',
        'is_active'  => 'boolean',
    ];
}
