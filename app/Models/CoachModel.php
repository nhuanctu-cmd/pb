<?php

namespace App\Models;

use CodeIgniter\Model;

class CoachModel extends Model
{
    protected $table = 'coaches';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'branch_id', 'user_id', 'full_name', 'phone', 'email', 'bio', 'certifications', 'specialties', 'hourly_rate', 'status', 'created_by', 'updated_by'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->where('tenant_id', $tenantId)->where('deleted_at', null);
        if (!empty($filters['status'])) $builder->where('status', $filters['status']);
        if (!empty($filters['branch_id'])) $builder->where('branch_id', (int) $filters['branch_id']);
        return $builder->orderBy('full_name', 'ASC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }
}
