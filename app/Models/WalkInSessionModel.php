<?php

namespace App\Models;

use CodeIgniter\Model;

class WalkInSessionModel extends Model
{
    protected $table = 'walk_in_sessions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'booking_id', 'branch_id', 'player_id', 'customer_name',
        'customer_phone', 'customer_email', 'session_key', 'status', 'note',
        'checked_in_at', 'checked_out_at', 'created_by', 'updated_by',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->where('tenant_id', $tenantId)->where('deleted_at', null);
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (!empty($filters['date'])) {
            $builder->where('created_at >=', $filters['date'] . ' 00:00:00')
                ->where('created_at <=', $filters['date'] . ' 23:59:59');
        }
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query(
            'SELECT * FROM walk_in_sessions WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE',
            [$id, $tenantId]
        )->getRowArray();
        return $row ? (object) $row : null;
    }

    public function findBySessionKey(string $key, int $tenantId): ?object
    {
        return $this->where('session_key', $key)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }
}
