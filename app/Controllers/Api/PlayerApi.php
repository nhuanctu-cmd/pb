<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\PlayerService;
use App\Services\WalletService;
use App\Services\MembershipService;

class PlayerApi extends BaseController
{
    protected PlayerService $playerService;
    protected WalletService $walletService;
    protected MembershipService $membershipService;

    public function __construct()
    {
        $this->playerService     = new PlayerService();
        $this->walletService     = new WalletService();
        $this->membershipService = new MembershipService();
    }

    public function profile()
    {
        [$userId, $tenantId] = $this->apiContext();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Player not found',
            ]);
        }

        $profile = $this->playerService->getProfile($player->id);

        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'id'           => $profile->id,
                'player_code'  => $profile->player_code,
                'full_name'    => $profile->full_name,
                'phone'        => $profile->phone,
                'email'        => $profile->email,
                'gender'       => $profile->gender,
                'birthday'     => $profile->birthday,
                'avatar'       => $profile->avatar,
                'level'        => $profile->level,
                'rating_score' => (float) $profile->rating_score,
                'status'       => $profile->status,
                'membership'   => $profile->active_membership ? [
                    'package_id'   => $profile->active_membership->package_id,
                    'package_name' => $profile->active_membership->package_name_vi
                        ?? $profile->active_membership->package_name_en
                        ?? null,
                    'start_date'   => $profile->active_membership->start_date,
                    'end_date'     => $profile->active_membership->end_date,
                    'remaining_days' => $profile->active_membership->getRemainingDays(),
                ] : null,
                'wallet_balance' => $profile->wallet ? (float) $profile->wallet->balance : 0,
                'statistics'     => $profile->statistics ? [
                    'total_matches'  => $profile->statistics->total_matches,
                    'total_wins'     => $profile->statistics->total_wins,
                    'total_losses'   => $profile->statistics->total_losses,
                    'win_rate'       => (float) $profile->statistics->win_rate,
                    'current_streak' => $profile->statistics->current_streak,
                    'best_streak'    => $profile->statistics->best_streak,
                ] : null,
            ],
        ]);
    }

    public function updateProfile()
    {
        [$userId, $tenantId] = $this->apiContext();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Player not found',
            ]);
        }

        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $data = [];
        foreach (['full_name', 'phone', 'email', 'gender', 'birthday'] as $field) {
            if (array_key_exists($field, $input) && $input[$field] !== '') {
                $data[$field] = $input[$field];
            }
        }

        if (empty($data)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No data to update',
            ]);
        }

        if ($this->playerService->updatePlayer($player->id, $data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Profile updated successfully',
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Update failed',
        ]);
    }

    public function wallet()
    {
        [$userId, $tenantId] = $this->apiContext();

        $player = $this->playerService->getPlayerByUser($userId, $tenantId);
        if (!$player) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Player not found',
            ]);
        }

        $wallet       = $this->walletService->getWallet($player->id, $tenantId);
        $transactions = $this->walletService->getPlayerTransactions($player->id, $tenantId);

        $txnData = [];
        foreach ($transactions as $txn) {
            $txnData[] = [
                'id'             => $txn->id,
                'type'           => $txn->type,
                'amount'         => (float) $txn->amount,
                'balance_before' => (float) $txn->balance_before,
                'balance_after'  => (float) $txn->balance_after,
                'note'           => $txn->note,
                'created_at'     => $txn->created_at,
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => [
                'balance'      => (float) ($wallet->balance ?? 0),
                'transactions' => $txnData,
            ],
        ]);
    }

    public function ranking()
    {
        $tenantId = (int) ($this->request->api_tenant_id ?? current_tenant_id());
        $orderBy  = $this->request->getGet('order_by') ?? 'rating_score';
        $limit    = (int) ($this->request->getGet('limit') ?? 50);

        $allowedFields = ['rating_score', 'win_rate', 'total_matches', 'total_wins'];
        if (!in_array($orderBy, $allowedFields)) {
            $orderBy = 'rating_score';
        }

        $ranking = $this->playerService->getRanking($tenantId, $orderBy, $limit);

        $data = [];
        $rank = 1;
        foreach ($ranking as $player) {
            $data[] = [
                'rank'           => $rank++,
                'player_id'      => $player->id,
                'player_code'    => $player->player_code,
                'full_name'      => $player->full_name,
                'avatar'         => $player->avatar,
                'level'          => $player->level,
                'rating_score'   => (float) $player->rating_score,
                'total_matches'  => (int) ($player->total_matches ?? 0),
                'total_wins'     => (int) ($player->total_wins ?? 0),
                'total_losses'   => (int) ($player->total_losses ?? 0),
                'win_rate'       => (float) ($player->win_rate ?? 0),
                'current_streak' => (int) ($player->current_streak ?? 0),
                'best_streak'    => (int) ($player->best_streak ?? 0),
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => $data,
        ]);
    }

    private function apiContext(): array
    {
        return [
            (int) ($this->request->api_user_id ?? user_id()),
            (int) ($this->request->api_tenant_id ?? current_tenant_id()),
        ];
    }
}
