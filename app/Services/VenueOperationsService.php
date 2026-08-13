<?php

namespace App\Services;

use App\Models\BranchModel;
use App\Models\ClubModel;
use App\Models\CourtModel;
use App\Models\FacilityModel;
use Config\Database;

class VenueOperationsService
{
    /**
     * Day-of-operation payload used by the venue control room and lightweight
     * polling clients. Every query is tenant scoped; branch is optional.
     */
    public function controlRoom(int $tenantId, ?int $branchId = null, ?string $date = null): array
    {
        $db = Database::connect();
        $date = $this->normalizeDate($date);
        $now = date('H:i:s');

        $courtBuilder = $db->table('courts c')
            ->select('c.id, c.branch_id, c.code, c.name_vi, c.status, b.name AS branch_name')
            ->join('branches b', 'b.id = c.branch_id', 'left')
            ->where('c.tenant_id', $tenantId)
            ->where('c.deleted_at', null)
            ->orderBy('b.name', 'ASC')->orderBy('c.code', 'ASC');
        if ($branchId) {
            $courtBuilder->where('c.branch_id', $branchId);
        }
        $courts = $courtBuilder->get()->getResult();

        $bookings = [];
        if ($db->tableExists('bookings') && $db->tableExists('booking_items')) {
            $bookingBuilder = $db->table('bookings b')
                ->select('b.id, b.branch_id, b.booking_code, b.customer_name, b.customer_phone, b.status, b.booking_date, b.start_time, b.end_time, bi.court_id')
                ->join('booking_items bi', 'bi.booking_id = b.id AND bi.status = "active"', 'inner')
                ->where('b.tenant_id', $tenantId)->where('b.booking_date', $date)
                ->where('b.deleted_at', null)
                ->whereNotIn('b.status', ['cancelled', 'refunded', 'expired', 'no_show'])
                ->orderBy('b.start_time', 'ASC');
            if ($branchId) {
                $bookingBuilder->where('b.branch_id', $branchId);
            }
            $bookings = $bookingBuilder->get()->getResult();
        }

        $byCourt = [];
        $late = [];
        $unchecked = [];
        $next = [];
        foreach ($bookings as $booking) {
            $courtId = (int) $booking->court_id;
            $byCourt[$courtId][] = $booking;
            $status = (string) $booking->status;
            $needsCheckin = in_array($status, ['pending', 'hold', 'reserved', 'paid'], true);
            if ($needsCheckin && (string) $booking->start_time <= $now) {
                $late[] = $booking;
            }
            if ($needsCheckin) {
                $unchecked[] = $booking;
            }
            if ((string) $booking->start_time > $now) {
                $next[] = $booking;
            }
        }

        $courtBoard = [];
        foreach ($courts as $court) {
            $timeline = $byCourt[(int) $court->id] ?? [];
            $live = null;
            $nextBooking = null;
            foreach ($timeline as $booking) {
                $isWithinSlot = (string) $booking->start_time <= $now && (string) $booking->end_time > $now;
                if ($isWithinSlot && in_array((string) $booking->status, ['checked_in', 'in_progress'], true)) {
                    $live = $booking;
                    break;
                }
                if (! $nextBooking && (string) $booking->start_time > $now) {
                    $nextBooking = $booking;
                }
            }
            $state = $live ? 'live' : (($court->status ?? '') === 'maintenance' ? 'maintenance' : ($nextBooking ? 'next' : 'available'));
            $courtBoard[] = ['court' => $court, 'state' => $state, 'live' => $live, 'next' => $nextBooking];
        }

        return [
            'date' => $date,
            'generated_at' => date('c'),
            'branch_id' => $branchId,
            'courts' => $courtBoard,
            'late' => array_slice($late, 0, 20),
            'unchecked' => array_slice($unchecked, 0, 20),
            'next' => array_slice($next, 0, 12),
            'stats' => [
                'live' => count(array_filter($courtBoard, static fn (array $row) => $row['state'] === 'live')),
                'available' => count(array_filter($courtBoard, static fn (array $row) => $row['state'] === 'available')),
                'late' => count($late),
                'unchecked' => count($unchecked),
            ],
        ];
    }

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

    private function normalizeDate(?string $date): string
    {
        $timestamp = $date ? strtotime($date) : false;
        return $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d');
    }
}
