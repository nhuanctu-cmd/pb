<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\User::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'username', 'email', 'password',
        'first_name', 'last_name', 'phone', 'avatar', 'gender',
        'birth_date', 'last_login', 'last_ip',
        'is_superadmin', 'is_active', 'status',
        'created_by', 'updated_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'username'  => 'required|is_unique[users.username,id,{id}]|min_length[3]|max_length[100]|alpha_dash',
        'email'     => 'required|is_unique[users.email,id,{id}]|valid_email|max_length[255]',
        'password'  => 'required|min_length[6]',
        'first_name'=> 'permit_empty|max_length[100]',
        'last_name' => 'permit_empty|max_length[100]',
        'phone'     => 'permit_empty|max_length[50]',
        'gender'    => 'permit_empty|in_list[male,female,other]',
        'status'    => 'required|in_list[active,inactive,suspended,banned]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword'];
    protected $afterInsert    = ['logAudit'];
    protected $beforeUpdate   = ['hashPassword'];
    protected $afterUpdate    = ['logAudit'];
    protected $afterDelete    = ['logAudit'];

    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }

    public function logAudit(array $data)
    {
        if (function_exists('log_audit')) {
            log_audit($data);
        }
        return $data;
    }

    public function findByEmail(string $email)
    {
        return $this->where('email', $email)
                    ->where('deleted_at', null)
                    ->first();
    }

    public function findByUsername(string $username)
    {
        return $this->where('username', $username)
                    ->where('deleted_at', null)
                    ->first();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('deleted_at', null)
                    ->findAll();
    }

    public function getByBranch(int $branchId)
    {
        return $this->where('branch_id', $branchId)
                    ->where('deleted_at', null)
                    ->findAll();
    }

    public function findForTenant(int $userId, int $tenantId): ?object
    {
        return $this->where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }
}
