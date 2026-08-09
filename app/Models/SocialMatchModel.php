<?php

namespace App\Models;

use CodeIgniter\Model;

class SocialMatchModel extends Model
{
    protected $table = 'social_matches';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'branch_id', 'booking_id', 'match_request_id', 'match_date', 'start_time', 'end_time', 'status',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->select('social_matches.*, branches.name as branch_name')
            ->join('branches', 'branches.id = social_matches.branch_id', 'left')
            ->where('social_matches.tenant_id', $tenantId)
            ->where('social_matches.deleted_at', null);

        if (! empty($filters['status'])) {
            $builder->where('social_matches.status', $filters['status']);
        }

        return $builder->orderBy('social_matches.match_date', 'DESC')
            ->orderBy('social_matches.start_time', 'ASC')
            ->findAll();
    }
}
