<?php

namespace App\Models;

use CodeIgniter\Model;

class OpenPlayRotationRoundModel extends Model
{
    protected $table = 'open_play_rotation_rounds';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'session_id', 'round_no', 'start_time', 'end_time', 'status', 'created_by', 'updated_by'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query('SELECT * FROM open_play_rotation_rounds WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE', [$id, $tenantId])->getRowArray();
        return $row ? (object) $row : null;
    }

    public function getBySession(int $sessionId, int $tenantId): array
    {
        return $this->where('session_id', $sessionId)->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('round_no', 'ASC')->findAll();
    }
}
