<?php

namespace App\Services;

use Config\Database;

class TournamentFinanceService
{
    public function summary(int $tenantId, int $tournamentId): array
    {
        $db = Database::connect();

        if (! $db->tableExists('tournaments') || ! $db->tableExists('tournament_registrations') || ! $db->fieldExists('tenant_id', 'tournaments')) {
            return $this->emptySummary();
        }

        $tournament = $db->table('tournaments')
            ->where('id', $tournamentId)
            ->where('tenant_id', $tenantId)
            ->where('deleted_at', null)
            ->get()
            ->getRow();

        if (! $tournament) {
            return $this->emptySummary();
        }

        $registrationStats = $db->table('tournament_registrations')->select(
            'COUNT(*) AS total_registrations, ' .
            'SUM(invoice_amount) AS expected_revenue, ' .
            'SUM(CASE WHEN payment_status = "paid" THEN invoice_amount ELSE 0 END) AS paid_by_status, ' .
            'SUM(CASE WHEN approval_status = "approved" THEN 1 ELSE 0 END) AS approved_registrations, ' .
            'SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) AS paid_registrations, ' .
            'SUM(CASE WHEN payment_status IN ("unpaid","partial","failed") THEN 1 ELSE 0 END) AS unpaid_registrations'
        )->where('tenant_id', $tenantId)->where('tournament_id', $tournamentId)->where('deleted_at', null)->get()->getRowArray();

        if (! is_array($registrationStats)) {
            $registrationStats = [];
        }

        $financialSource = 'legacy_registration';
        $totalBilled = (float) ($registrationStats['expected_revenue'] ?? 0);
        $totalCollected = (float) ($registrationStats['paid_by_status'] ?? 0);
        $paidCount = (int) ($registrationStats['paid_registrations'] ?? 0);

        if ($db->tableExists('invoices') && $db->fieldExists('ref_type', 'invoices') && $db->fieldExists('ref_id', 'invoices')) {
            $invoiceStats = $db->table('invoices i')
                ->join('tournament_registrations tr', 'tr.id = i.ref_id AND tr.tenant_id = i.tenant_id', 'inner')
                ->select(
                    'COUNT(i.id) AS invoice_count, ' .
                    'SUM(i.total_amount) AS billed_revenue, ' .
                    'SUM(i.paid_amount) AS collected_revenue, ' .
                    'SUM(CASE WHEN i.status = "paid" THEN 1 ELSE 0 END) AS paid_invoice_count, ' .
                    'SUM(CASE WHEN i.status IN ("unpaid","partial") THEN 1 ELSE 0 END) AS pending_invoice_count'
                )
                ->where('i.tenant_id', $tenantId)
                ->where('i.ref_type', 'tournament_registration')
                ->where('tr.tournament_id', $tournamentId)
                ->where('tr.deleted_at', null)
                ->whereNotIn('i.status', ['cancelled', 'refunded'])
                ->get()
                ->getRowArray();

            if (! empty($invoiceStats) && ((int) $invoiceStats['invoice_count'] > 0)) {
                $financialSource = 'invoice';
                $totalBilled = (float) ($invoiceStats['billed_revenue'] ?? 0);
                $totalCollected = (float) ($invoiceStats['collected_revenue'] ?? 0);
                $paidCount = (int) ($invoiceStats['paid_invoice_count'] ?? 0);
            }
        }

        $sponsors = $this->sponsorMetrics($db, $tenantId, $tournamentId);

        return [
            'source' => $financialSource,
            'currency' => $this->currencyForTenant($db, $tenantId),
            'registration_count' => (int) ($registrationStats['total_registrations'] ?? 0),
            'approved_registrations' => (int) ($registrationStats['approved_registrations'] ?? 0),
            'paid_registrations' => $paidCount,
            'unpaid_registrations' => (int) ($registrationStats['unpaid_registrations'] ?? 0),
            'expected_revenue' => $totalBilled,
            'collected_revenue' => $totalCollected,
            'outstanding_revenue' => max(0.0, $totalBilled - $totalCollected),
            'sponsors' => $sponsors,
        ];
    }

    protected function sponsorMetrics($db, int $tenantId, int $tournamentId): array
    {
        if (! $db->tableExists('tournament_sponsors')) {
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
            ];
        }

        $total = (int) $db->table('tournament_sponsors')
            ->where('tenant_id', $tenantId)
            ->where('tournament_id', $tournamentId)
            ->where('deleted_at', null)
            ->countAllResults();

        $active = (int) $db->table('tournament_sponsors')
            ->where('tenant_id', $tenantId)
            ->where('tournament_id', $tournamentId)
            ->where('deleted_at', null)
            ->where('status', 'active')
            ->countAllResults();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => max(0, $total - $active),
        ];
    }

    protected function currencyForTenant($db, int $tenantId): string
    {
        if (! $db->tableExists('tenants')) {
            return 'VND';
        }

        if ($db->fieldExists('default_currency', 'tenants')) {
            $tenant = $db->table('tenants')->select('default_currency')->where('id', $tenantId)->get()->getRow();
            if (! empty($tenant->default_currency)) {
                return strtoupper((string) $tenant->default_currency);
            }
        }

        if ($db->fieldExists('country_code', 'tenants')
            && $db->tableExists('platform_countries')
            && $db->fieldExists('code', 'platform_countries')
            && $db->fieldExists('default_currency', 'platform_countries')
        ) {
            $tenant = $db->table('platform_countries pc')
                ->select('pc.default_currency')
                ->join('tenants t', 't.country_code = pc.code', 'left')
                ->where('t.id', $tenantId)
                ->get()
                ->getRow();

            if (! empty($tenant->default_currency)) {
                return strtoupper((string) $tenant->default_currency);
            }
        }

        return 'VND';
    }

    protected function emptySummary(): array
    {
        return [
            'source' => 'none',
            'currency' => 'VND',
            'registration_count' => 0,
            'approved_registrations' => 0,
            'paid_registrations' => 0,
            'unpaid_registrations' => 0,
            'expected_revenue' => 0.0,
            'collected_revenue' => 0.0,
            'outstanding_revenue' => 0.0,
            'sponsors' => ['total' => 0, 'active' => 0, 'inactive' => 0],
        ];
    }
}
