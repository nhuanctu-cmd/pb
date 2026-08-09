<?php

namespace App\Models;

use CodeIgniter\Model;

class OpenPlaySessionPlayerModel extends Model
{
    protected $table = 'open_play_session_players';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'session_id', 'player_id', 'status', 'requested_at', 'approved_at', 'created_by', 'updated_by'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findByPlayer(int $sessionId, int $playerId, int $tenantId): ?object
    {
        return $this->where('session_id', $sessionId)->where('player_id', $playerId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query('SELECT * FROM open_play_session_players WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE', [$id, $tenantId])->getRowArray();
        return $row ? (object) $row : null;
    }

    public function approvedCount(int $sessionId, int $tenantId): int
    {
        return (int) $this->where('session_id', $sessionId)->where('tenant_id', $tenantId)->where('status', 'approved')->where('deleted_at', null)->countAllResults();
    }

    public function getBySession(int $sessionId, int $tenantId): array
    {
        return $this->where('session_id', $sessionId)->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('created_at', 'ASC')->findAll();
    }
}
