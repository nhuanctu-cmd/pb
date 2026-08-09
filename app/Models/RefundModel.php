<?php

namespace App\Models;

use CodeIgniter\Model;

class RefundModel extends Model
{
    protected $table            = 'refunds';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\Refund::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'payment_id', 'invoice_id', 'amount', 'reason', 'status', 'processed_by'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'  => 'required|integer',
        'payment_id' => 'required|integer',
        'invoice_id' => 'required|integer',
        'amount'     => 'required|decimal',
        'status'     => 'required|in_list[pending,approved,rejected,completed]',
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

    public function getCompletedTotal(int $invoiceId, ?int $tenantId = null): float
    {
        $builder = $this->selectSum('amount')
            ->where('invoice_id', $invoiceId)
            ->where('status', 'completed');
        if ($tenantId !== null) {
            $builder->where('tenant_id', $tenantId);
        }
        return (float) ($builder->first()->amount ?? 0);
    }
}
