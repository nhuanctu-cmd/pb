<?php

namespace App\Models;

use CodeIgniter\Model;

class CoachingSessionModel extends Model
{
    protected $table = 'coaching_sessions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'branch_id', 'coach_id', 'court_id', 'booking_id', 'title', 'session_type', 'session_date', 'start_time', 'end_time', 'capacity', 'price_per_player', 'status', 'notes', 'created_by', 'updated_by'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->select('coaching_sessions.*, coaches.full_name as coach_name, branches.name as branch_name')
            ->join('coaches', 'coaches.id = coaching_sessions.coach_id', 'left')
            ->join('branches', 'branches.id = coaching_sessions.branch_id', 'left')
            ->where('coaching_sessions.tenant_id', $tenantId)->where('coaching_sessions.deleted_at', null);
        if (isset($filters['session_date']) && $filters['session_date'] !== '') $builder->where('coaching_sessions.session_date', $filters['session_date']);
        if (!empty($filters['status'])) $builder->where('coaching_sessions.status', $filters['status']);
        return $builder->orderBy('coaching_sessions.session_date', 'ASC')->orderBy('coaching_sessions.start_time', 'ASC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query('SELECT * FROM coaching_sessions WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE', [$id, $tenantId])->getRowArray();
        return $row ? (object) $row : null;
    }
}
