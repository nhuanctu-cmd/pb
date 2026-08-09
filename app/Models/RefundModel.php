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

    public function getByInvoice(int $invoiceId)
    {
        return $this->where('invoice_id', $invoiceId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
