<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\OperationsDashboardService;
use App\Services\MembershipService;

class OwnerDashboardController extends BaseController
{
    public function index()
    {
        $tenantId = (int) current_tenant_id();
        if ($tenantId <= 0) {
            return redirect()->to('/admin/tenants/select')->with('warning', 'Vui lòng chọn sân để xem Owner dashboard.');
        }

        $rawDate = $this->request->getGet('date');
        $date = OperationsDashboardService::normalizeDate($rawDate);
        $branchId = (int) current_branch_id();
        $scopeBranchId = is_superadmin() ? ((int) $this->request->getGet('branch_id') ?: null) : ($branchId ?: null);

        $operations = service('operationsDashboardService')->get($tenantId, $date, $scopeBranchId);
        $membershipService = new MembershipService();
        $db = \Config\Database::connect();

        $monthStart = date('Y-m-01', strtotime($date));
        $yearStart = date('Y-01-01', strtotime($date));

        $mtd = $this->getRevenueSummary($db, $tenantId, $scopeBranchId, $monthStart, $date);
        $ytd = $this->getRevenueSummary($db, $tenantId, $scopeBranchId, $yearStart, $date);
        $offPeakCustomers = $this->getTopCustomers($db, $tenantId, $scopeBranchId, $monthStart, $date, true, 6);
        $topCustomers = $this->getTopCustomers($db, $tenantId, $scopeBranchId, $monthStart, $date, false, 6);
        $discrepancyAlerts = $this->getClosingDiscrepancies($db, $tenantId, $scopeBranchId, 30);
        $creditAlerts = $this->getCreditAlerts($db, $tenantId, $scopeBranchId, 12);

        $renewals = $membershipService->getRenewalCandidatesFiltered($tenantId, 30, null, 'active', '');
        $soonExpired = [];
        $expired = [];
        foreach ($renewals as $item) {
            $days = (int) ($item->remaining_days ?? 0);
            if ($days < 0) {
                $expired[] = $item;
            } elseif ($days <= 7) {
                $soonExpired[] = $item;
            }
            if (count($soonExpired) >= 12 && count($expired) >= 12) {
                break;
            }
        }

        $campaigns = $db->table('crm_campaigns')->where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->get()->getResult();
        $draftCampaigns = array_filter($campaigns, static fn ($campaign) => in_array((string) $campaign->status, ['draft', 'scheduled'], true));

        $this->viewData = [
            'pageTitle' => 'Owner Dashboard',
            'date' => $date,
            'operations' => $operations,
            'courts' => $operations['courts'] ?? [],
            'renewals' => array_slice($renewals, 0, 10),
            'expired' => array_slice($expired, 0, 5),
            'soonExpired' => array_slice($soonExpired, 0, 10),
            'draftCampaigns' => $draftCampaigns,
            'scopeBranchId' => $scopeBranchId,
            'branches' => $scopeBranchId ? $db->table('branches')->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('name', 'ASC')->get()->getResult() : $db->table('branches')->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('name', 'ASC')->get()->getResult(),
            'mtd' => $mtd,
            'ytd' => $ytd,
            'topCustomers' => $topCustomers,
            'offPeakCustomers' => $offPeakCustomers,
            'discrepancyAlerts' => $discrepancyAlerts,
            'creditAlerts' => $creditAlerts,
        ];

        return $this->render('admin/owner_dashboard/index', $this->viewData);
    }

    private function getRevenueSummary(\CodeIgniter\Database\ConnectionInterface $db, int $tenantId, ?int $branchId, string $startDate, string $endDate): array
    {
        $bookingRow = [];
        $invoiceRow = [];
        $posRow = [];

        if ($db->tableExists('bookings')) {
            $bookingQuery = $this->applyScope($db->table('bookings b'), $tenantId, $branchId, 'b', 'bookings')
                ->select("COALESCE(SUM(b.total_amount), 0) AS billed, COALESCE(SUM(COALESCE(b.paid_amount, b.total_amount)), 0) AS collected, COUNT(*) AS booking_count")
                ->where('b.booking_date >=', $startDate)
                ->where('b.booking_date <=', $endDate)
                ->whereNotIn('b.status', ['cancelled', 'refunded', 'expired']);
            $bookingRow = (array) $bookingQuery->get()->getRowArray();
        }

        if ($db->tableExists('invoices')) {
            $invoiceQuery = $this->applyScope($db->table('invoices i'), $tenantId, $branchId, 'i', 'invoices')
                ->select("COALESCE(SUM(i.total_amount), 0) AS billed, COALESCE(SUM(i.paid_amount), 0) AS collected, COUNT(*) AS invoice_count")
                ->where('DATE(i.created_at) >=', $startDate, false)
                ->where('DATE(i.created_at) <=', $endDate, false)
                ->whereNotIn('i.status', ['cancelled', 'refunded']);
            $invoiceRow = (array) $invoiceQuery->get()->getRowArray();
        }

        if ($db->tableExists('pos_orders')) {
            $posQuery = $this->applyScope($db->table('pos_orders p'), $tenantId, $branchId, 'p', 'pos_orders')
                ->select("COALESCE(SUM(p.total_amount), 0) AS billed, COALESCE(SUM(p.paid_amount), 0) AS collected, COUNT(*) AS pos_count")
                ->where('DATE(p.created_at) >=', $startDate, false)
                ->where('DATE(p.created_at) <=', $endDate, false)
                ->whereNotIn('p.status', ['cancelled', 'void']);
            $posRow = (array) $posQuery->get()->getRowArray();
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
            'booking_count' => (int) ($bookingRow['booking_count'] ?? 0),
            'invoice_count' => (int) ($invoiceRow['invoice_count'] ?? 0),
            'pos_count' => (int) ($posRow['pos_count'] ?? 0),
            'billed' => (float) ($bookingRow['billed'] ?? 0) + (float) ($invoiceRow['billed'] ?? 0) + (float) ($posRow['billed'] ?? 0),
            'collected' => (float) ($bookingRow['collected'] ?? 0) + (float) ($invoiceRow['collected'] ?? 0) + (float) ($posRow['collected'] ?? 0),
        ];
    }

    private function getTopCustomers(
        \CodeIgniter\Database\ConnectionInterface $db,
        int $tenantId,
        ?int $branchId,
        string $startDate,
        string $endDate,
        bool $offPeakOnly,
        int $limit = 6
    ): array {
        if (!$db->tableExists('bookings')) {
            return [];
        }

        $builder = $this->applyScope($db->table('bookings b'), $tenantId, $branchId, 'b', 'bookings')
            ->select('p.id AS player_id, p.full_name AS player_name, p.phone AS player_phone, COALESCE(SUM(COALESCE(b.paid_amount, b.total_amount)), 0) AS revenue, COUNT(*) AS booking_count')
            ->join('players p', 'p.id = b.player_id', 'left')
            ->where('b.booking_date >=', $startDate)
            ->where('b.booking_date <=', $endDate)
            ->whereNotIn('b.status', ['cancelled', 'refunded', 'expired'])
            ->groupBy('p.id')
            ->orderBy('revenue', 'DESC')
            ->limit($limit);

        if ($offPeakOnly) {
            $builder->where('(TIME(b.start_time) < "17:00:00" OR TIME(b.start_time) >= "22:00:00")', null, false);
        }

        return $builder->get()->getResult();
    }

    private function getClosingDiscrepancies(\CodeIgniter\Database\ConnectionInterface $db, int $tenantId, ?int $branchId, int $limit = 20): array
    {
        if (!$db->tableExists('daily_closings') || !$db->fieldExists('discrepancy_amount', 'daily_closings')) {
            return [];
        }

        $builder = $this->applyScope($db->table('daily_closings c'), $tenantId, $branchId, 'c', 'daily_closings')
            ->select('c.id, c.closing_date, c.discrepancy_amount, c.status, c.closed_at, c.locked_at')
            ->where('c.discrepancy_amount !=', 0)
            ->orderBy('c.closing_date', 'DESC')
            ->limit($limit);

        return $builder->get()->getResult();
    }

    private function getCreditAlerts(\CodeIgniter\Database\ConnectionInterface $db, int $tenantId, ?int $branchId, int $limit = 12): array
    {
        if (!$db->tableExists('invoices') || !$db->fieldExists('paid_amount', 'invoices')) {
            return [];
        }

        $builder = $this->applyScope($db->table('invoices i'), $tenantId, $branchId, 'i', 'invoices')
            ->select('i.id, i.invoice_code, p.full_name AS player_name, p.phone AS player_phone, i.total_amount, i.paid_amount, (i.total_amount - i.paid_amount) AS outstanding, i.status')
            ->join('players p', 'p.id = i.player_id', 'left')
            ->whereNotIn('i.status', ['paid', 'cancelled', 'refunded'])
            ->where('i.total_amount - i.paid_amount >', 0, false)
            ->orderBy('outstanding', 'DESC')
            ->limit($limit);

        return $builder->get()->getResult();
    }

    private function applyScope(\CodeIgniter\Database\BaseBuilder $builder, int $tenantId, ?int $branchId, string $alias, string $tableName): \CodeIgniter\Database\BaseBuilder
    {
        $builder->where($alias . '.tenant_id', $tenantId);
        if ($branchId) {
            if (\Config\Database::connect()->fieldExists('branch_id', $tableName)) {
                $builder->where($alias . '.branch_id', $branchId);
            }
        }

        return $builder;
    }
}
