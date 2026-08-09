<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentQrConfigModel extends Model
{
    protected $table            = 'payment_qr_configs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\PaymentQrConfig::class;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['tenant_id', 'bank_name', 'bank_account', 'account_name', 'qr_template', 'status'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'tenant_id'     => 'required|integer',
        'bank_name'     => 'required|max_length[100]',
        'bank_account'  => 'required|max_length[50]',
        'account_name'  => 'required|max_length[255]',
        'status'        => 'required|in_list[active,inactive]',
    ];

    public function getActiveByTenant(int $tenantId)
    {
        return $this->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();
    }
}
