<?php

namespace App\Services;

use App\Models\MembershipModel;
use App\Models\MembershipPackageModel;

class MembershipService
{
    protected MembershipModel $membershipModel;
    protected MembershipPackageModel $packageModel;

    public function __construct()
    {
        $this->membershipModel = new MembershipModel();
        $this->packageModel    = new MembershipPackageModel();
    }

    public function buyPackage(int $playerId, int $packageId, int $tenantId, ?int $createdBy = null): ?int
    {
        if ($playerId <= 0 || $packageId <= 0 || $tenantId <= 0) {
            return null;
        }
        $package = $this->packageModel->findForTenant($packageId, $tenantId);
        if (!$package || (int) $package->tenant_id !== $tenantId || $package->status !== 'active') {
            return null;
        }

        if ((int) $package->duration_days <= 0) {
            return null;
        }

        $player = model(\App\Models\PlayerModel::class)->find($playerId);
        if (!$player || (int) $player->tenant_id !== $tenantId) {
            return null;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // Serialize membership replacement for this player and tenant.
        $db->query(
            'SELECT id FROM memberships WHERE player_id = ? AND tenant_id = ? AND status = ? AND deleted_at IS NULL FOR UPDATE',
            [$playerId, $tenantId, 'active']
        );
        $this->membershipModel->where('player_id', $playerId)
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->set(['status' => 'cancelled'])
            ->update();

        // Create new membership
        $startDate = date('Y-m-d');
        $endDate   = date('Y-m-d', strtotime("+{$package->duration_days} days"));

        $data = [
            'tenant_id'  => $tenantId,
            'player_id'  => $playerId,
            'package_id' => $packageId,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => 'active',
            'created_by' => $createdBy,
        ];

        $membershipId = $this->membershipModel->insert($data);
        if (!$membershipId) {
            $db->transRollback();
            return null;
        }

        $db->transComplete();
        if ($db->transStatus() === false) {
            $db->transRollback();
            return null;
        }

        return (int) $membershipId;
    }

    public function renew(int $membershipId, ?int $tenantId = null): ?int
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $membership = $this->membershipModel->findForUpdate($membershipId, $tenantId);
        if (!$membership) {
            $db->transRollback();
            return null;
        }

        $package = $this->packageModel->findForTenant((int) $membership->package_id, (int) $membership->tenant_id);
        if (!$package) {
            $db->transRollback();
            return null;
        }

        // Create new membership starting from end date of current
        $startDate = max(date('Y-m-d'), $membership->end_date);
        $endDate   = date('Y-m-d', strtotime($startDate . " +{$package->duration_days} days"));

        // Mark old as expired
        $this->membershipModel->update($membershipId, ['status' => 'expired']);

        $data = [
            'tenant_id'  => $membership->tenant_id,
            'player_id'  => $membership->player_id,
            'package_id' => $membership->package_id,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'status'     => 'active',
        ];

        $newId = $this->membershipModel->insert($data);
        if (! $newId) {
            $db->transRollback();
            return null;
        }
        $db->transComplete();
        return $db->transStatus() ? (int) $newId : null;
    }

    public function cancel(int $membershipId, ?int $tenantId = null): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();
        $membership = $this->membershipModel->findForUpdate($membershipId, $tenantId);
        if (!$membership || $membership->status !== 'active') {
            $db->transRollback();
            return false;
        }

        $updated = $this->membershipModel->update($membershipId, ['status' => 'cancelled']);
        $db->transComplete();
        return $updated && $db->transStatus();
    }

    public function checkActiveMembership(int $playerId, ?int $tenantId = null): bool
    {
        $membership = $this->membershipModel->getActiveByPlayer($playerId, $tenantId);
        return $membership !== null;
    }

    public function getActiveMembership(int $playerId, ?int $tenantId = null)
    {
        return $this->membershipModel->getActiveByPlayer($playerId, $tenantId);
    }

    public function getPlayerMemberships(int $playerId, ?int $tenantId = null)
    {
        return $this->membershipModel->getByPlayer($playerId, $tenantId);
    }

    public function getMemberships(int $tenantId, array $filters = [])
    {
        return $this->membershipModel->getByTenant($tenantId, $filters);
    }

    public function getRenewalCandidates(int $tenantId, int $days = 30): array
    {
        $db = \Config\Database::connect();
        return $db->table('memberships m')
            ->select('m.*, p.player_code, p.full_name, p.phone, mp.name_vi AS package_name_vi, mp.name_en AS package_name_en, mp.price, DATEDIFF(m.end_date, CURDATE()) AS remaining_days')
            ->join('players p', 'p.id = m.player_id', 'left')
            ->join('membership_packages mp', 'mp.id = m.package_id', 'left')
            ->where('m.tenant_id', $tenantId)->where('m.deleted_at', null)->whereIn('m.status', ['active', 'expired'])
            ->where('m.end_date <=', date('Y-m-d', strtotime('+' . max(0, $days) . ' days')))
            ->orderBy('m.end_date', 'ASC')->get()->getResult();
    }

    public function getPackages(int $tenantId)
    {
        return $this->packageModel->getActiveByTenant($tenantId);
    }

    public function getAllPackages(int $tenantId)
    {
        return $this->packageModel->getByTenant($tenantId);
    }

    public function createPackage(array $data): ?int
    {
        if ((int) ($data['tenant_id'] ?? 0) <= 0 || (int) ($data['duration_days'] ?? 0) <= 0 || (float) ($data['price'] ?? 0) < 0) {
            return null;
        }
        $id = $this->packageModel->insert($data);
        return $id ? (int) $id : null;
    }

    public function updatePackage(int $id, array $data, ?int $tenantId = null): bool
    {
        if ($tenantId !== null && ! $this->packageModel->findForTenant($id, $tenantId)) {
            return false;
        }
        return $this->packageModel->update($id, $data);
    }

    public function deletePackage(int $id, ?int $tenantId = null): bool
    {
        if ($tenantId !== null && ! $this->packageModel->findForTenant($id, $tenantId)) {
            return false;
        }
        return $this->packageModel->delete($id);
    }

    public function getPackageById(int $id, ?int $tenantId = null)
    {
        return $tenantId === null ? $this->packageModel->find($id) : $this->packageModel->findForTenant($id, $tenantId);
    }

    public function expireOverdueMemberships(): int
    {
        return $this->membershipModel->expireOverdue();
    }
}
