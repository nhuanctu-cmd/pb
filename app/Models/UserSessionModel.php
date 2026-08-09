<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSessionModel extends Model
{
    protected $table            = 'user_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'session_id', 'ip_address', 'user_agent', 'last_activity', 'created_at'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'user_id'    => 'required|integer',
        'session_id' => 'required|max_length[128]',
    ];

    /**
     * Đăng ký/cập nhật phiên đăng nhập
     */
    public function track(int $userId, string $sessionId, ?string $ip, ?string $userAgent): void
    {
        $now  = date('Y-m-d H:i:s');
        $existing = $this->where('session_id', $sessionId)->first();

        if ($existing) {
            $this->update($existing['id'], ['last_activity' => $now]);
            return;
        }

        $this->insert([
            'user_id'       => $userId,
            'session_id'    => $sessionId,
            'ip_address'    => $ip,
            'user_agent'    => $userAgent,
            'last_activity' => $now,
            'created_at'    => $now,
        ]);
    }

    public function removeBySessionId(string $sessionId): void
    {
        $this->where('session_id', $sessionId)->delete();
    }

    public function getActiveByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('last_activity', 'DESC')
                    ->findAll();
    }
}
