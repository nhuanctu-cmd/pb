<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Tenant extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'         => 'int',
        'is_active'  => 'boolean',
        'tenant_id'  => '?int',
        'branch_id'  => '?int',
    ];

    public function getLogoUrl(): ?string
    {
        if ($this->attributes['logo'] ?? null) {
            return base_url('uploads/' . $this->attributes['logo']);
        }
        return null;
    }

    public function getFullAddress(): string
    {
        $parts = [];
        if ($this->attributes['address'] ?? null) {
            $parts[] = $this->attributes['address'];
        }
        return implode(', ', $parts);
    }
}
