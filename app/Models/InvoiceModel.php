<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table            = 'invoices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Invoice::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'branch_id', 'invoice_code', 'customer_type', 'player_id', 'ref_type', 'ref_id', 'subtotal', 'discount_amount', 'total_amount', 'paid_amount', 'status', 'note', 'created_by'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'      => 'required|integer',
        'invoice_code'   => 'required|max_length[50]',
        'customer_type'  => 'required|in_list[player,guest]',
        'subtotal'       => 'required|decimal',
        'total_amount'   => 'required|decimal',
        'paid_amount'    => 'required|decimal',
        'status'         => 'required|in_list[unpaid,partial,paid,cancelled,refunded]',
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

    public function getWithDetails(int $invoiceId)
    {
        return $this->select('invoices.*, players.fullname as player_name, users.fullname as staff_name')
            ->join('players', 'players.id = invoices.player_id', 'left')
            ->join('users', 'users.id = invoices.created_by', 'left')
            ->where('invoices.id', $invoiceId)
            ->first();
    }

    public function getInvoicesByTenant(int $tenantId, ?int $branchId = null, ?string $status = null, int $limit = 100)
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
}
