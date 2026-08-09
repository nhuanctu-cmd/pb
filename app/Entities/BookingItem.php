<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BookingItem extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'booking_id' => 'int',
        'court_id'   => 'int',
        'price'      => 'float',
    ];
}
