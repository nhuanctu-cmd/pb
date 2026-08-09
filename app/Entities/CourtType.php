<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CourtType extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'               => 'int',
        'tenant_id'        => 'int',
        'default_capacity' => 'int',
    ];

    public function getName()
    {
        $locale = service('request')->getLocale();
        return $locale === 'vi' ? $this->attributes['name_vi'] : ($this->attributes['name_en'] ?? $this->attributes['name_vi']);
    }

    public function getDescription()
    {
        $locale = service('request')->getLocale();
        return $locale === 'vi' ? $this->attributes['description_vi'] : ($this->attributes['description_en'] ?? $this->attributes['description_vi']);
    }
}
