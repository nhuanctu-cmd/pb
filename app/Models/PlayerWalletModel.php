<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerWalletModel extends Model
{
    protected $table            = 'player_wallets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = \App\Entities\PlayerWallet::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id', 'player_id', 'balance',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'tenant_id' => 'required|integer',
        'player_id' => 'required|integer',
        'balance'   => 'permit_empty|decimal',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    public function getByPlayer(int $playerId, int $tenantId)
    {
        return $this->where('player_id', $playerId)
                    ->where('tenant_id', $tenantId)
                    ->first();
    }

    public function findOrCreate(int $playerId, int $tenantId)
    {
        $wallet = $this->getByPlayer($playerId, $tenantId);
        if (!$wallet) {
            $this->insert([
                'tenant_id' => $tenantId,
                'player_id' => $playerId,
                'balance'   => 0,
            ]);
            $wallet = $this->getByPlayer($playerId, $tenantId);
        }
        return $wallet;
    }

    public function updateBalance(int $walletId, float $newBalance): bool
    {
        return $this->update($walletId, ['balance' => $newBalance]);
    }
}
