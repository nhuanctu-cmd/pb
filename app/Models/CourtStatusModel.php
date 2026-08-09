<?php

namespace App\Models;

use CodeIgniter\Model;

class CourtStatusModel extends Model
{
    protected $table            = 'court_statuses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\CourtStatus::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'code', 'name_vi', 'name_en', 'color', 'icon',
        'is_bookable', 'is_active', 'sort_order', 'created_by', 'updated_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getByTenant(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    public function getActive(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    public function getBookable(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->where('is_bookable', 1)
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    public function getByCode(string $code, int $tenantId)
    {
        return $this->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->where('deleted_at', null)
            ->first();
    }
}
