<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class CourtDevice extends Entity
{
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'last_ping_at'];
    protected $casts = [
        'id'          => 'int',
        'tenant_id'   => 'int',
        'branch_id'   => 'int',
        'court_id'    => '?int',
        'facility_id' => '?int',
        'is_active'   => 'bool',
        'config'      => 'json',
    ];

    public function getName(): string
    {
        $locale = service('request')->getLocale();
        return $locale === 'vi'
            ? (string) ($this->attributes['name_vi'] ?? '')
            : (string) ($this->attributes['name_en'] ?? $this->attributes['name_vi'] ?? '');
    }

    public function getDeviceIcon(): string
    {
        return [
            'light' => 'bi-lightbulb',
            'fan' => 'bi-wind',
            'camera' => 'bi-camera-video',
            'locker' => 'bi-lock',
            'speaker' => 'bi-speaker',
            'scoreboard' => 'bi-123',
            'gate' => 'bi-door-open',
            'ac' => 'bi-snow',
        ][$this->attributes['device_type'] ?? ''] ?? 'bi-cpu';
    }

    public function getStatusBadge(): string
    {
        $color = [
            'online' => 'success',
            'offline' => 'secondary',
            'error' => 'danger',
            'disabled' => 'dark',
        ][$this->attributes['status'] ?? 'offline'] ?? 'secondary';

        return '<span class="badge bg-' . $color . '">' . esc($this->attributes['status'] ?? 'offline') . '</span>';
    }
}
