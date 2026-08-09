<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingWaitlistModel extends Model
{
    protected $table = 'booking_waitlist';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'tenant_id', 'branch_id', 'court_id', 'player_id', 'booking_id',
        'customer_name', 'customer_phone', 'customer_email', 'booking_date',
        'start_time', 'end_time', 'duration_minutes', 'priority', 'status',
        'idempotency_key', 'notified_at', 'expires_at', 'claimed_at',
        'created_by', 'updated_by',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function getByTenant(int $tenantId, array $filters = []): array
    {
        $builder = $this->where('tenant_id', $tenantId)->where('deleted_at', null);
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (!empty($filters['booking_date'])) {
            $builder->where('booking_date', $filters['booking_date']);
        }
        return $builder->orderBy('priority', 'ASC')->orderBy('created_at', 'ASC')->findAll();
    }

    public function findForTenant(int $id, int $tenantId): ?object
    {
        return $this->where('id', $id)->where('tenant_id', $tenantId)->where('deleted_at', null)->first();
    }

    public function findForUpdate(int $id, int $tenantId): ?object
    {
        $row = $this->db->query(
            'SELECT * FROM booking_waitlist WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL LIMIT 1 FOR UPDATE',
            [$id, $tenantId]
        )->getRowArray();
        return $row ? (object) $row : null;
    }

    public function findExistingKey(string $key, int $tenantId): ?object
    {
        return $this->where('idempotency_key', $key)->where('tenant_id', $tenantId)->first();
    }

    public function findNextForSlot(int $tenantId, int $branchId, ?int $courtId, string $date, string $start, string $end): ?object
    {
        $builder = $this->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->where('status', 'waiting')
            ->where('booking_date', $date)
            ->where('deleted_at', null)
            ->groupStart()
                ->where('start_time <', $end)
                ->where('end_time >', $start)
            ->groupEnd();
        if ($courtId !== null) {
            $builder->groupStart()->where('court_id', $courtId)->orWhere('court_id', null)->groupEnd();
        }
        return $builder->orderBy('priority', 'ASC')->orderBy('created_at', 'ASC')->first();
    }
}
