<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Court extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'            => 'int',
        'tenant_id'     => 'int',
        'branch_id'     => 'int',
        'court_type_id' => 'int',
        'floor'         => 'int',
        'area'          => 'float',
        'is_indoor'     => 'bool',
        'has_light'     => 'bool',
        'has_fan'       => 'bool',
        'has_camera'    => 'bool',
        'sort_order'    => 'int',
    ];

    public function getName()
    {
        $locale = service('request')->getLocale();
        return $locale === 'vi' ? $this->attributes['name_vi'] : ($this->attributes['name_en'] ?? $this->attributes['name_vi']);
    }

    public function getStatusBadge()
    {
        $badges = [
            'available'   => 'success',
            'occupied'    => 'warning',
            'maintenance' => 'danger',
            'inactive'    => 'secondary',
        ];
        $color = $badges[$this->attributes['status']] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . $this->attributes['status'] . '</span>';
    }

    public function getStatusIcon()
    {
        $icons = [
            'available'   => 'bi-check-circle-fill text-success',
            'occupied'    => 'bi-play-circle-fill text-warning',
            'maintenance' => 'bi-exclamation-triangle-fill text-danger',
            'inactive'    => 'bi-x-circle-fill text-secondary',
        ];
        return $icons[$this->attributes['status']] ?? 'bi-question-circle-fill';
    }
}
