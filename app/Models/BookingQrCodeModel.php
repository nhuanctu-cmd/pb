<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingQrCodeModel extends Model
{
    protected $table            = 'booking_qr_codes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\BookingQrCode::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'booking_id', 'qr_token', 'qr_path',
        'expired_at', 'used_at', 'status',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'booking_id' => 'required|integer',
        'qr_token'   => 'required|max_length[100]|is_unique[booking_qr_codes.qr_token]',
        'status'     => 'required|in_list[active,used,expired,revoked]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Generate unique QR token
     */
    public function generateQrToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Find active QR by token
     */
    public function findActiveByToken(string $token)
    {
        return $this->where('qr_token', $token)
                    ->where('status', 'active')
                    ->first();
    }

    /**
     * Invalidate old QR codes for a booking
     */
    public function invalidateByBooking(int $bookingId)
    {
        return $this->where('booking_id', $bookingId)
                    ->where('status', 'active')
                    ->set(['status' => 'revoked', 'updated_at' => date('Y-m-d H:i:s')])
                    ->update();
    }
}
