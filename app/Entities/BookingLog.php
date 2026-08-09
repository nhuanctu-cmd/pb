<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BookingLog extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at'];
    protected $casts   = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'booking_id' => 'int',
        'created_by' => '?int',
    ];
}
