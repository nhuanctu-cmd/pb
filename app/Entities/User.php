<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'last_login', 'birth_date'];
    protected $casts   = [
        'id'            => 'int',
        'tenant_id'     => '?int',
        'branch_id'     => '?int',
        'is_superadmin' => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function setPassword(string $password)
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->attributes['first_name'] ?? '') . ' ' . ($this->attributes['last_name'] ?? ''));
    }

    public function getAvatarUrl(): ?string
    {
        if ($this->attributes['avatar'] ?? null) {
            return base_url('uploads/avatars/' . $this->attributes['avatar']);
        }
        return null;
    }
}
