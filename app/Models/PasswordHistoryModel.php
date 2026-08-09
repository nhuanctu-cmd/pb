<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordHistoryModel extends Model
{
    protected $table            = 'password_histories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['user_id', 'password', 'created_at'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'user_id'  => 'required|integer',
        'password' => 'required',
    ];

    /**
     * Lấy N hash mật khẩu gần nhất của user
     */
    public function getRecentHashes(int $userId, int $limit = 3): array
    {
        return array_column(
            $this->where('user_id', $userId)
                 ->orderBy('id', 'DESC')
                 ->limit($limit)
                 ->find(),
            'password'
        );
    }
}
