<?php

namespace App\Services;

use App\Models\CourtModel;
use App\Models\CourtTypeModel;
use App\Models\CourtImageModel;
use App\Models\CourtMaintenanceModel;
use App\Models\BranchOpeningHourModel;
use App\Models\BranchHolidayModel;

class CourtService
{
    protected CourtModel $courtModel;
    protected CourtTypeModel $courtTypeModel;
    protected CourtImageModel $courtImageModel;
    protected CourtMaintenanceModel $courtMaintenanceModel;
    protected BranchOpeningHourModel $branchOpeningHourModel;
    protected BranchHolidayModel $branchHolidayModel;

    public function __construct()
    {
        $this->courtModel              = new CourtModel();
        $this->courtTypeModel          = new CourtTypeModel();
        $this->courtImageModel         = new CourtImageModel();
        $this->courtMaintenanceModel   = new CourtMaintenanceModel();
        $this->branchOpeningHourModel  = new BranchOpeningHourModel();
        $this->branchHolidayModel      = new BranchHolidayModel();
    }

    public function getAllCourtTypes(int $tenantId)
    {
        return $this->courtTypeModel->getByTenant($tenantId);
    }

    public function getActiveCourtTypes(int $tenantId)
    {
        return $this->courtTypeModel->getActive($tenantId);
    }

    public function getCourtById(int $id)
    {
        return $this->courtModel->find($id);
    }

    public function createCourt(array $data): ?int
    {
        $this->courtModel->db->transStart();

        $courtId = $this->courtModel->insert($data);
        if (!$courtId) {
            $this->courtModel->db->transRollback();
            return null;
        }

        $this->courtModel->db->transComplete();

        if ($this->courtModel->db->transStatus() === false) {
            $this->courtModel->db->transRollback();
            return null;
        }

        return (int) $courtId;
    }

    public function updateCourt(int $id, array $data): bool
    {
        return $this->courtModel->update($id, $data);
    }

    public function deleteCourt(int $id): bool
    {
        // Check if court has active bookings
        if ($this->courtModel->hasBookings($id)) {
            return false;
        }

        return $this->courtModel->delete($id);
    }

    public function changeCourtStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['available', 'occupied', 'maintenance', 'inactive'])) {
            return false;
        }

        return $this->courtModel->update($id, ['status' => $status]);
    }

    public function getCourtGridByBranch(int $branchId, array $filters = [])
    {
        $courts = $this->courtModel->getByBranch($branchId, $filters);

        // Group by floor
        $grouped = [];
        foreach ($courts as $court) {
            $floor = $court->floor ?? 1;
            if (!isset($grouped[$floor])) {
                $grouped[$floor] = [
                    'floor'  => $floor,
                    'courts' => [],
                ];
            }
            $grouped[$floor]['courts'][] = $court;
        }

        ksort($grouped);
        return $grouped;
    }

    public function getAvailableCourts(int $branchId, ?string $date = null, ?string $startTime = null, ?string $endTime = null)
    {
        return $this->courtModel->getAvailable($branchId, $date, $startTime, $endTime);
    }

    public function scheduleMaintenance(array $data): ?int
    {
        $this->courtMaintenanceModel->db->transStart();

        $maintenanceId = $this->courtMaintenanceModel->insert($data);
        if (!$maintenanceId) {
            $this->courtMaintenanceModel->db->transRollback();
            return null;
        }

        // Update court status to maintenance
        $this->courtModel->update($data['court_id'], ['status' => 'maintenance']);

        $this->courtMaintenanceModel->db->transComplete();

        if ($this->courtMaintenanceModel->db->transStatus() === false) {
            $this->courtMaintenanceModel->db->transRollback();
            return null;
        }

        return (int) $maintenanceId;
    }

    public function checkCourtConflict(int $courtId, string $startTime, ?string $endTime = null, ?int $excludeMaintenanceId = null): bool
    {
        return $this->courtMaintenanceModel->hasConflict($courtId, $startTime, $endTime, $excludeMaintenanceId);
    }

    public function getMaintenanceByCourt(int $courtId)
    {
        return $this->courtMaintenanceModel->getByCourt($courtId);
    }

    public function getMaintenanceByBranch(int $branchId, array $filters = [])
    {
        return $this->courtMaintenanceModel->getByBranch($branchId, $filters);
    }

    public function getActiveMaintenanceByCourt(int $courtId)
    {
        return $this->courtMaintenanceModel->getActiveByCourt($courtId);
    }

    public function updateMaintenance(int $id, array $data): bool
    {
        return $this->courtMaintenanceModel->update($id, $data);
    }

    public function getCourtImages(int $courtId)
    {
        return $this->courtImageModel->getByCourt($courtId);
    }

    public function getPrimaryImage(int $courtId)
    {
        return $this->courtImageModel->getPrimary($courtId);
    }

    public function uploadImage(int $courtId, array $fileData): ?int
    {
        // Reset primary for first image
        $existingImages = $this->courtImageModel->getByCourt($courtId);
        $isPrimary = empty($existingImages) ? 1 : 0;

        $data = [
            'tenant_id'  => $fileData['tenant_id'],
            'court_id'   => $courtId,
            'file_path'  => $fileData['file_path'],
            'is_primary' => $isPrimary,
            'sort_order' => count($existingImages) + 1,
            'created_by' => $fileData['created_by'] ?? null,
        ];

        return $this->courtImageModel->insert($data);
    }

    public function deleteImage(int $imageId): bool
    {
        return $this->courtImageModel->delete($imageId);
    }

    public function setPrimaryImage(int $courtId, int $imageId): bool
    {
        $this->courtImageModel->db->transStart();

        $this->courtImageModel->resetPrimary($courtId);
        $result = $this->courtImageModel->update($imageId, ['is_primary' => 1]);

        $this->courtImageModel->db->transComplete();

        return $result;
    }

    public function isCodeUnique(string $code, int $branchId, ?int $excludeId = null): bool
    {
        return $this->courtModel->isCodeUnique($code, $branchId, $excludeId);
    }

    public function getOpeningHours(int $branchId)
    {
        return $this->branchOpeningHourModel->getByBranch($branchId);
    }
}
