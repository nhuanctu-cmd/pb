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
        $package = $this->packageModel->find($packageId);
        if (!$package || (int) $package->tenant_id !== $tenantId || $package->status !== 'active') {
            return null;
        }

        $player = model(\App\Models\PlayerModel::class)->find($playerId);
        if (!$player || (int) $player->tenant_id !== $tenantId) {
            return null;
        }

        $this->membershipModel->db->transStart();

        // Cancel any active membership
        $activeMembership = $this->membershipModel->getActiveByPlayer($playerId, $tenantId);
        if ($activeMembership) {
            $this->membershipModel->update($activeMembership->id, ['status' => 'cancelled']);
        }

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
            $this->membershipModel->db->transRollback();
            return null;
        }

        $this->membershipModel->db->transComplete();
        if ($this->membershipModel->db->transStatus() === false) {
            $this->membershipModel->db->transRollback();
            return null;
        }

        return (int) $membershipId;
    }

    public function renew(int $membershipId): ?int
    {
        $membership = $this->membershipModel->find($membershipId);
        if (!$membership) return null;

        $package = $this->packageModel->find($membership->package_id);
        if (!$package) return null;

        // Create new membership starting from end date of current
        $startDate = $membership->end_date;
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

        return (int) $this->membershipModel->insert($data);
    }

    public function cancel(int $membershipId): bool
    {
        $membership = $this->membershipModel->find($membershipId);
        if (!$membership || $membership->status !== 'active') return false;

        return $this->membershipModel->update($membershipId, ['status' => 'cancelled']);
    }

    public function checkActiveMembership(int $playerId): bool
    {
        $membership = $this->membershipModel->getActiveByPlayer($playerId);
        return $membership !== null;
    }

    public function getActiveMembership(int $playerId, ?int $tenantId = null)
    {
        return $this->membershipModel->getActiveByPlayer($playerId, $tenantId);
    }

    public function getPlayerMemberships(int $playerId)
    {
        return $this->membershipModel->getByPlayer($playerId);
    }

    public function getMemberships(int $tenantId, array $filters = [])
    {
        return $this->membershipModel->getByTenant($tenantId, $filters);
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
        $id = $this->packageModel->insert($data);
        return $id ? (int) $id : null;
    }

    public function updatePackage(int $id, array $data): bool
    {
        return $this->packageModel->update($id, $data);
    }

    public function deletePackage(int $id): bool
    {
        return $this->packageModel->delete($id);
    }

    public function getPackageById(int $id)
    {
        return $this->packageModel->find($id);
    }

    public function expireOverdueMemberships(): int
    {
        return $this->membershipModel->expireOverdue();
    }
}
