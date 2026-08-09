<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table            = 'bookings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Booking::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'facility_id', 'branch_id', 'player_id',
        'customer_name', 'customer_phone', 'customer_email',
        'booking_code', 'booking_date', 'start_time', 'end_time',
        'duration_minutes', 'total_amount', 'deposit_amount', 'paid_amount',
        'status', 'status_id', 'payment_status', 'source', 'note',
        'hold_until', 'is_hold', 'timeout_minutes', 'auto_release_at',
        'discount_amount', 'tax_amount', 'surcharge_amount', 'net_amount',
        'refund_amount', 'refund_policy', 'cancellation_policy',
        'pricing_rule_id', 'price_breakdown', 'player_count',
        'is_recurring', 'recurring_pattern', 'recurring_parent_id',
        'membership_discount', 'platform_fee',
        'cancelled_at', 'cancelled_reason', 'checked_in_at', 'completed_at',
        'expires_at', 'check_in_window_start', 'check_in_window_end',
        'reminder_sent_at', 'rating', 'feedback', 'created_by', 'updated_by',
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
        'tenant_id'      => 'required|integer',
        'branch_id'      => 'required|integer',
        'customer_name'  => 'required|max_length[255]',
        'customer_phone' => 'required|max_length[20]',
        'customer_email' => 'permit_empty|valid_email|max_length[255]',
        'booking_code'   => 'required|max_length[50]|is_unique[bookings.booking_code]',
        'booking_date'   => 'required|valid_date',
        'start_time'     => 'required',
        'end_time'       => 'required',
        'status'         => 'required|in_list[draft,pending,hold,reserved,paid,checked_in,in_progress,completed,cancelled,refunded,no_show,expired]',
        'payment_status' => 'required|in_list[unpaid,partial,paid,refunded]',
        'source'         => 'required|in_list[admin,player_portal,public_web,zalo,phone]',
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

    /**
     * Get bookings by branch with optional filters
     */
    public function getByBranch(int $branchId, array $filters = [])
    {
        $builder = $this->where('bookings.branch_id', $branchId)
                        ->where('bookings.deleted_at', null);

        if (!empty($filters['status'])) {
            $builder->where('bookings.status', $filters['status']);
        }

        if (!empty($filters['booking_date'])) {
            $builder->where('bookings.booking_date', $filters['booking_date']);
        }

        if (!empty($filters['date_from'])) {
            $builder->where('bookings.booking_date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('bookings.booking_date <=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('bookings.customer_name', $filters['search'])
                    ->orLike('bookings.customer_phone', $filters['search'])
                    ->orLike('bookings.booking_code', $filters['search'])
                    ->groupEnd();
        }

        $builder->orderBy('bookings.booking_date', 'DESC')
                ->orderBy('bookings.start_time', 'ASC');

        return $builder->findAll();
    }

    /**
     * Get bookings by player (user)
     */
    public function getByPlayer(int $playerId, array $filters = [])
    {
        $builder = $this->where('bookings.player_id', $playerId)
                        ->where('bookings.deleted_at', null);

        if (!empty($filters['status'])) {
            $builder->where('bookings.status', $filters['status']);
        }

        $builder->orderBy('bookings.booking_date', 'DESC')
                ->orderBy('bookings.start_time', 'DESC');

        return $builder->findAll();
    }

    /**
     * Get calendar events for a date range
     */
    public function getCalendarEvents(int $branchId, string $dateFrom, string $dateTo)
    {
        return $this->select('bookings.*, GROUP_CONCAT(CONCAT(booking_items.court_id, \':\', courts.code) SEPARATOR \', \') as court_info')
                    ->join('booking_items', 'booking_items.booking_id = bookings.id', 'left')
                    ->join('courts', 'courts.id = booking_items.court_id', 'left')
                    ->where('bookings.branch_id', $branchId)
                    ->where('bookings.deleted_at', null)
                    ->where('bookings.booking_date >=', $dateFrom)
                    ->where('bookings.booking_date <=', $dateTo)
                    ->where('bookings.status !=', 'cancelled')
                    ->groupBy('bookings.id')
                    ->orderBy('bookings.booking_date', 'ASC')
                    ->orderBy('bookings.start_time', 'ASC')
                    ->findAll();
    }

    /**
     * Find booking by QR token
     */
    public function findByQrToken(string $token)
    {
        return $this->select('bookings.*')
                    ->join('booking_qr_codes', 'booking_qr_codes.booking_id = bookings.id')
                    ->where('booking_qr_codes.qr_token', $token)
                    ->where('booking_qr_codes.status', 'active')
                    ->where('bookings.deleted_at', null)
                    ->first();
    }

    /**
     * Get pending expired bookings
     */
    public function getExpiredPending()
    {
        $now = date('Y-m-d H:i:s');
        return $this->groupStart()
                        ->where('bookings.status', 'pending')
                        ->where('bookings.expires_at IS NOT NULL')
                        ->where('bookings.expires_at <=', $now)
                    ->groupEnd()
                    ->orGroupStart()
                        ->where('bookings.status', 'hold')
                        ->where('bookings.auto_release_at IS NOT NULL')
                        ->where('bookings.auto_release_at <=', $now)
                    ->groupEnd()
                    ->where('bookings.deleted_at', null)
                    ->findAll();
    }

    /**
     * Generate unique booking code
     */
    public function generateBookingCode(int $tenantId, int $branchId): string
    {
        $prefix = 'BK-' . str_pad($tenantId, 3, '0', STR_PAD_LEFT) . str_pad($branchId, 3, '0', STR_PAD_LEFT) . '-';
        $datePart = date('ymd');

        $last = $this->select('booking_code')
                     ->where('booking_code LIKE', $prefix . $datePart . '%')
                     ->orderBy('id', 'DESC')
                     ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->booking_code);
            $seq = (int)end($parts) + 1;
        }

        return $prefix . $datePart . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check court availability for a time slot
     */
    public function isCourtAvailable(int $courtId, string $date, string $startTime, string $endTime, ?int $excludeBookingId = null): bool
    {
        $builder = $this->db->table('booking_items')
                           ->select('booking_items.id')
                           ->join('bookings', 'bookings.id = booking_items.booking_id')
                           ->where('booking_items.court_id', $courtId)
                           ->where('bookings.booking_date', $date)
                           ->where('bookings.deleted_at', null)
                           ->where('bookings.status !=', 'cancelled')
                           ->where('booking_items.status', 'active')
                           ->groupStart()
                           ->where('booking_items.start_time <', $endTime)
                           ->where('booking_items.end_time >', $startTime)
                           ->groupEnd();

        if ($excludeBookingId) {
            $builder->where('bookings.id !=', $excludeBookingId);
        }

        return $builder->countAllResults() === 0;
    }
}
