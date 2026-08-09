<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class MembershipPackage extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'               => 'int',
        'tenant_id'        => 'int',
        'duration_days'    => 'int',
        'price'            => 'float',
        'discount_percent' => 'float',
        'booking_priority' => 'int',
    ];

    public function getName()
    {
        $locale = service('request')->getLocale();
        return $locale === 'vi' ? ($this->attributes['name_vi'] ?? '') : ($this->attributes['name_en'] ?? $this->attributes['name_vi']);
    }

    public function getPriceFormatted()
    {
        return number_format($this->attributes['price'], 0, ',', '.') . 'đ';
    }

    public function getStatusBadge()
    {
        $color = $this->attributes['status'] === 'active' ? 'success' : 'secondary';
        return '<span class="badge bg-' . $color . '">' . lang('App.' . $this->attributes['status']) . '</span>';
    }
}
