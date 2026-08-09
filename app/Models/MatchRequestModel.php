<?php

namespace App\Models;

use CodeIgniter\Model;

class MatchRequestModel extends Model
{
    protected $table = 'match_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'player_id', 'branch_id', 'preferred_date', 'preferred_start_time',
        'preferred_end_time', 'level_from', 'level_to', 'match_type', 'need_players', 'status',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';
    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'player_id' => 'required|integer',
        'branch_id' => 'required|integer',
        'preferred_date' => 'required|valid_date',
        'preferred_start_time' => 'required',
        'preferred_end_time' => 'required',
        'match_type' => 'required|in_list[single,double,mixed]',
        'status' => 'permit_empty|in_list[open,matched,cancelled,expired]',
    ];

    public function getOpen(int $tenantId, array $filters = []): array
    {
        $builder = $this->select('match_requests.*, players.full_name, players.rating_score, branches.name as branch_name')
            ->join('players', 'players.id = match_requests.player_id', 'left')
            ->join('branches', 'branches.id = match_requests.branch_id', 'left')
            ->where('match_requests.tenant_id', $tenantId)
            ->where('match_requests.deleted_at', null);

        if (! empty($filters['status'])) {
            $builder->where('match_requests.status', $filters['status']);
        }

        if (! empty($filters['branch_id'])) {
            $builder->where('match_requests.branch_id', $filters['branch_id']);
        }

        if (! empty($filters['preferred_date'])) {
            $builder->where('match_requests.preferred_date', $filters['preferred_date']);
        }

        return $builder->orderBy('match_requests.preferred_date', 'ASC')
            ->orderBy('match_requests.preferred_start_time', 'ASC')
            ->findAll();
    }

    public function findForTenant(int $requestId, int $tenantId): ?object
    {
        return $this->where('id', $requestId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }

    public function findForUpdate(int $requestId, int $tenantId): ?object
    {
        $row = $this->db->query(
            'SELECT * FROM match_requests WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE',
            [$requestId, $tenantId]
        )->getRowArray();
        return $row ? (object) $row : null;
    }
}
