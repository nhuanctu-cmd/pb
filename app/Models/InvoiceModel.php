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
    protected $allowedFields    = ['tenant_id', 'branch_id', 'invoice_code', 'customer_type', 'player_id', 'customer_id', 'ref_type', 'ref_id', 'subtotal', 'discount_amount', 'total_amount', 'paid_amount', 'status', 'note', 'created_by'];
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

    public function getWithDetails(int $invoiceId, ?int $tenantId = null)
    {
        $builder = $this->select("invoices.*, players.full_name AS player_name, CONCAT_WS(' ', users.first_name, users.last_name) AS staff_name")
            ->join('players', 'players.id = invoices.player_id', 'left')
            ->join('users', 'users.id = invoices.created_by', 'left')
            ->where('invoices.id', $invoiceId);
        if ($tenantId !== null) {
            $builder->where('invoices.tenant_id', $tenantId);
        }
        return $builder->first();
    }

    public function findForTenant(int $invoiceId, int $tenantId): ?\App\Entities\Invoice
    {
        return $this->where('invoices.id', $invoiceId)
            ->where('invoices.tenant_id', $tenantId)
            ->first();
    }

    public function findForUpdate(int $invoiceId, ?int $tenantId = null): ?\App\Entities\Invoice
    {
        $sql = 'SELECT * FROM invoices WHERE id = ?';
        $params = [$invoiceId];
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = ?';
            $params[] = $tenantId;
        }
        $sql .= ' LIMIT 1 FOR UPDATE';
        $row = $this->db->query($sql, $params)->getRowArray();

        return $row ? new \App\Entities\Invoice($row) : null;
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
