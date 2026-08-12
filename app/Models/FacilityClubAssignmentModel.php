<?php

namespace App\Models;

use CodeIgniter\Model;

class FacilityClubAssignmentModel extends Model
{
    protected $table = 'facility_club_assignments';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['tenant_id', 'facility_id', 'club_id', 'status', 'is_primary', 'notes', 'created_by', 'updated_by'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getForFacility(int $facilityId, int $tenantId, bool $activeOnly = true): array
    {
        $builder = $this->select('facility_club_assignments.*, clubs.name_vi, clubs.name_en, clubs.logo, clubs.status AS club_status')
            ->join('clubs', 'clubs.id = facility_club_assignments.club_id AND clubs.tenant_id = facility_club_assignments.tenant_id', 'inner')
            ->where('facility_club_assignments.facility_id', $facilityId)
            ->where('facility_club_assignments.tenant_id', $tenantId)
            ->where('clubs.deleted_at', null);

        if ($activeOnly) $builder->where('facility_club_assignments.status', 'active');

        return $builder->orderBy('facility_club_assignments.is_primary', 'DESC')
            ->orderBy('clubs.name_vi', 'ASC')
            ->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->first();
    }
}
