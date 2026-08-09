<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'user_id', 'template_code', 'title', 'message', 'channel',
        'data', 'is_read', 'read_at', 'action_url', 'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getUnreadByUser(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->where('deleted_at', null)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function getRecentByUser(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)
                    ->where('deleted_at', null)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function countUnreadByUser(int $userId): int
    {
        return (int) $this->where('user_id', $userId)
                          ->where('is_read', 0)
                          ->where('deleted_at', null)
                          ->countAllResults();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        return $this->where('id', $notificationId)
                    ->where('user_id', $userId)
                    ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
                    ->update();
    }

    public function markAllAsRead(int $userId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('is_read', 0)
                    ->set(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')])
                    ->update();
    }
}
