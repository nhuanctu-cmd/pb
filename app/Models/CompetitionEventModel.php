<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetitionEventModel extends Model
{
    protected $table = 'competition_events';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'branch_id', 'tournament_id', 'name', 'format', 'entry_fee', 'scoring_rules', 'start_date', 'end_date', 'status', 'created_by', 'updated_by'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTenant(int $tenantId): array
    {
        return $this->select('competition_events.*, branches.name as branch_name')->join('branches', 'branches.id = competition_events.branch_id AND branches.tenant_id = competition_events.tenant_id', 'left')->where('competition_events.tenant_id', $tenantId)->where('competition_events.deleted_at', null)->orderBy('competition_events.created_at', 'DESC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query('SELECT * FROM competition_events WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE', [$id, $tenantId])->getRowArray();
        return $row ? (object) $row : null;
    }
}
