<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Player extends Entity
{
    protected $datamap = [];
    protected $dates   = ['birthday', 'created_at', 'updated_at', 'deleted_at'];
    protected $casts   = [
        'id'           => 'int',
        'tenant_id'    => 'int',
        'user_id'      => '?int',
        'rating_score' => 'float',
        'home_branch_id' => '?int',
        'level_id' => '?int',
        'checkin_streak' => 'int',
        'best_checkin_streak' => 'int',
        'mvp_count' => 'int',
    ];

    public function getLevelBadge()
    {
        $badges = [
            'beginner'     => 'secondary',
            'intermediate' => 'info',
            'advanced'     => 'warning',
            'pro'          => 'danger',
        ];
        $color = $badges[$this->attributes['level']] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . lang('App.level_' . $this->attributes['level']) . '</span>';
    }

    public function getStatusBadge()
    {
        $badges = [
            'active'   => 'success',
            'inactive' => 'secondary',
            'banned'   => 'danger',
        ];
        $color = $badges[$this->attributes['status']] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . lang('App.' . $this->attributes['status']) . '</span>';
    }

    public function getGenderText()
    {
        $genders = [
            'male'   => lang('App.male'),
            'female' => lang('App.female'),
            'other'  => lang('App.other'),
        ];
        return $genders[$this->attributes['gender']] ?? '-';
    }

    public function getAvatarUrl()
    {
        return $this->attributes['avatar']
            ? base_url($this->attributes['avatar'])
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->attributes['full_name']) . '&color=7F9CF5&background=EBF4FF';
    }
}
