<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingLogModel extends Model
{
    protected $table            = 'booking_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\BookingLog::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'booking_id', 'action',
        'old_status', 'new_status', 'message', 'created_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'booking_id' => 'required|integer',
        'action'     => 'required|max_length[100]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get logs by booking
     */
    public function getByBooking(int $bookingId)
    {
        return $this->where('booking_id', $bookingId)
                    ->orderBy('created_at', 'ASC')
                    ->findAll();
    }

    /**
     * Add a log entry
     */
    public function addLog(int $tenantId, int $bookingId, string $action, ?string $oldStatus, ?string $newStatus, ?string $message = null, ?int $createdBy = null)
    {
        return $this->insert([
            'tenant_id'  => $tenantId,
            'booking_id' => $bookingId,
            'action'     => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'message'    => $message,
            'created_by' => $createdBy,
        ]);
    }
}
