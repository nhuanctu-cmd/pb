<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'branch_id', 'user_id', 'action', 'module',
        'table_name', 'record_id', 'old_values', 'new_values',
        'ip_address', 'user_agent', 'description',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';

    protected $validationRules = [
        'action'     => 'required|max_length[50]',
        'module'     => 'required|max_length[100]',
        'table_name' => 'permit_empty|max_length[100]',
    ];

    public function getByUser(int $userId, int $limit = 50)
    {
        return $this->where('user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function getByModule(string $module, int $limit = 50)
    {
        return $this->where('module', $module)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    public function getByDateRange(string $startDate, string $endDate, int $limit = 100)
    {
        return $this->where('created_at >=', $startDate)
                    ->where('created_at <=', $endDate)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }
}
