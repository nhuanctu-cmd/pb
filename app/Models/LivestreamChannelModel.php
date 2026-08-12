<?php

namespace App\Models;

use CodeIgniter\Model;

class LivestreamChannelModel extends Model
{
    protected $table = 'livestream_channels';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'tenant_id', 'branch_id', 'tournament_id', 'name', 'provider', 'stream_url', 'embed_url',
        'status', 'scheduled_at', 'started_at', 'ended_at', 'created_by', 'updated_by',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function allForTenant(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)->where('deleted_at', null)
            ->orderBy('scheduled_at', 'DESC')->orderBy('id', 'DESC')->findAll(100);
    }

    public function publicForTenant(int $tenantId): array
    {
        return $this->where('tenant_id', $tenantId)->whereIn('status', ['scheduled', 'live'])
            ->where('deleted_at', null)->orderBy('status', 'DESC')->orderBy('scheduled_at', 'ASC')->findAll(50);
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query(
            'SELECT * FROM livestream_channels WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE',
            [$id, $tenantId]
        )->getRowArray();
        return $row ? (object) $row : null;
    }
}
