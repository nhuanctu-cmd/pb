<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationTemplateModel extends Model
{
    protected $table            = 'notification_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'code', 'channel', 'locale', 'subject', 'body', 'variables',
        'is_active', 'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getByCode(string $code, string $channel = 'email', string $locale = 'vi'): ?object
    {
        return $this->where('code', $code)
                    ->where('channel', $channel)
                    ->where('locale', $locale)
                    ->where('is_active', 1)
                    ->where('deleted_at', null)
                    ->first();
    }

    public function getAllActive(): array
    {
        return $this->where('is_active', 1)
                    ->where('deleted_at', null)
                    ->orderBy('code')
                    ->orderBy('channel')
                    ->findAll();
    }
}
