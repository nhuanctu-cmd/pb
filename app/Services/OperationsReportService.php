<?php

namespace App\Services;

use App\Models\BranchModel;

class OperationsReportService
{
    private BranchModel $branchModel;

    public function __construct()
    {
        $this->branchModel = new BranchModel();
    }

    public function get(int $tenantId, ?string $from = null, ?string $to = null, ?int $branchId = null): array
    {
        [$from, $to] = self::normalizeRange($from, $to);
        if (!$tenantId) {
            return ['from' => $from, 'to' => $to, 'summary' => [], 'daily' => [], 'branches' => []];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('bookings')
            ->select("booking_date, branch_id, COUNT(*) AS bookings, COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','expired') THEN total_amount ELSE 0 END), 0) AS revenue, COALESCE(SUM(paid_amount), 0) AS collected")
            ->where('tenant_id', $tenantId)->where('booking_date >=', $from)->where('booking_date <=', $to)->where('deleted_at', null)
            ->groupBy(['booking_date', 'branch_id'])->orderBy('booking_date', 'ASC');
        if ($branchId) {
            $builder->where('branch_id', $branchId);
        }
        $rows = $builder->get()->getResultArray();

        $summary = $db->table('bookings')
            ->select("COUNT(*) AS bookings, COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','refunded','expired') THEN total_amount ELSE 0 END), 0) AS revenue, COALESCE(SUM(paid_amount), 0) AS collected")
            ->where('tenant_id', $tenantId)->where('booking_date >=', $from)->where('booking_date <=', $to)->where('deleted_at', null);
        if ($branchId) {
            $summary->where('branch_id', $branchId);
        }
        $summary = $summary->get()->getRowArray() ?: [];

        $branches = [];
        foreach ($this->branchModel->getByTenant($tenantId) as $branch) {
            $branches[(int) $branch->id] = (string) $branch->name;
        }
        $commerce = ['invoices' => 0, 'billed' => 0, 'collected' => 0, 'outstanding' => 0];
        if ($db->tableExists('invoices')) {
            $invoiceQuery = $db->table('invoices')->select('COUNT(*) AS invoices, COALESCE(SUM(total_amount), 0) AS billed, COALESCE(SUM(paid_amount), 0) AS collected, COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding')
                ->where('tenant_id', $tenantId)->where('created_at >=', $from . ' 00:00:00')->where('created_at <=', $to . ' 23:59:59')->whereNotIn('status', ['cancelled', 'refunded']);
            if ($branchId) $invoiceQuery->where('branch_id', $branchId);
            $commerce = array_merge($commerce, $invoiceQuery->get()->getRowArray() ?: []);
        }
        return ['from' => $from, 'to' => $to, 'summary' => $summary, 'daily' => $rows, 'branches' => $branches, 'commerce' => $commerce];
    }

    public static function normalizeRange(?string $from, ?string $to): array
    {
        $today = date('Y-m-d');
        $from = self::validDate($from) ?? date('Y-m-01');
        $to = self::validDate($to) ?? $today;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        if ((strtotime($to) - strtotime($from)) > 366 * 86400) {
            $to = date('Y-m-d', strtotime($from . ' +366 days'));
        }
        return [$from, $to];
    }

    private static function validDate(?string $date): ?string
    {
        $date = trim((string) $date);
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
    }
}
