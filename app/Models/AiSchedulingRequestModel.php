<?php

namespace App\Models;

use CodeIgniter\Model;

class AiSchedulingRequestModel extends Model
{
    protected $table = 'ai_scheduling_requests';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['tenant_id', 'branch_id', 'tournament_id', 'requested_by', 'date_from', 'date_to', 'match_minutes', 'rest_minutes', 'provider', 'constraints_json', 'status', 'result_json', 'error_message'];
    protected $useTimestamps = true;

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query('SELECT * FROM ai_scheduling_requests WHERE id = ? AND tenant_id = ? LIMIT 1 FOR UPDATE', [$id, $tenantId])->getRowArray();
        return $row ? (object) $row : null;
    }
}
