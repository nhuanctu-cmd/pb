<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginAttemptModel extends Model
{
    protected $table            = 'login_attempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['email', 'ip_address', 'user_agent', 'success', 'attempted_at'];

    protected $useTimestamps = false;

    protected $validationRules = [
        'email'        => 'required|valid_email|max_length[255]',
        'success'      => 'required|in_list[0,1]',
        'attempted_at' => 'required|valid_date',
    ];
    protected $skipValidation = false;
}
