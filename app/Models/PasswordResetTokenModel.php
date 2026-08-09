<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetTokenModel extends Model
{
    protected $table            = 'password_reset_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['email', 'token', 'expires_at', 'used_at', 'created_ip', 'created_at'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'email'      => 'required|valid_email',
        'token'      => 'required|max_length[128]',
        'expires_at' => 'required|valid_date',
    ];

    /**
     * Tìm token còn hiệu lực (chưa dùng, chưa hết hạn)
     */
    public function findValidToken(string $token): ?array
    {
        $row = $this->where('token', $token)
                    ->where('used_at', null)
                    ->where('expires_at >=', date('Y-m-d H:i:s'))
                    ->first();

        return $row ?: null;
    }

    /**
     * Vô hiệu mọi token cũ của 1 email
     */
    public function invalidateAllForEmail(string $email): void
    {
        $this->where('email', $email)
             ->where('used_at', null)
             ->set('used_at', date('Y-m-d H:i:s'))
             ->update();
    }
}
