<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Payment::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'invoice_id', 'payment_code', 'method', 'amount', 'transaction_ref', 'status', 'idempotency_key', 'paid_at', 'created_by'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'      => 'required|integer',
        'invoice_id'     => 'required|integer',
        'payment_code'   => 'required|max_length[50]',
        'method'         => 'required|in_list[cash,bank_qr,wallet,momo,stripe]',
        'amount'         => 'required|decimal',
        'status'         => 'required|in_list[pending,success,failed,cancelled]',
    ];

    public function getByInvoice(int $invoiceId, ?int $tenantId = null)
    {
        $builder = $this->where('invoice_id', $invoiceId);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        return $builder
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function findByIdempotencyKey(string $key, ?int $tenantId = null)
    {
        $builder = $this->where('idempotency_key', $key);
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        return $builder->first();
    }

    public function findForUpdate(int $paymentId, ?int $tenantId = null): ?\App\Entities\Payment
    {
        $sql = 'SELECT * FROM payments WHERE id = ?';
        $params = [$paymentId];
        if ($tenantId !== null) {
            $sql .= ' AND tenant_id = ?';
            $params[] = $tenantId;
        }
        $sql .= ' LIMIT 1 FOR UPDATE';
        $row = $this->db->query($sql, $params)->getRowArray();

        return $row ? new \App\Entities\Payment($row) : null;
    }

    public function getByTenant(int $tenantId, ?string $status = null, int $limit = 100)
    {
        $builder = $this->where('tenant_id', $tenantId);
        if ($status) {
            $builder->where('status', $status);
        }
        return $builder->orderBy('created_at', 'DESC')->limit($limit)->findAll();
    }
}
