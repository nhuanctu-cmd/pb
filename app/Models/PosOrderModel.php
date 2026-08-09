<?php

namespace App\Models;

use CodeIgniter\Model;

class PosOrderModel extends Model
{
    protected $table            = 'pos_orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'branch_id', 'player_id', 'booking_id', 'order_code', 'total_amount', 'discount_amount', 'paid_amount', 'payment_status', 'status', 'note', 'created_by'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'     => 'required|integer',
        'branch_id'     => 'required|integer',
        'order_code'    => 'required|max_length[50]',
        'total_amount'  => 'required|decimal',
        'paid_amount'   => 'required|decimal',
        'payment_status'=> 'required|in_list[pending,paid,refunded]',
        'status'        => 'required|in_list[pending,completed,cancelled]',
    ];

    public function getByTenant(int $tenantId, ?int $branchId = null, ?string $status = null, int $limit = 100)
    {
        $builder = $this->where('tenant_id', $tenantId);
        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }
        if ($status) {
            $builder->where('status', $status);
        }
        return $builder->orderBy('created_at', 'DESC')->limit($limit)->findAll();
    }

    public function getWithDetails(int $orderId)
    {
        return $this->select('pos_orders.*, users.fullname as staff_name')
            ->join('users', 'users.id = pos_orders.created_by', 'left')
            ->where('pos_orders.id', $orderId)
            ->first();
    }
}
