<?php

namespace App\Models;

use CodeIgniter\Model;

class CourtMaintenanceModel extends Model
{
    protected $table            = 'court_maintenance';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'facility_id', 'branch_id', 'court_id', 'maintenance_type',
        'priority', 'title_vi', 'title_en', 'notes', 'assigned_to',
        'start_time', 'end_time', 'reason', 'status', 'completed_at',
        'cost_estimate', 'actual_cost', 'images_before', 'images_after',
        'created_by', 'updated_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'branch_id'  => 'required|integer',
        'court_id'   => 'required|integer',
        'start_time' => 'required|valid_date',
        'end_time'   => 'permit_empty|valid_date',
        'reason'     => 'permit_empty',
        'status'     => 'required|in_list[scheduled,doing,completed,cancelled]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = ['logAudit'];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = ['logAudit'];
    protected $afterDelete    = ['logAudit'];

    public function logAudit(array $data)
    {
        if (function_exists('log_audit')) {
            log_audit($data);
        }
        return $data;
    }

    public function getByCourt(int $courtId)
    {
        return $this->where('court_id', $courtId)
                    ->where('deleted_at', null)
                    ->orderBy('start_time', 'DESC')
                    ->findAll();
    }

    public function findForTenant(int $maintenanceId, int $tenantId): ?object
    {
        return $this->where('id', $maintenanceId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }

    public function getActiveByCourt(int $courtId)
    {
        return $this->where('court_id', $courtId)
                    ->whereIn('status', ['scheduled', 'doing'])
                    ->where('deleted_at', null)
                    ->orderBy('start_time', 'ASC')
                    ->findAll();
    }

    public function getByBranch(int $branchId, array $filters = [])
    {
        $builder = $this->where('court_maintenance.branch_id', $branchId)
                        ->where('court_maintenance.deleted_at', null)
                        ->join('courts', 'courts.id = court_maintenance.court_id', 'left')
                        ->select('court_maintenance.*, courts.code as court_code, courts.name_vi as court_name_vi');

        if (!empty($filters['status'])) {
            $builder->where('court_maintenance.status', $filters['status']);
        }

        if (!empty($filters['court_id'])) {
            $builder->where('court_maintenance.court_id', $filters['court_id']);
        }

        return $builder->orderBy('court_maintenance.start_time', 'DESC')->findAll();
    }

    public function hasConflict(int $courtId, string $startTime, ?string $endTime = null, ?int $excludeId = null): bool
    {
        $builder = $this->where('court_id', $courtId)
                        ->where('deleted_at', null)
                        ->whereIn('status', ['scheduled', 'doing'])
                        ->where('start_time <', $endTime ?: '9999-12-31 23:59:59')
                        ->groupStart()
                        ->where('end_time IS NULL', null, false)
                        ->orWhere('end_time >', $startTime)
                        ->groupEnd();

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    public static function intervalsOverlap(string $existingStart, ?string $existingEnd, string $requestedStart, ?string $requestedEnd): bool
    {
        $existingEnd = $existingEnd ?: '9999-12-31 23:59:59';
        $requestedEnd = $requestedEnd ?: '9999-12-31 23:59:59';
        return $existingStart < $requestedEnd && $requestedStart < $existingEnd;
    }
}
