<?php

namespace App\Services;

use App\Models\PlayerWalletModel;
use App\Models\WalletTransactionModel;

class WalletService
{
    protected PlayerWalletModel $walletModel;
    protected WalletTransactionModel $transactionModel;

    public function __construct()
    {
        $this->walletModel      = new PlayerWalletModel();
        $this->transactionModel = new WalletTransactionModel();
    }

    public function getWallet(int $playerId, int $tenantId)
    {
        return $this->walletModel->findOrCreate($playerId, $tenantId);
    }

    public function topup(int $playerId, int $tenantId, float $amount, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $createdBy = null): bool
    {
        $wallet = $this->walletModel->findOrCreate($playerId, $tenantId);
        if (!$wallet) return false;

        $this->walletModel->db->transStart();

        $balanceBefore = (float) $wallet->balance;
        $balanceAfter  = $balanceBefore + $amount;

        // Update wallet balance
        $this->walletModel->updateBalance($wallet->id, $balanceAfter);

        // Create transaction record
        $this->transactionModel->insert([
            'tenant_id'      => $tenantId,
            'player_id'      => $playerId,
            'wallet_id'      => $wallet->id,
            'type'           => 'topup',
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'ref_type'       => $refType,
            'ref_id'         => $refId,
            'note'           => $note,
            'created_by'     => $createdBy,
        ]);

        $this->walletModel->db->transComplete();
        return $this->walletModel->db->transStatus();
    }

    public function pay(int $playerId, int $tenantId, float $amount, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $createdBy = null): bool
    {
        $wallet = $this->walletModel->findOrCreate($playerId, $tenantId);
        if (!$wallet) return false;

        if ((float) $wallet->balance < $amount) {
            return false; // Insufficient balance
        }

        $this->walletModel->db->transStart();

        $balanceBefore = (float) $wallet->balance;
        $balanceAfter  = $balanceBefore - $amount;

        $this->walletModel->updateBalance($wallet->id, $balanceAfter);

        $this->transactionModel->insert([
            'tenant_id'      => $tenantId,
            'player_id'      => $playerId,
            'wallet_id'      => $wallet->id,
            'type'           => 'payment',
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'ref_type'       => $refType,
            'ref_id'         => $refId,
            'note'           => $note,
            'created_by'     => $createdBy,
        ]);

        $this->walletModel->db->transComplete();
        return $this->walletModel->db->transStatus();
    }

    public function refund(int $playerId, int $tenantId, float $amount, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $createdBy = null): bool
    {
        $wallet = $this->walletModel->findOrCreate($playerId, $tenantId);
        if (!$wallet) return false;

        $this->walletModel->db->transStart();

        $balanceBefore = (float) $wallet->balance;
        $balanceAfter  = $balanceBefore + $amount;

        $this->walletModel->updateBalance($wallet->id, $balanceAfter);

        $this->transactionModel->insert([
            'tenant_id'      => $tenantId,
            'player_id'      => $playerId,
            'wallet_id'      => $wallet->id,
            'type'           => 'refund',
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $balanceAfter,
            'ref_type'       => $refType,
            'ref_id'         => $refId,
            'note'           => $note,
            'created_by'     => $createdBy,
        ]);

        $this->walletModel->db->transComplete();
        return $this->walletModel->db->transStatus();
    }

    public function adjust(int $playerId, int $tenantId, float $newBalance, ?string $note = null, ?int $createdBy = null): bool
    {
        $wallet = $this->walletModel->findOrCreate($playerId, $tenantId);
        if (!$wallet) return false;

        $this->walletModel->db->transStart();

        $balanceBefore = (float) $wallet->balance;
        $amount        = $newBalance - $balanceBefore;

        $this->walletModel->updateBalance($wallet->id, $newBalance);

        $this->transactionModel->insert([
            'tenant_id'      => $tenantId,
            'player_id'      => $playerId,
            'wallet_id'      => $wallet->id,
            'type'           => 'adjust',
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $newBalance,
            'note'           => $note,
            'created_by'     => $createdBy,
        ]);

        $this->walletModel->db->transComplete();
        return $this->walletModel->db->transStatus();
    }

    public function getTransactions(int $walletId, int $limit = 20)
    {
        return $this->transactionModel->getByWallet($walletId, $limit);
    }

    public function getPlayerTransactions(int $playerId, int $tenantId, int $limit = 20)
    {
        return $this->transactionModel->getByPlayer($playerId, $tenantId, $limit);
    }
}
