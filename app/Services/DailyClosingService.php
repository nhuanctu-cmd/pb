<?php

namespace App\Services;

use App\Models\DailyClosingModel;

class DailyClosingService
{
    private DailyClosingModel $model;

    public function __construct()
    {
        $this->model = new DailyClosingModel();
    }

    public function snapshot(int $tenantId, ?int $branchId, string $date): array
    {
        $db = \Config\Database::connect();
        $scope = static function ($builder, string $realTable, string $alias, int $tenantId, ?int $branchId) use ($db) {
            $builder->where($alias . '.tenant_id', $tenantId);
            if ($branchId && $db->fieldExists('branch_id', $realTable)) {
                $builder->where($alias . '.branch_id', $branchId);
            }
            return $builder;
        };

        $payments = ['cash' => 0, 'bank_qr' => 0, 'wallet' => 0, 'other' => 0];
        if ($db->tableExists('payments')) {
            $query = $db->table('payments p')->select('p.method, COALESCE(SUM(p.amount), 0) AS total')
                ->join('invoices i', 'i.id = p.invoice_id', 'left')
                ->where('p.status', 'success')->where('DATE(p.paid_at)', $date, false);
            if ($branchId) $query->where('i.branch_id', $branchId);
            $query = $scope($query, 'payments', 'p', $tenantId, null)->groupBy('p.method');
            foreach ($query->get()->getResultArray() as $row) {
                $method = (string) $row['method'];
                $key = in_array($method, ['cash', 'bank_qr', 'wallet'], true) ? $method : 'other';
                $payments[$key] += (float) $row['total'];
            }
        }

        $invoices = ['billed' => 0, 'collected' => 0, 'refunds' => 0];
        if ($db->tableExists('invoices')) {
            $query = $db->table('invoices i')->select('COALESCE(SUM(i.total_amount), 0) AS billed, COALESCE(SUM(i.paid_amount), 0) AS collected')
                ->where('DATE(i.created_at)', $date, false)->whereNotIn('i.status', ['cancelled', 'refunded']);
            $invoices = array_merge($invoices, (array) ($scope($query, 'invoices', 'i', $tenantId, $branchId)->get()->getRowArray() ?: []));
            $refundQuery = $db->table('invoices i')->select('COALESCE(SUM(i.total_amount), 0) AS refunds')
                ->where('DATE(i.updated_at)', $date, false)->where('i.status', 'refunded');
            $invoices['refunds'] = (float) (($scope($refundQuery, 'invoices', 'i', $tenantId, $branchId)->get()->getRowArray()['refunds'] ?? 0));
        }

        $pos = ['orders' => 0, 'billed' => 0, 'collected' => 0];
        if ($db->tableExists('pos_orders')) {
            $query = $db->table('pos_orders p')->select('COUNT(*) AS orders, COALESCE(SUM(p.total_amount), 0) AS billed, COALESCE(SUM(p.paid_amount), 0) AS collected')
                ->where('DATE(p.created_at)', $date, false)->where('p.status !=', 'cancelled');
            $pos = array_merge($pos, (array) ($scope($query, 'pos_orders', 'p', $tenantId, $branchId)->get()->getRowArray() ?: []));
        }

        return [
            'payments' => $payments,
            'billed' => (float) $invoices['billed'] + (float) $pos['billed'],
            'collected' => (float) $invoices['collected'] + (float) $pos['collected'],
            'refunds' => (float) $invoices['refunds'],
            'pos_orders' => (int) $pos['orders'],
            'payment_total' => array_sum($payments),
            'source_date' => $date,
        ];
    }

    public function getOrCreate(int $tenantId, ?int $branchId, string $date): object
    {
        $closing = $this->model->findForScope($tenantId, $branchId, $date);
        if ($closing) return $closing;
        $id = $this->model->insert([
            'tenant_id' => $tenantId, 'branch_id' => $branchId ?: null, 'closing_date' => $date,
            'status' => 'open', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->model->find((int) $id);
    }

    public function close(int $tenantId, ?int $branchId, string $date, ?int $userId, float $declaredCash = 0, ?string $notes = null): bool
    {
        $closing = $this->getOrCreate($tenantId, $branchId, $date);
        if ($closing->status === 'closed') return true;
        $snapshot = $this->snapshot($tenantId, $branchId, $date);
        $expectedCash = (float) ($snapshot['payments']['cash'] ?? 0);
        return (bool) $this->model->update($closing->id, [
            'status' => 'closed', 'cash_total' => $expectedCash, 'qr_total' => $snapshot['payments']['bank_qr'],
            'wallet_total' => $snapshot['payments']['wallet'], 'other_total' => $snapshot['payments']['other'],
            'billed_total' => $snapshot['billed'], 'collected_total' => $snapshot['collected'],
            'refund_total' => $snapshot['refunds'], 'discrepancy_amount' => $declaredCash - $expectedCash,
            'notes' => $notes, 'closed_by' => $userId, 'closed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function reopen(int $tenantId, ?int $branchId, string $date, ?int $userId): bool
    {
        $closing = $this->model->findForScope($tenantId, $branchId, $date);
        return $closing ? (bool) $this->model->update($closing->id, ['status' => 'reopened', 'reopened_by' => $userId, 'reopened_at' => date('Y-m-d H:i:s')]) : false;
    }
}
