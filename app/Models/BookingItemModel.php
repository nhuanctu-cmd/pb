<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingItemModel extends Model
{
    protected $table            = 'booking_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\BookingItem::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'facility_id', 'booking_id', 'court_id',
        'court_name', 'court_type_name', 'date', 'start_time', 'end_time',
        'price', 'base_price', 'dynamic_price', 'surcharge', 'discount',
        'pricing_detail', 'item_order', 'status',
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
        'court_id'   => 'required|integer',
        'start_time' => 'required',
        'end_time'   => 'required',
        'price'      => 'required|decimal',
        'status'     => 'required|in_list[active,cancelled,refunded]',
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
     * Get items by booking
     */
    public function getByBooking(int $bookingId)
    {
        return $this->select('booking_items.*, courts.code as court_code, courts.name_vi as court_name_vi, courts.name_en as court_name_en')
                    ->join('courts', 'courts.id = booking_items.court_id', 'left')
                    ->where('booking_items.booking_id', $bookingId)
                    ->findAll();
    }
}
