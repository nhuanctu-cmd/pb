<?php

namespace App\Models;

use CodeIgniter\Model;

class OpenPlaySessionModel extends Model
{
    protected $table = 'open_play_sessions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'branch_id', 'host_player_id', 'title', 'session_date', 'start_time', 'end_time', 'capacity', 'min_level', 'max_level', 'price_per_player', 'visibility', 'status', 'notes', 'created_by', 'updated_by'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->where('tenant_id', $tenantId)->where('deleted_at', null);
        foreach (['status', 'branch_id', 'session_date'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $builder->where($field, $filters[$field]);
            }
        }
        return $builder->orderBy('session_date', 'ASC')->orderBy('start_time', 'ASC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query('SELECT * FROM open_play_sessions WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE', [$id, $tenantId])->getRowArray();
        return $row ? (object) $row : null;
    }
}
