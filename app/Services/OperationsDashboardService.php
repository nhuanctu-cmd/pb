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

    public function get(int $tenantId, ?string $date = null): array
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

        $upcoming = $this->bookingModel->where('tenant_id', $tenantId)
            ->where('booking_date', $date)->where('deleted_at', null)
            ->whereIn('status', ['reserved', 'paid', 'checked_in', 'in_progress'])
            ->orderBy('start_time', 'ASC')->findAll(10);

        $courtRows = $this->courtModel->where('tenant_id', $tenantId)->where('deleted_at', null)
            ->select("status, COUNT(*) AS total")->groupBy('status')->findAll();
        $courts = [];
        foreach ($courtRows as $row) {
            $courts[(string) $row->status] = (int) $row->total;
        }

        $walkIns = [];
        if ($db->tableExists('walk_in_sessions')) {
            $walkIns = $db->table('walk_in_sessions')->select('status, COUNT(*) AS total')
                ->where('tenant_id', $tenantId)->where('created_at >=', $date . ' 00:00:00')->where('created_at <=', $date . ' 23:59:59')->where('deleted_at', null)
                ->groupBy('status')->get()->getResultArray();
        }
        $waitlist = [];
        if ($db->tableExists('booking_waitlist')) {
            $waitlist = $db->table('booking_waitlist')->select('status, COUNT(*) AS total')
                ->where('tenant_id', $tenantId)->where('booking_date', $date)->where('deleted_at', null)
                ->groupBy('status')->get()->getResultArray();
        }

        $coaching = [];
        if ($db->tableExists('coaching_sessions')) {
            $coaching = $db->table('coaching_sessions')->select("COUNT(*) AS total, SUM(status IN ('open','full','started')) AS active, SUM(status = 'completed') AS completed")
                ->where('tenant_id', $tenantId)->where('session_date', $date)->where('deleted_at', null)->get()->getRowArray() ?: [];
        }
        $competition = [];
        if ($db->tableExists('competition_events')) {
            $competition = $db->table('competition_events')->select("COUNT(*) AS total, SUM(status = 'running') AS running")
                ->where('tenant_id', $tenantId)->where('deleted_at', null)->get()->getRowArray() ?: [];
        }
        $commerce = [];
        if ($db->tableExists('invoices')) {
            $commerce = $db->table('invoices')->select('COUNT(*) AS invoices, COALESCE(SUM(total_amount), 0) AS billed, COALESCE(SUM(paid_amount), 0) AS collected, COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding')
                ->where('tenant_id', $tenantId)->where('DATE(created_at)', $date, false)->whereNotIn('status', ['cancelled', 'refunded'])->get()->getRowArray() ?: [];
        }
        return ['date' => $date, 'summary' => $summary, 'upcoming' => $upcoming, 'courts' => $courts, 'walk_ins' => $this->toStatusMap($walkIns), 'waitlist' => $this->toStatusMap($waitlist), 'coaching' => $coaching, 'competition' => $competition, 'commerce' => $commerce];
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
