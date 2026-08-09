<?php

namespace App\Models;

use CodeIgniter\Model;

class CourtModel extends Model
{
    protected $table            = 'courts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Court::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'facility_id', 'branch_id', 'court_type_id', 'status_id',
        'code', 'name_vi', 'name_en', 'display_name', 'floor', 'area',
        'is_indoor', 'surface_type', 'length', 'width', 'ceiling_height',
        'player_capacity', 'spectator_capacity', 'color_scheme',
        'has_light', 'has_fan', 'has_camera', 'status', 'sort_order',
        'coordinates_x', 'coordinates_y', 'rotation', 'amenities',
        'pricing_rules', 'last_active_at', 'created_by', 'updated_by',
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
        'tenant_id'     => 'required|integer',
        'branch_id'     => 'required|integer',
        'court_type_id' => 'required|integer',
        'code'          => 'required|max_length[50]',
        'name_vi'       => 'required|max_length[255]',
        'name_en'       => 'permit_empty|max_length[255]',
        'floor'         => 'permit_empty|integer',
        'area'          => 'permit_empty|decimal',
        'status'        => 'required|in_list[available,occupied,maintenance,inactive]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = ['logAudit'];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = ['logAudit'];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = ['logAudit'];

    public function logAudit(array $data)
    {
        if (function_exists('log_audit')) {
            log_audit($data);
        }
        return $data;
    }

    public function getByBranch(int $branchId, array $filters = [])
    {
        $builder = $this->where('courts.branch_id', $branchId)
                        ->where('courts.deleted_at', null)
                        ->join('court_types', 'court_types.id = courts.court_type_id', 'left')
                        ->select('courts.*, court_types.name_vi as court_type_name_vi, court_types.name_en as court_type_name_en');

        if (!empty($filters['court_type_id'])) {
            $builder->where('courts.court_type_id', $filters['court_type_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('courts.status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('courts.name_vi', $filters['search'])
                    ->orLike('courts.name_en', $filters['search'])
                    ->orLike('courts.code', $filters['search'])
                    ->groupEnd();
        }

        $builder->orderBy('courts.sort_order', 'ASC')
                ->orderBy('courts.code', 'ASC');

        return $builder->findAll();
    }

    public function getAvailable(int $branchId, ?string $date = null, ?string $startTime = null, ?string $endTime = null)
    {
        $builder = $this->where('courts.branch_id', $branchId)
                        ->where('courts.status', 'available')
                        ->where('courts.deleted_at', null);

        if ($date && $startTime && $endTime) {
            $builder->whereNotIn('courts.id', function ($sub) use ($date, $startTime, $endTime) {
                $sub->select('booking_items.court_id')
                    ->from('booking_items')
                    ->join('bookings', 'bookings.id = booking_items.booking_id')
                    ->where('bookings.booking_date', $date)
                    ->where('bookings.deleted_at', null)
                    ->whereNotIn('bookings.status', ['cancelled', 'refunded', 'expired'])
                    ->where('booking_items.status', 'active')
                    ->groupStart()
                    ->where('booking_items.start_time <', $endTime)
                    ->where('booking_items.end_time >', $startTime)
                    ->groupEnd();
            });
        }

        return $builder->findAll();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->where('tenant_id', $tenantId)
                    ->where('deleted_at', null)
                    ->findAll();
    }

    public function findForTenant(int $courtId, int $tenantId): ?object
    {
        return $this->where('id', $courtId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->first();
    }

    public function belongsToTenant(int $courtId, int $tenantId): bool
    {
        return $this->where('id', $courtId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->countAllResults() === 1;
    }

    public function isCodeUnique(string $code, int $branchId, ?int $excludeId = null): bool
    {
        $builder = $this->where('code', $code)
                        ->where('branch_id', $branchId)
                        ->where('deleted_at', null);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() === 0;
    }

    public function hasBookings(int $courtId): bool
    {
        $db = $this->db;
        $result = $db->table('booking_items')
                     ->join('bookings', 'bookings.id = booking_items.booking_id')
                     ->where('booking_items.court_id', $courtId)
                     ->where('bookings.deleted_at', null)
                     ->where('booking_items.status', 'active')
                     ->whereIn('bookings.status', ['pending', 'reserved', 'paid', 'checked_in', 'in_progress'])
                     ->countAllResults();

        return $result > 0;
    }
}
