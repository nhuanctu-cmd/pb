<?php

namespace App\Services;

use App\Models\FacilityModel;
use App\Models\BranchModel;
use App\Models\CourtModel;
use App\Models\CourtTypeModel;
use App\Models\CourtStatusModel;
use App\Models\CourtImageModel;
use App\Models\CourtDeviceModel;
use App\Models\CourtDeviceLogModel;
use App\Models\CourtSessionModel;
use App\Models\CourtMaintenanceModel;
use App\Models\BranchOpeningHourModel;
use App\Models\BranchHolidayModel;
use App\Models\BranchMediaModel;
use App\Models\ClubModel;
use App\Models\FacilityClubAssignmentModel;

class FacilityService
{
    protected FacilityModel $facilityModel;
    protected BranchModel $branchModel;
    protected CourtModel $courtModel;
    protected CourtTypeModel $courtTypeModel;
    protected CourtStatusModel $courtStatusModel;
    protected CourtImageModel $courtImageModel;
    protected CourtDeviceModel $courtDeviceModel;
    protected CourtDeviceLogModel $courtDeviceLogModel;
    protected CourtSessionModel $courtSessionModel;
    protected CourtMaintenanceModel $courtMaintenanceModel;
    protected BranchOpeningHourModel $branchOpeningHourModel;
    protected BranchHolidayModel $branchHolidayModel;
    protected BranchMediaModel $branchMediaModel;
    protected ClubModel $clubModel;
    protected FacilityClubAssignmentModel $facilityClubAssignmentModel;

    public function __construct()
    {
        $this->facilityModel          = new FacilityModel();
        $this->branchModel            = new BranchModel();
        $this->courtModel             = new CourtModel();
        $this->courtTypeModel         = new CourtTypeModel();
        $this->courtStatusModel       = new CourtStatusModel();
        $this->courtImageModel        = new CourtImageModel();
        $this->courtDeviceModel       = new CourtDeviceModel();
        $this->courtDeviceLogModel    = new CourtDeviceLogModel();
        $this->courtSessionModel      = new CourtSessionModel();
        $this->courtMaintenanceModel  = new CourtMaintenanceModel();
        $this->branchOpeningHourModel = new BranchOpeningHourModel();
        $this->branchHolidayModel     = new BranchHolidayModel();
        $this->branchMediaModel       = new BranchMediaModel();
        $this->clubModel              = new ClubModel();
        $this->facilityClubAssignmentModel = new FacilityClubAssignmentModel();
    }

    // ========== FACILITY MANAGEMENT ==========

    public function getAllFacilities(int $tenantId, array $filters = [])
    {
        return $this->facilityModel->getByTenant($tenantId, $filters);
    }

    public function getTenantClubs(int $tenantId): array
    {
        return $tenantId ? $this->clubModel->getByTenant($tenantId, ['status' => 'active']) : [];
    }

    public function getFacilityClubs(int $facilityId, int $tenantId, bool $activeOnly = true): array
    {
        if (! $this->facilityClubAssignmentModel->db->tableExists('facility_club_assignments')) return [];
        return $this->facilityClubAssignmentModel->getForFacility($facilityId, $tenantId, $activeOnly);
    }

    public function assignClubToFacility(
        int $facilityId,
        int $clubId,
        int $tenantId,
        int $actorId = 0,
        bool $primary = false,
        ?string $notes = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?float $revenueShare = null,
        int $bookingPriority = 0,
        $allowedCourts = null,
        $allowedHours = null
    ): array {
        if (! $this->facilityModel->findForTenant($facilityId, $tenantId)) return ['success' => false, 'message' => 'Cụm sân không thuộc tenant hiện tại.'];
        if (! $this->clubModel->findForTenant($clubId, $tenantId)) return ['success' => false, 'message' => 'CLB không thuộc tenant hiện tại.'];
        if (! $this->facilityClubAssignmentModel->db->tableExists('facility_club_assignments')) return ['success' => false, 'message' => 'Chưa có schema gán CLB cho cụm sân.'];

        $db = $this->facilityClubAssignmentModel->db;
        $db->transStart();
        if ($primary) {
            $db->table('facility_club_assignments')
                ->where('tenant_id', $tenantId)
                ->where('facility_id', $facilityId)
                ->update(['is_primary' => 0, 'updated_by' => $actorId ?: null, 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $existing = $db->table('facility_club_assignments')
            ->where('tenant_id', $tenantId)
            ->where('facility_id', $facilityId)
            ->where('club_id', $clubId)
            ->get()->getRow();

        $data = [
            'tenant_id'        => $tenantId,
            'facility_id'      => $facilityId,
            'club_id'          => $clubId,
            'status'           => 'active',
            'is_primary'       => $primary ? 1 : (int) ($existing->is_primary ?? 0),
            'start_date'       => $startDate ?: null,
            'end_date'         => $endDate ?: null,
            'revenue_share'    => $revenueShare,
            'booking_priority' => $bookingPriority,
            'allowed_courts'   => $this->normalizeJson($allowedCourts),
            'allowed_hours'    => $this->normalizeJson($allowedHours),
            'notes'            => $notes,
            'updated_by'       => $actorId ?: null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $db->table('facility_club_assignments')->where('id', $existing->id)->update($data);
        } else {
            $db->table('facility_club_assignments')->insert($data + ['created_by' => $actorId ?: null, 'created_at' => date('Y-m-d H:i:s')]);
        }

        $db->transComplete();
        return [
            'success' => $db->transStatus(),
            'message' => $db->transStatus() ? 'Đã gán CLB vào cụm sân.' : 'Không thể gán CLB vào cụm sân.'
        ];
    }

    public function removeClubFromFacility(int $assignmentId, int $tenantId, int $actorId = 0): bool
    {
        if (! $this->facilityClubAssignmentModel->db->tableExists('facility_club_assignments')) return false;
        return (bool) $this->facilityClubAssignmentModel->where('id', $assignmentId)->where('tenant_id', $tenantId)->update(['status' => 'inactive', 'updated_by' => $actorId ?: null, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    private function normalizeJson($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }
        if (is_array($value)) {
            return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        }
        return (string) $value;
    }

    public function getFacilityById(int $id)
    {
        return $this->facilityModel->find($id);
    }

    public function createFacility(array $data): ?int
    {
        $this->facilityModel->db->transStart();

        $facilityId = $this->facilityModel->insert($data);
        if (!$facilityId) {
            $this->facilityModel->db->transRollback();
            return null;
        }

        $this->facilityModel->db->transComplete();
        return $this->facilityModel->db->transStatus() ? (int) $facilityId : null;
    }

    public function updateFacility(int $id, array $data): bool
    {
        return $this->facilityModel->update($id, $data);
    }

    public function deleteFacility(int $id): bool
    {
        // Check if facility has branches
        $facility = $this->facilityModel->find($id);
        $branchQuery = $this->branchModel;
        if ($this->hasBranchFacilityColumn()) {
            $branchQuery->where('facility_id', $id);
        } elseif ($facility) {
            // Older installations may have facilities but not the optional
            // relation column on branches. Keep the module usable by tenant.
            $branchQuery->where('tenant_id', $facility->tenant_id);
        }
        $branches = $branchQuery->countAllResults();
        if ($branches > 0) {
            return false;
        }
        return $this->facilityModel->delete($id);
    }

    public function getFacilityDashboard(int $facilityId): array
    {
        $facility = $this->facilityModel->find($facilityId);
        if (!$facility) return [];

        $branchQuery = $this->branchModel
            ->where('tenant_id', $facility->tenant_id)
            ->where('deleted_at', null);
        if ($this->hasBranchFacilityColumn()) {
            $branchQuery->where('facility_id', $facilityId);
        }
        $branches = $branchQuery->findAll();
        $branchIds = array_map(static fn ($branch) => (int) ($branch->id ?? 0), $branches);
        if (empty($branchIds)) {
            return [
                'facility'           => $facility,
                'branches'           => $branches,
                'total_courts'       => 0,
                'active_sessions'    => 0,
                'maintenance_courts' => 0,
                'available_courts'   => 0,
                'today_bookings'     => 0,
                'online_devices'     => 0,
                'occupancy_rate'     => 0,
            ];
        }

        $totalCourts = $this->courtModel->whereIn('branch_id', $branchIds)->countAllResults();
        $activeSessions = 0;
        if ($this->dbTableExists('court_sessions')) {
            $activeSessions = $this->courtSessionModel->whereIn('branch_id', $branchIds)
                ->where('status', 'active')
                ->countAllResults();
        }
        $maintenanceCourts = $this->courtModel->whereIn('branch_id', $branchIds)
            ->where('status', 'maintenance')
            ->countAllResults();
        $availableCourts = $totalCourts - $activeSessions - $maintenanceCourts;

        $today = date('Y-m-d');
        $todayBookings = model('BookingModel')
            ->whereIn('branch_id', $branchIds)
            ->where('DATE(booking_date)', $today)
            ->countAllResults();

        $onlineDevices = 0;
        if ($this->dbTableExists('court_devices')) {
            $onlineDevices = $this->courtDeviceModel
                ->whereIn('branch_id', $branchIds)
                ->where('status', 'online')
                ->countAllResults();
        }

        return [
            'facility'          => $facility,
            'branches'          => $branches,
            'total_courts'      => $totalCourts,
            'active_sessions'   => $activeSessions,
            'maintenance_courts'=> $maintenanceCourts,
            'available_courts'  => $availableCourts,
            'today_bookings'    => $todayBookings,
            'online_devices'    => $onlineDevices,
            'occupancy_rate'    => $totalCourts > 0 ? round(($activeSessions / $totalCourts) * 100) : 0,
        ];
    }

    // ========== BRANCH MANAGEMENT ==========

    public function getBranchesByFacility(int $facilityId)
    {
        $facility = $this->facilityModel->find($facilityId);
        $query = $this->branchModel;
        if ($this->hasBranchFacilityColumn()) {
            $query->where('facility_id', $facilityId);
        } elseif ($facility) {
            $query->where('tenant_id', $facility->tenant_id);
        }
        if ($this->branchModel->db->fieldExists('branch_type', 'branches')) {
            $query->orderBy('branch_type', 'ASC');
        }
        return $query
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    private function hasBranchFacilityColumn(): bool
    {
        return $this->branchModel->db->fieldExists('facility_id', 'branches');
    }

    private function dbTableExists(string $table): bool
    {
        return $this->branchModel->db->tableExists($table);
    }

    public function getBranchById(int $id)
    {
        return $this->branchModel->find($id);
    }

    public function createBranch(array $data): ?int
    {
        $this->branchModel->db->transStart();
        $branchId = $this->branchModel->insert($data);
        if (!$branchId) {
            $this->branchModel->db->transRollback();
            return null;
        }

        // Update facility branch count
        if (!empty($data['facility_id'])) {
            $this->facilityModel->where('id', $data['facility_id'])
                ->set('total_branches', 'total_branches + 1', false)
                ->update();
        }

        $this->branchModel->db->transComplete();
        return $this->branchModel->db->transStatus() ? (int) $branchId : null;
    }

    public function updateBranch(int $id, array $data): bool
    {
        return $this->branchModel->update($id, $data);
    }

    public function deleteBranch(int $id): bool
    {
        $branch = $this->branchModel->find($id);
        if (!$branch) return false;

        $courts = $this->courtModel->where('branch_id', $id)->countAllResults();
        if ($courts > 0) return false;

        $this->branchModel->db->transStart();
        $result = $this->branchModel->delete($id);

        if ($result && !empty($branch->facility_id)) {
            $this->facilityModel->where('id', $branch->facility_id)
                ->set('total_branches', 'total_branches - 1', false)
                ->update();
        }

        $this->branchModel->db->transComplete();
        return $result;
    }

    public function getBranchOpeningHours(int $branchId)
    {
        return $this->branchOpeningHourModel->getByBranch($branchId);
    }

    public function updateOpeningHours(int $branchId, array $hours): bool
    {
        $this->branchOpeningHourModel->db->transStart();
        $this->branchOpeningHourModel->where('branch_id', $branchId)->delete();

        foreach ($hours as $hour) {
            $this->branchOpeningHourModel->insert([
                'tenant_id'  => $hour['tenant_id'],
                'branch_id'  => $branchId,
                'day_of_week'=> $hour['day_of_week'],
                'open_time'  => $hour['open_time'] ?? null,
                'close_time' => $hour['close_time'] ?? null,
                'is_closed'  => $hour['is_closed'] ?? 0,
            ]);
        }

        $this->branchOpeningHourModel->db->transComplete();
        return $this->branchOpeningHourModel->db->transStatus();
    }

    public function getBranchHolidays(int $branchId, ?int $year = null)
    {
        return $this->branchHolidayModel->getByBranch($branchId, $year);
    }

    public function getBranchMedia(int $branchId)
    {
        return $this->branchMediaModel->getByBranch($branchId);
    }

    // ========== COURT TYPE MANAGEMENT ==========

    public function getAllCourtTypes(int $tenantId)
    {
        return $this->courtTypeModel->getByTenant($tenantId);
    }

    public function getActiveCourtTypes(int $tenantId)
    {
        return $this->courtTypeModel->getActive($tenantId);
    }

    public function createCourtType(array $data): ?int
    {
        return $this->courtTypeModel->insert($data);
    }

    public function updateCourtType(int $id, array $data): bool
    {
        return $this->courtTypeModel->update($id, $data);
    }

    public function deleteCourtType(int $id): bool
    {
        $courts = $this->courtModel->where('court_type_id', $id)->countAllResults();
        if ($courts > 0) return false;
        return $this->courtTypeModel->delete($id);
    }

    // ========== COURT STATUS MANAGEMENT ==========

    public function getAllCourtStatuses(int $tenantId)
    {
        return $this->courtStatusModel->getByTenant($tenantId);
    }

    public function getActiveCourtStatuses(int $tenantId)
    {
        return $this->courtStatusModel->getActive($tenantId);
    }

    public function getBookableStatuses(int $tenantId)
    {
        return $this->courtStatusModel->getBookable($tenantId);
    }

    public function createCourtStatus(array $data): ?int
    {
        return $this->courtStatusModel->insert($data);
    }

    public function updateCourtStatus(int $id, array $data): bool
    {
        return $this->courtStatusModel->update($id, $data);
    }

    public function deleteCourtStatus(int $id): bool
    {
        return $this->courtStatusModel->delete($id);
    }

    // ========== COURT MANAGEMENT ==========

    public function getCourtsByBranch(int $branchId, array $filters = [])
    {
        return $this->courtModel->getByBranch($branchId, $filters);
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

        // Update branch court count
        if (!empty($data['branch_id'])) {
            $this->branchModel->where('id', $data['branch_id'])
                ->set('total_courts', 'total_courts + 1', false)
                ->update();

            $branch = $this->branchModel->find($data['branch_id']);
            if ($branch && !empty($branch->facility_id)) {
                $this->facilityModel->where('id', $branch->facility_id)
                    ->set('total_courts', 'total_courts + 1', false)
                    ->update();
            }

            // Update indoor/outdoor counts
            if (!empty($data['is_indoor'])) {
                $this->branchModel->where('id', $data['branch_id'])
                    ->set('indoor_courts', 'indoor_courts + 1', false)
                    ->update();
            } else {
                $this->branchModel->where('id', $data['branch_id'])
                    ->set('outdoor_courts', 'outdoor_courts + 1', false)
                    ->update();
            }
        }

        $this->courtModel->db->transComplete();
        return $this->courtModel->db->transStatus() ? (int) $courtId : null;
    }

    public function updateCourt(int $id, array $data): bool
    {
        return $this->courtModel->update($id, $data);
    }

    public function deleteCourt(int $id): bool
    {
        $court = $this->courtModel->find($id);
        if (!$court) return false;

        if ($this->courtModel->hasBookings($id)) return false;

        $this->courtModel->db->transStart();
        $result = $this->courtModel->delete($id);

        if ($result && !empty($court->branch_id)) {
            $this->branchModel->where('id', $court->branch_id)
                ->set('total_courts', 'total_courts - 1', false)
                ->update();

            if ($court->is_indoor) {
                $this->branchModel->where('id', $court->branch_id)
                    ->set('indoor_courts', 'indoor_courts - 1', false)
                    ->update();
            } else {
                $this->branchModel->where('id', $court->branch_id)
                    ->set('outdoor_courts', 'outdoor_courts - 1', false)
                    ->update();
            }

            $branch = $this->branchModel->find($court->branch_id);
            if ($branch && !empty($branch->facility_id)) {
                $this->facilityModel->where('id', $branch->facility_id)
                    ->set('total_courts', 'total_courts - 1', false)
                    ->update();
            }
        }

        $this->courtModel->db->transComplete();
        return $result;
    }

    public function changeCourtStatus(int $courtId, string $statusCode): bool
    {
        $court = $this->courtModel->find($courtId);
        $tenantId = $court->tenant_id ?? current_tenant_id();
        $status = $tenantId ? $this->courtStatusModel->getByCode($statusCode, (int) $tenantId) : null;
        $data = ['status' => $statusCode];
        if ($status) {
            $data['status_id'] = $status->id;
        }
        return $this->courtModel->update($courtId, $data);
    }

    public function getCourtGridByBranch(int $branchId, array $filters = [])
    {
        $courts = $this->courtModel->getByBranch($branchId, $filters);

        // Attach active sessions and status info
        $activeSessions = $this->courtSessionModel->getActiveSessions($branchId);
        $sessionMap = [];
        foreach ($activeSessions as $session) {
            $sessionMap[$session->court_id] = $session;
        }

        $branch = $this->branchModel->find($branchId);
        $tenantId = $branch->tenant_id ?? current_tenant_id();
        $statuses = $tenantId ? $this->courtStatusModel->getActive((int) $tenantId) : [];
        $statusMap = [];
        foreach ($statuses as $s) {
            $statusMap[$s->code] = $s;
        }

        $grouped = [];
        foreach ($courts as $court) {
            $floor = $court->floor ?? 1;
            if (!isset($grouped[$floor])) {
                $grouped[$floor] = ['floor' => $floor, 'courts' => []];
            }

            $courtArray = (array) $court;
            $courtArray['active_session'] = $sessionMap[$court->id] ?? null;
            $courtArray['status_info'] = $statusMap[$court->status] ?? null;
            $courtArray['is_occupied'] = isset($sessionMap[$court->id]);
            $courtArray['remaining_minutes'] = $sessionMap[$court->id]
                ? $sessionMap[$court->id]->getRemainingMinutes()
                : 0;

            $grouped[$floor]['courts'][] = (object) $courtArray;
        }

        ksort($grouped);
        return $grouped;
    }

    public function getAvailableCourts(int $branchId, ?string $date = null,
                                       ?string $startTime = null, ?string $endTime = null)
    {
        return $this->courtModel->getAvailable($branchId, $date, $startTime, $endTime);
    }

    public function isCourtCodeUnique(string $code, int $branchId, ?int $excludeId = null): bool
    {
        return $this->courtModel->isCodeUnique($code, $branchId, $excludeId);
    }

    // ========== COURT IMAGES ==========

    public function getCourtImages(int $courtId)
    {
        return $this->courtImageModel->getByCourt($courtId);
    }

    public function getPrimaryImage(int $courtId)
    {
        return $this->courtImageModel->getPrimary($courtId);
    }

    public function uploadCourtImage(int $courtId, array $fileData): ?int
    {
        $existing = $this->courtImageModel->getByCourt($courtId);
        $isPrimary = empty($existing) ? 1 : 0;

        return $this->courtImageModel->insert([
            'tenant_id'  => $fileData['tenant_id'],
            'court_id'   => $courtId,
            'file_path'  => $fileData['file_path'],
            'is_primary' => $isPrimary,
            'sort_order' => count($existing) + 1,
            'created_by' => $fileData['created_by'] ?? null,
        ]);
    }

    public function deleteCourtImage(int $imageId): bool
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

    // ========== COURT MAINTENANCE ==========

    public function scheduleMaintenance(array $data): ?int
    {
        $this->courtMaintenanceModel->db->transStart();

        $maintenanceId = $this->courtMaintenanceModel->insert($data);
        if (!$maintenanceId) {
            $this->courtMaintenanceModel->db->transRollback();
            return null;
        }

        // Update court status
        $this->courtModel->update($data['court_id'], ['status' => 'maintenance']);

        $this->courtMaintenanceModel->db->transComplete();
        return $this->courtMaintenanceModel->db->transStatus() ? (int) $maintenanceId : null;
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

    public function completeMaintenance(int $id): bool
    {
        $this->courtMaintenanceModel->db->transStart();

        $result = $this->courtMaintenanceModel->update($id, [
            'status'       => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
        ]);

        if ($result) {
            $maintenance = $this->courtMaintenanceModel->find($id);
            if ($maintenance) {
                $this->courtModel->update($maintenance->court_id, ['status' => 'available']);
            }
        }

        $this->courtMaintenanceModel->db->transComplete();
        return $result;
    }

    public function checkMaintenanceConflict(int $courtId, string $startTime,
                                              ?string $endTime = null,
                                              ?int $excludeId = null): bool
    {
        return $this->courtMaintenanceModel->hasConflict($courtId, $startTime, $endTime, $excludeId);
    }

    // ========== COURT DEVICES (IoT) ==========

    public function getDevicesByBranch(int $branchId, array $filters = [])
    {
        return $this->courtDeviceModel->getByBranch($branchId, $filters);
    }

    public function getDevicesByCourt(int $courtId)
    {
        return $this->courtDeviceModel->getByCourt($courtId);
    }

    public function getOnlineDevices(int $branchId)
    {
        return $this->courtDeviceModel->getOnlineDevices($branchId);
    }

    public function createDevice(array $data): ?int
    {
        return $this->courtDeviceModel->insert($data);
    }

    public function updateDevice(int $id, array $data): bool
    {
        return $this->courtDeviceModel->update($id, $data);
    }

    public function deleteDevice(int $id): bool
    {
        return $this->courtDeviceModel->delete($id);
    }

    public function updateDeviceStatus(int $deviceId, string $status, ?string $value = null): bool
    {
        $device = $this->courtDeviceModel->find($deviceId);
        if (!$device) return false;

        $previousValue = $device->last_value;

        $result = $this->courtDeviceModel->updateDeviceStatus($deviceId, $status, $value);

        if ($result) {
            $this->courtDeviceLogModel->log(
                $device->tenant_id,
                $deviceId,
                "status_change:{$status}",
                $value,
                $previousValue,
                'system'
            );
        }

        return $result;
    }

    public function toggleDevice(int $deviceId): bool
    {
        $device = $this->courtDeviceModel->find($deviceId);
        if (!$device) return false;

        $newValue = $device->last_value === 'on' ? 'off' : 'on';
        return $this->updateDeviceStatus($deviceId, $device->status, $newValue);
    }

    public function getDeviceLogs(int $deviceId, int $limit = 50)
    {
        return $this->courtDeviceLogModel->getLogs($deviceId, $limit);
    }

    public function getRecentDeviceActions(int $branchId, int $minutes = 60)
    {
        return $this->courtDeviceLogModel->getRecentActions($branchId, $minutes);
    }

    // ========== COURT SESSIONS (Realtime) ==========

    public function startSession(array $data): ?int
    {
        return $this->courtSessionModel->insert($data);
    }

    public function getActiveSessions(int $branchId)
    {
        return $this->courtSessionModel->getActiveSessions($branchId);
    }

    public function getActiveSessionByCourt(int $courtId)
    {
        return $this->courtSessionModel->getActiveByCourt($courtId);
    }

    public function getTodaySessions(int $branchId, ?int $courtId = null)
    {
        return $this->courtSessionModel->getTodaySessions($branchId, $courtId);
    }

    public function completeSession(int $sessionId): bool
    {
        return $this->courtSessionModel->completeSession($sessionId);
    }

    public function extendSession(int $sessionId, int $extraMinutes): bool
    {
        $session = $this->courtSessionModel->find($sessionId);
        if (!$session) return false;

        $newEnd = date('Y-m-d H:i:s', strtotime($session->expected_end_time . " +{$extraMinutes} minutes"));

        return $this->courtSessionModel->update($sessionId, [
            'expected_end_time' => $newEnd,
            'status'            => 'extended',
            'overtime_minutes'  => ($session->overtime_minutes ?? 0) + $extraMinutes,
        ]);
    }

    public function checkOvertimeSessions(): array
    {
        return $this->courtSessionModel->checkOvertime();
    }

    // ========== REALTIME COURT STATUS ==========

    public function getRealtimeCourtStatus(int $branchId): array
    {
        $courts = $this->courtModel->where('branch_id', $branchId)
            ->whereIn('status', ['available', 'occupied', 'booked', 'maintenance', 'cleaning'])
            ->orderBy('floor', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $activeSessions = $this->courtSessionModel->getActiveSessions($branchId);
        $sessionMap = [];
        foreach ($activeSessions as $session) {
            $sessionMap[$session->court_id] = $session;
        }

        $branch = $this->branchModel->find($branchId);
        $tenantId = $branch->tenant_id ?? current_tenant_id();
        $statuses = $tenantId ? $this->courtStatusModel->getActive((int) $tenantId) : [];
        $statusMap = [];
        foreach ($statuses as $s) {
            $statusMap[$s->code] = $s;
        }

        $result = [];
        foreach ($courts as $court) {
            $session = $sessionMap[$court->id] ?? null;
            $statusInfo = $statusMap[$court->status] ?? null;

            $result[] = [
                'id'                => $court->id,
                'code'              => $court->code,
                'name'              => $court->getName(),
                'floor'             => $court->floor,
                'status'            => $court->status,
                'status_color'      => $statusInfo->color ?? '#6c757d',
                'status_icon'       => $statusInfo->icon ?? 'bi-question-circle',
                'status_name'       => $statusInfo ? $statusInfo->getName() : $court->status,
                'is_bookable'       => $statusInfo->is_bookable ?? true,
                'coordinates_x'     => $court->coordinates_x,
                'coordinates_y'     => $court->coordinates_y,
                'rotation'          => $court->rotation,
                'color_scheme'      => $court->color_scheme,
                'surface_type'      => $court->surface_type,
                'is_indoor'         => $court->is_indoor,
                'has_light'         => $court->has_light,
                'has_camera'        => $court->has_camera,
                'active_session'    => $session ? [
                    'id'                => $session->id,
                    'start_time'        => $session->start_time,
                    'expected_end_time' => $session->expected_end_time,
                    'player_count'      => $session->player_count,
                    'player_names'      => $session->player_names,
                    'remaining_minutes' => $session->getRemainingMinutes(),
                    'is_overtime'       => $session->isOvertime(),
                    'overtime_minutes'  => $session->overtime_minutes,
                    'delay_minutes'     => $session->delay_minutes,
                ] : null,
                'devices' => $this->getDevicesByCourt($court->id),
            ];
        }

        return $result;
    }

    // ========== COURT TIMELINE ==========

    public function getCourtTimeline(int $branchId, string $date): array
    {
        $courts = $this->courtModel->where('branch_id', $branchId)
            ->where('status !=', 'inactive')
            ->orderBy('floor', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $sessions = $this->courtSessionModel->getTodaySessions($branchId);
        $bookings = model('BookingModel')
            ->where('branch_id', $branchId)
            ->where('DATE(booking_date)', $date)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->findAll();

        $maintenance = $this->courtMaintenanceModel
            ->where('branch_id', $branchId)
            ->where('DATE(start_time) <=', $date)
            ->where('DATE(COALESCE(end_time, start_time)) >=', $date)
            ->findAll();

        $timeline = [];
        foreach ($courts as $court) {
            $courtSessions = array_filter($sessions, fn($s) => $s->court_id === $court->id);
            $courtBookings = array_filter($bookings, function ($booking) use ($court) {
                $items = model('App\Models\BookingItemModel')->getByBooking($booking->id);
                foreach ($items as $item) {
                    if ((int) $item->court_id === (int) $court->id) {
                        return true;
                    }
                }
                return false;
            });
            $courtMaintenance = array_filter($maintenance, fn($m) => $m->court_id === $court->id);

            $slots = [];
            foreach ($courtSessions as $session) {
                $slots[] = [
                    'type'   => 'session',
                    'start'  => $session->start_time,
                    'end'    => $session->actual_end_time ?? $session->expected_end_time,
                    'status' => $session->status,
                    'data'   => $session,
                ];
            }
            foreach ($courtBookings as $booking) {
                $slots[] = [
                    'type'   => 'booking',
                    'start'  => $booking->booking_date . ' ' . $booking->start_time,
                    'end'    => $booking->booking_date . ' ' . $booking->end_time,
                    'status' => $booking->status,
                    'data'   => $booking,
                ];
            }
            foreach ($courtMaintenance as $maint) {
                $slots[] = [
                    'type'   => 'maintenance',
                    'start'  => $maint->start_time,
                    'end'    => $maint->end_time ?? $maint->start_time,
                    'status' => $maint->status,
                    'data'   => $maint,
                ];
            }

            usort($slots, fn($a, $b) => strtotime($a['start']) - strtotime($b['start']));

            $timeline[] = [
                'court' => $court,
                'slots' => $slots,
            ];
        }

        return $timeline;
    }

    // ========== UTILIZATION & REPORTS ==========

    public function getUtilizationStats(int $branchId, string $date): array
    {
        return $this->courtSessionModel->getUtilizationStats($branchId, $date);
    }

    public function getBranchReport(int $branchId, string $fromDate, string $toDate): array
    {
        $branch = $this->branchModel->find($branchId);
        if (!$branch) return [];

        $totalCourts = $this->courtModel->where('branch_id', $branchId)
            ->where('status !=', 'inactive')
            ->countAllResults();

        // Daily stats
        $dailyStats = [];
        $period = new \DatePeriod(
            new \DateTime($fromDate),
            new \DateInterval('P1D'),
            (new \DateTime($toDate))->modify('+1 day')
        );

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $stats = $this->getUtilizationStats($branchId, $dateStr);

            $totalMinutes = 0;
            $sessionCount = 0;
            foreach ($stats['sessions'] as $s) {
                $totalMinutes += $s->total_minutes;
                $sessionCount += $s->session_count;
            }

            $availableMinutes = $totalCourts * 16 * 60; // 16 hours operation
            $utilizationRate = $availableMinutes > 0
                ? round(($totalMinutes / $availableMinutes) * 100, 2)
                : 0;

            $dailyStats[] = [
                'date'             => $dateStr,
                'day_of_week'      => $date->format('l'),
                'session_count'    => $sessionCount,
                'total_minutes'    => $totalMinutes,
                'utilization_rate' => $utilizationRate,
                'avg_session_time' => $sessionCount > 0 ? round($totalMinutes / $sessionCount) : 0,
            ];
        }

        // Peak hours analysis
        $peakHours = $this->getPeakHours($branchId, $fromDate, $toDate);

        // Court ranking
        $courtRanking = $this->getCourtRanking($branchId, $fromDate, $toDate);

        return [
            'branch'         => $branch,
            'total_courts'   => $totalCourts,
            'period'         => ['from' => $fromDate, 'to' => $toDate],
            'daily_stats'    => $dailyStats,
            'peak_hours'     => $peakHours,
            'court_ranking'  => $courtRanking,
            'avg_utilization'=> count($dailyStats) > 0
                ? round(array_sum(array_column($dailyStats, 'utilization_rate')) / count($dailyStats), 2)
                : 0,
        ];
    }

    public function getPeakHours(int $branchId, string $fromDate, string $toDate): array
    {
        $sessions = $this->courtSessionModel
            ->select("HOUR(start_time) as hour, COUNT(*) as count,
                      SUM(TIMESTAMPDIFF(MINUTE, start_time,
                          COALESCE(actual_end_time, expected_end_time))) as total_minutes")
            ->where('branch_id', $branchId)
            ->where('DATE(start_time) >=', $fromDate)
            ->where('DATE(start_time) <=', $toDate)
            ->groupBy('HOUR(start_time)')
            ->orderBy('count', 'DESC')
            ->findAll();

        $peakHours = [];
        foreach ($sessions as $s) {
            $peakHours[] = [
                'hour'           => sprintf('%02d:00-%02d:00', $s->hour, $s->hour + 1),
                'session_count'  => $s->count,
                'total_minutes'  => $s->total_minutes,
            ];
        }

        return $peakHours;
    }

    public function getCourtRanking(int $branchId, string $fromDate, string $toDate): array
    {
        return $this->courtSessionModel
            ->select('court_sessions.court_id, courts.code, courts.name_vi, courts.name_en,
                      COUNT(*) as session_count,
                      SUM(TIMESTAMPDIFF(MINUTE, start_time,
                          COALESCE(actual_end_time, expected_end_time))) as total_minutes,
                      SUM(CASE WHEN is_overtime = 1 THEN 1 ELSE 0 END) as overtime_count')
            ->join('courts', 'courts.id = court_sessions.court_id')
            ->where('court_sessions.branch_id', $branchId)
            ->where('DATE(court_sessions.start_time) >=', $fromDate)
            ->where('DATE(court_sessions.start_time) <=', $toDate)
            ->groupBy('court_sessions.court_id')
            ->orderBy('session_count', 'DESC')
            ->findAll();
    }

    public function getRevenueByCourt(int $branchId, string $fromDate, string $toDate): array
    {
        $branch = $this->branchModel->find($branchId);
        if (!$branch) {
            return [];
        }

        return model('BookingItemModel')
            ->select('booking_items.court_id, courts.code, courts.name_vi, courts.name_en,
                      COUNT(DISTINCT booking_items.booking_id) as booking_count,
                      SUM(booking_items.price) as total_revenue')
            ->join('courts', 'courts.id = booking_items.court_id')
            ->join('bookings', 'bookings.id = booking_items.booking_id')
            ->where('bookings.branch_id', $branchId)
            ->where('bookings.tenant_id', $branch->tenant_id)
            ->where('courts.branch_id', $branchId)
            ->where('courts.tenant_id', $branch->tenant_id)
            ->where('booking_items.status', 'active')
            ->where('bookings.deleted_at', null)
            ->where('DATE(bookings.booking_date) >=', $fromDate)
            ->where('DATE(bookings.booking_date) <=', $toDate)
            ->whereIn('bookings.status', ['completed', 'checked_in', 'paid'])
            ->groupBy('booking_items.court_id')
            ->orderBy('total_revenue', 'DESC')
            ->findAll();
    }

    // ========== BRANCH MEDIA ==========

    public function uploadBranchMedia(int $branchId, array $data): ?int
    {
        $existing = $this->branchMediaModel->getByBranch($branchId);
        $data['is_primary'] = empty($existing) ? 1 : 0;
        $data['sort_order'] = count($existing) + 1;
        return $this->branchMediaModel->insert($data);
    }

    public function deleteBranchMedia(int $id): bool
    {
        return $this->branchMediaModel->delete($id);
    }

    public function setPrimaryBranchMedia(int $branchId, int $mediaId): bool
    {
        $this->branchMediaModel->db->transStart();
        $this->branchMediaModel->resetPrimary($branchId);
        $result = $this->branchMediaModel->update($mediaId, ['is_primary' => 1]);
        $this->branchMediaModel->db->transComplete();
        return $result;
    }
}
