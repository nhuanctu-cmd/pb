<?php

namespace App\Models;

use CodeIgniter\Model;

class WalletTransactionModel extends Model
{
    protected $table            = 'wallet_transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\WalletTransaction::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'player_id', 'wallet_id', 'type', 'amount',
        'balance_before', 'balance_after', 'ref_type', 'ref_id', 'note', 'created_by',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'tenant_id'      => 'required|integer',
        'player_id'      => 'required|integer',
        'wallet_id'      => 'required|integer',
        'type'           => 'required|in_list[topup,payment,refund,adjust]',
        'amount'         => 'required|decimal',
        'balance_before' => 'required|decimal',
        'balance_after'  => 'required|decimal',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function getByWallet(int $walletId, int $limit = 20)
    {
        return $this->where('wallet_id', $walletId)
                    ->orderBy('created_at', 'DESC')
                    ->paginate($limit);
    }

    public function getByPlayer(int $playerId, int $tenantId, int $limit = 20)
    {
        return $this->where('player_id', $playerId)
                    ->where('tenant_id', $tenantId)
                    ->orderBy('created_at', 'DESC')
                    ->paginate($limit);
    }
}
