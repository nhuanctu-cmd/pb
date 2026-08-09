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
        return $this->mutate($playerId, $tenantId, $amount, 'topup', $note, $refType, $refId, $createdBy);
    }

    public function pay(int $playerId, int $tenantId, float $amount, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $createdBy = null): bool
    {
        return $this->mutate($playerId, $tenantId, -$amount, 'payment', $note, $refType, $refId, $createdBy);
    }

    public function refund(int $playerId, int $tenantId, float $amount, ?string $note = null, ?string $refType = null, ?int $refId = null, ?int $createdBy = null): bool
    {
        return $this->mutate($playerId, $tenantId, $amount, 'refund', $note, $refType, $refId, $createdBy);
    }

    public function adjust(int $playerId, int $tenantId, float $newBalance, ?string $note = null, ?int $createdBy = null): bool
    {
        $newBalance = round($newBalance, 2);
        if ($newBalance < 0) {
            return false;
        }
        return $this->mutate($playerId, $tenantId, null, 'adjust', $note, null, null, $createdBy, $newBalance);
    }

    private function mutate(int $playerId, int $tenantId, ?float $delta, string $type, ?string $note, ?string $refType, ?int $refId, ?int $createdBy, ?float $targetBalance = null): bool
    {
        $amount = $delta === null ? 0 : round(abs($delta), 2);
        if ($playerId <= 0 || $tenantId <= 0 || ($delta !== null && $amount <= 0)) {
            return false;
        }

        $db = \Config\Database::connect();
        $db->transStart();
        try {
            $wallet = $this->walletModel->findForUpdate($playerId, $tenantId);
            if (! $wallet) {
                $walletId = $this->walletModel->insert([
                    'tenant_id' => $tenantId, 'player_id' => $playerId, 'balance' => 0,
                ]);
                if (! $walletId) {
                    throw new \RuntimeException('Wallet could not be created');
                }
                $wallet = $this->walletModel->findForUpdate($playerId, $tenantId);
            }
            if (! $wallet) {
                throw new \RuntimeException('Wallet not found');
            }

            $before = round((float) $wallet->balance, 2);
            $after = $targetBalance ?? round($before + (float) $delta, 2);
            if ($after < 0) {
                throw new \InvalidArgumentException('Insufficient wallet balance');
            }
            if (! $this->walletModel->updateBalance((int) $wallet->id, $after)) {
                throw new \RuntimeException('Wallet balance could not be updated');
            }
            if (! $this->transactionModel->insert([
                'tenant_id' => $tenantId, 'player_id' => $playerId, 'wallet_id' => $wallet->id,
                'type' => $type, 'amount' => $amount,
                'balance_before' => $before, 'balance_after' => $after,
                'ref_type' => $refType, 'ref_id' => $refId, 'note' => $note, 'created_by' => $createdBy,
            ])) {
                throw new \RuntimeException('Wallet ledger entry could not be created');
            }

            $db->transComplete();
            return $db->transStatus();
        } catch (\Throwable $e) {
            $db->transRollback();
            return false;
        }
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
