<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CourtStatus extends Entity
{
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts = [
        'id'          => 'int',
        'tenant_id'   => 'int',
        'is_bookable' => 'bool',
        'is_active'   => 'bool',
        'sort_order'  => 'int',
    ];

    public function getName(): string
    {
        $locale = service('request')->getLocale();
        return $locale === 'vi'
            ? (string) ($this->attributes['name_vi'] ?? '')
            : (string) ($this->attributes['name_en'] ?? $this->attributes['name_vi'] ?? '');
    }
}
