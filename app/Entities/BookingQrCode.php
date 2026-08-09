<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class BookingQrCode extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'expired_at', 'used_at'];
    protected $casts   = [
        'id'         => 'int',
        'tenant_id'  => 'int',
        'booking_id' => 'int',
    ];
}
