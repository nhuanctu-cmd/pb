<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\CourtModel;

class OperationsDashboardService
{
    private BookingModel $bookingModel;
    private CourtModel $courtModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->courtModel = new CourtModel();
    }

    public function get(int $tenantId, ?string $date = null, ?int $branchId = null): array
    {
        $date = self::normalizeDate($date);
        if (!$tenantId) {
            return ['date' => $date, 'summary' => [], 'upcoming' => [], 'courts' => [], 'walk_ins' => [], 'waitlist' => [], 'commerce' => []];
        }

        $db = \Config\Database::connect();
        $summary = $db->table('bookings')
            ->select("COUNT(*) AS total, SUM(CASE WHEN status IN ('reserved','paid') THEN 1 ELSE 0 END) AS confirmed, SUM(CASE WHEN status IN ('checked_in','in_progress') THEN 1 ELSE 0 END) AS playing, SUM(CASE WHEN status IN ('cancelled','refunded','expired') THEN 1 ELSE 0 END) AS cancelled, COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','expired') THEN total_amount ELSE 0 END), 0) AS revenue, COALESCE(SUM(paid_amount), 0) AS collected")
            ->where('tenant_id', $tenantId)->where('booking_date', $date)->where('deleted_at', null)
            ->get()->getRowArray() ?: [];
        if ($branchId && $db->fieldExists('branch_id', 'bookings')) {
            $summary = $db->table('bookings')
                ->select("COUNT(*) AS total, SUM(CASE WHEN status IN ('reserved','paid') THEN 1 ELSE 0 END) AS confirmed, SUM(CASE WHEN status IN ('checked_in','in_progress') THEN 1 ELSE 0 END) AS playing, SUM(CASE WHEN status IN ('cancelled','refunded','expired') THEN 1 ELSE 0 END) AS cancelled, COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','expired') THEN total_amount ELSE 0 END), 0) AS revenue, COALESCE(SUM(paid_amount), 0) AS collected")
                ->where('tenant_id', $tenantId)->where('branch_id', $branchId)->where('booking_date', $date)->where('deleted_at', null)
                ->get()->getRowArray() ?: [];
        }

        $upcoming = $this->bookingModel->where('tenant_id', $tenantId)
            ->where('booking_date', $date)->where('deleted_at', null)
            ->whereIn('status', ['reserved', 'paid', 'checked_in', 'in_progress'])
            ->orderBy('start_time', 'ASC')->findAll(10);
        if ($branchId && $db->fieldExists('branch_id', 'bookings')) {
            $upcoming = $this->bookingModel->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)->where('booking_date', $date)->where('deleted_at', null)
                ->whereIn('status', ['reserved', 'paid', 'checked_in', 'in_progress'])
                ->orderBy('start_time', 'ASC')->findAll(10);
        }

        $courtRows = $this->courtModel->where('tenant_id', $tenantId)->where('deleted_at', null)
            ->select("status, COUNT(*) AS total")->groupBy('status')->findAll();
        if ($branchId && $db->fieldExists('branch_id', 'courts')) {
            $courtRows = $this->courtModel->where('tenant_id', $tenantId)->where('branch_id', $branchId)->where('deleted_at', null)
                ->select("status, COUNT(*) AS total")->groupBy('status')->findAll();
        }
        $courts = [];
        foreach ($courtRows as $row) {
            $courts[(string) $row->status] = (int) $row->total;
        }

        $walkIns = [];
        if ($db->tableExists('walk_in_sessions')) {
            $walkInQuery = $db->table('walk_in_sessions')->select('status, COUNT(*) AS total')
                ->where('tenant_id', $tenantId)->where('created_at >=', $date . ' 00:00:00')->where('created_at <=', $date . ' 23:59:59')->where('deleted_at', null);
            if ($branchId && $db->fieldExists('branch_id', 'walk_in_sessions')) {
                $walkInQuery->where('branch_id', $branchId);
            }
            $walkIns = $walkInQuery
                ->groupBy('status')->get()->getResultArray();
        }
        $waitlist = [];
        if ($db->tableExists('booking_waitlist')) {
            $waitlistQuery = $db->table('booking_waitlist')->select('status, COUNT(*) AS total')
                ->where('tenant_id', $tenantId)->where('booking_date', $date)->where('deleted_at', null);
            if ($branchId && $db->fieldExists('branch_id', 'booking_waitlist')) {
                $waitlistQuery->where('branch_id', $branchId);
            }
            $waitlist = $waitlistQuery
                ->groupBy('status')->get()->getResultArray();
        }

        $coaching = [];
        if ($db->tableExists('coaching_sessions')) {
            $coachingQuery = $db->table('coaching_sessions')->select("COUNT(*) AS total, SUM(status IN ('open','full','started')) AS active, SUM(status = 'completed') AS completed")
                ->where('tenant_id', $tenantId)->where('session_date', $date)->where('deleted_at', null);
            if ($branchId && $db->fieldExists('branch_id', 'coaching_sessions')) {
                $coachingQuery->where('branch_id', $branchId);
            }
            $coaching = $coachingQuery->get()->getRowArray() ?: [];
        }
        $competition = [];
        if ($db->tableExists('competition_events')) {
            $competitionQuery = $db->table('competition_events')->select("COUNT(*) AS total, SUM(status = 'running') AS running")
                ->where('tenant_id', $tenantId)->where('deleted_at', null);
            if ($branchId && $db->fieldExists('branch_id', 'competition_events')) {
                $competitionQuery->where('branch_id', $branchId);
            }
            $competition = $competitionQuery->get()->getRowArray() ?: [];
        }
        $commerce = [];
        if ($db->tableExists('invoices')) {
            $commerceQuery = $db->table('invoices')->select('COUNT(*) AS invoices, COALESCE(SUM(total_amount), 0) AS billed, COALESCE(SUM(paid_amount), 0) AS collected, COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding')
                ->where('tenant_id', $tenantId)->where('DATE(created_at)', $date, false)->whereNotIn('status', ['cancelled', 'refunded']);
            if ($branchId && $db->fieldExists('branch_id', 'invoices')) {
                $commerceQuery->where('branch_id', $branchId);
            }
            $commerce = $commerceQuery->get()->getRowArray() ?: [];
        }

        // Lũy kế toàn tenant giúp dashboard không bị hiểu nhầm là chỉ có dữ
        // liệu trong ngày đang chọn. Các chỉ số vận hành phía trên vẫn là
        // snapshot theo ngày để phục vụ quầy.
        $totals = [
            'players' => 0,
            'courts' => 0,
            'bookings' => 0,
            'memberships' => 0,
            'tournaments' => 0,
            'pos_orders' => 0,
            'open_play_sessions' => 0,
            'coaching_sessions' => 0,
            'community_posts' => 0,
            'invoices' => 0,
            'billed' => 0,
            'collected' => 0,
            'outstanding' => 0,
        ];
        foreach ([
            'players' => 'players',
            'courts' => 'courts',
            'bookings' => 'bookings',
            'memberships' => 'memberships',
            'tournaments' => 'tournaments',
            'pos_orders' => 'pos_orders',
            'open_play_sessions' => 'open_play_sessions',
            'coaching_sessions' => 'coaching_sessions',
            'community_posts' => 'community_posts',
        ] as $key => $table) {
            if ($db->tableExists($table)) {
                $builder = $db->table($table)->where('tenant_id', $tenantId);
                if ($db->fieldExists('deleted_at', $table)) {
                    $builder->where('deleted_at', null);
                }
                if ($branchId && $db->fieldExists('branch_id', $table)) {
                    $builder->where('branch_id', $branchId);
                } elseif ($branchId && $table === 'players' && $db->fieldExists('home_branch_id', $table)) {
                    $builder->where('home_branch_id', $branchId);
                }
                $totals[$key] = (int) ($builder->countAllResults() ?: 0);
            }
        }
        if ($db->tableExists('invoices')) {
            $invoiceTotalsQuery = $db->table('invoices')
                ->select('COUNT(*) AS invoices, COALESCE(SUM(total_amount), 0) AS billed, COALESCE(SUM(paid_amount), 0) AS collected, COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding')
                ->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['cancelled', 'refunded']);
            if ($branchId && $db->fieldExists('branch_id', 'invoices')) {
                $invoiceTotalsQuery->where('branch_id', $branchId);
            }
            $invoiceTotals = $invoiceTotalsQuery->get()->getRowArray() ?: [];
            $totals = array_merge($totals, [
                'invoices' => (int) ($invoiceTotals['invoices'] ?? 0),
                'billed' => (float) ($invoiceTotals['billed'] ?? 0),
                'collected' => (float) ($invoiceTotals['collected'] ?? 0),
                'outstanding' => (float) ($invoiceTotals['outstanding'] ?? 0),
            ]);
        }

        return ['date' => $date, 'summary' => $summary, 'upcoming' => $upcoming, 'courts' => $courts, 'walk_ins' => $this->toStatusMap($walkIns), 'waitlist' => $this->toStatusMap($waitlist), 'coaching' => $coaching, 'competition' => $competition, 'commerce' => $commerce, 'totals' => $totals];
    }

    public static function normalizeDate(?string $date): string
    {
        $date = trim((string) $date);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date ? $date : date('Y-m-d');
    }

    private function toStatusMap(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['status']] = (int) $row['total'];
        }
        return $result;
    }
}
