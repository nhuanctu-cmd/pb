<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\ClubModel;
use App\Models\CourtModel;
use App\Models\FacilityModel;
use Config\Database;

class VenueOperationsService
{
    public function overview(int $tenantId): array
    {
        $db = Database::connect();
        $facilities = model(FacilityModel::class)->getByTenant($tenantId);
        $branches = model(BranchModel::class)->getByTenant($tenantId);
        $clubs = model(ClubModel::class)->getByTenant($tenantId);
        $courts = model(CourtModel::class)->getByTenant($tenantId);

        $facilityRows = [];
        foreach ($facilities as $facility) {
            $facilityBranches = array_values(array_filter($branches, static fn ($branch) => (int) ($branch->facility_id ?? 0) === (int) $facility->id));
            $branchIds = array_map(static fn ($branch) => (int) $branch->id, $facilityBranches);
            $facilityCourts = array_values(array_filter($courts, static fn ($court) => in_array((int) $court->branch_id, $branchIds, true)));
            $assignedClubs = [];
            if ($db->tableExists('facility_club_assignments')) {
                $assignedClubs = $db->table('facility_club_assignments a')
                    ->select('c.id, c.name_vi, a.is_primary')
                    ->join('clubs c', 'c.id = a.club_id AND c.tenant_id = a.tenant_id', 'left')
                    ->where('a.tenant_id', $tenantId)->where('a.facility_id', (int) $facility->id)
                    ->where('a.status', 'active')->where('c.deleted_at', null)
                    ->orderBy('a.is_primary', 'DESC')->orderBy('c.name_vi', 'ASC')->get()->getResult();
            }
            $facilityRows[] = [
                'facility' => $facility,
                'branches' => $facilityBranches,
                'courts' => $facilityCourts,
                'clubs' => $assignedClubs,
                'active_courts' => count(array_filter($facilityCourts, static fn ($court) => ($court->status ?? '') === 'available')),
                'maintenance_courts' => count(array_filter($facilityCourts, static fn ($court) => ($court->status ?? '') === 'maintenance')),
            ];
        }

        $todayBookings = 0;
        if ($db->tableExists('bookings')) {
            $todayBookings = (int) $db->table('bookings')
                ->where('tenant_id', $tenantId)->where('booking_date', date('Y-m-d'))
                ->whereNotIn('status', ['cancelled', 'rejected'])->countAllResults();
        }

        $memberCount = 0;
        if ($db->tableExists('player_club_memberships')) {
            $memberCount = (int) $db->table('player_club_memberships m')
                ->join('clubs c', 'c.id = m.club_id AND c.tenant_id = m.tenant_id', 'inner')
                ->where('m.tenant_id', $tenantId)->whereIn('m.status', ['active', 'approved'])->countAllResults();
        }

        return [
            'tenant_id' => $tenantId,
            'facilities' => $facilityRows,
            'branches' => $branches,
            'clubs' => $clubs,
            'courts' => $courts,
            'stats' => [
                'facilities' => count($facilities),
                'branches' => count($branches),
                'courts' => count($courts),
                'available_courts' => count(array_filter($courts, static fn ($court) => ($court->status ?? '') === 'available')),
                'maintenance_courts' => count(array_filter($courts, static fn ($court) => ($court->status ?? '') === 'maintenance')),
                'clubs' => count($clubs),
                'club_members' => $memberCount,
                'today_bookings' => $todayBookings,
            ],
        ];
    }
}
