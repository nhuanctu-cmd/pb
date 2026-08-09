<?php

namespace App\Models;

use CodeIgniter\Model;

class CoachingSessionPlayerModel extends Model
{
    protected $table = 'coaching_session_players';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'session_id', 'player_id', 'invoice_id', 'status', 'requested_at', 'approved_at', 'created_by'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findByPlayer(int $sessionId, int $playerId, int $tenantId): ?object
    {
        return $this->where('session_id', $sessionId)->where('player_id', $playerId)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query('SELECT * FROM coaching_session_players WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE', [$id, $tenantId])->getRowArray();
        return $row ? (object) $row : null;
    }

    public function approvedCount(int $sessionId, int $tenantId): int
    {
        return (int) $this->where('session_id', $sessionId)->where('tenant_id', $tenantId)->where('status', 'approved')->where('deleted_at', null)->countAllResults();
    }

    public function getBySession(int $sessionId, int $tenantId): array
    {
        return $this->select('coaching_session_players.*, players.full_name, players.rating_score, invoices.status as invoice_status, invoices.total_amount as invoice_total_amount, invoices.paid_amount as invoice_paid_amount')->join('players', 'players.id = coaching_session_players.player_id', 'left')->join('invoices', 'invoices.id = coaching_session_players.invoice_id AND invoices.tenant_id = coaching_session_players.tenant_id', 'left')->where('coaching_session_players.session_id', $sessionId)->where('coaching_session_players.tenant_id', $tenantId)->where('coaching_session_players.deleted_at', null)->orderBy('coaching_session_players.created_at', 'ASC')->findAll();
    }
}
