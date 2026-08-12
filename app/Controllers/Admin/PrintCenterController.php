<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\TournamentPrintCenterService;

class PrintCenterController extends BaseController
{
    private TournamentPrintCenterService $printCenter;

    public function __construct()
    {
        $this->printCenter = new TournamentPrintCenterService();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $page = max(1, (int) $this->request->getGet('page'));
        $perPage = (int) ($this->request->getGet('per_page') ?: 12);
        $search = (string) $this->request->getGet('search');
        $status = (string) $this->request->getGet('status');
        $tournamentId = (int) $this->request->getGet('tournament_id');
        return $this->render('admin/print_center/index', [
            'pageTitle' => 'Print Center',
            'pageDescription' => 'Chọn đúng giải, xem dữ liệu tổng quan và in toàn bộ bộ tài liệu vận hành.',
            'tournaments' => $this->printCenter->getTournamentsPaginated($tenantId, $page, $perPage, $search, $status),
            'overview' => $tournamentId > 0 ? $this->printCenter->overview($tournamentId, $tenantId) : null,
            'tournamentId' => $tournamentId,
        ]);
    }

    public function print()
    {
        $type = (string) ($this->request->getGet('document') ?: 'schedule');
        if (! in_array($type, TournamentPrintCenterService::DOCUMENT_TYPES, true)) {
            $type = 'schedule';
        }
        $data = $this->printCenter->getDocument($type, (int) $this->request->getGet('tournament_id'), (int) current_tenant_id());
        if (! $data) {
            return redirect()->to('/admin/print-center')->with('error', 'Vui lòng chọn giải đấu hợp lệ.');
        }
        return view('admin/print_center/print', $data + ['pageTitle' => 'In ' . $type]);
    }
}
