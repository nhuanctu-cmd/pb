<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Facility extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'             => 'int',
        'tenant_id'      => 'int',
        'total_courts'   => 'int',
        'total_branches' => 'int',
        'is_active'      => 'bool',
        'sort_order'     => 'int',
        'meta'           => 'json',
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

    public function getStatusBadge()
    {
        $badges = [
            'active'    => 'success',
            'inactive'  => 'secondary',
            'suspended' => 'danger',
        ];
        $color = $badges[$this->attributes['status']] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . lang('Facility.status_' . $this->attributes['status']) . '</span>';
    }

    public function getLogoUrl()
    {
        if (empty($this->attributes['logo'])) {
            return asset('images/default-facility-logo.png');
        }
        return base_url('uploads/' . $this->attributes['logo']);
    }

    public function getCoverUrl()
    {
        if (empty($this->attributes['cover_image'])) {
            return asset('images/default-facility-cover.jpg');
        }
        return base_url('uploads/' . $this->attributes['cover_image']);
    }
}
