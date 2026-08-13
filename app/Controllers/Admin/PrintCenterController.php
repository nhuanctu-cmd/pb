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
        $document = (string) $this->request->getGet('document');
        $printOptions = [
            'category_id' => (string) $this->request->getGet('category_id'),
            'court_id' => (string) $this->request->getGet('court_id'),
            'date_from' => (string) $this->request->getGet('date_from'),
            'date_to' => (string) $this->request->getGet('date_to'),
            'status' => (string) $this->request->getGet('status_match'),
            'checkin_status' => (string) $this->request->getGet('checkin_status'),
            'sequence' => (string) $this->request->getGet('sequence'),
        ];
        $categoryOptions = [];
        $courtOptions = [];
        if ($tournamentId > 0) {
            $categoryOptions = $this->printCenter->getCategoryOptions($tournamentId);
            $courtOptions = $this->printCenter->getCourtOptions($tournamentId, $tenantId);
        }
        $printScopeTitle = $this->printCenter->getDocumentPackMeta($printOptions)['label'] ?? 'Toàn bộ dữ liệu';
        return $this->render('admin/print_center/index', [
            'pageTitle' => 'Print Center',
            'pageDescription' => 'Chọn đúng giải, xem dữ liệu tổng quan và in toàn bộ bộ tài liệu vận hành.',
            'tournaments' => $this->printCenter->getTournamentsPaginated($tenantId, $page, $perPage, $search, $status),
            'overview' => $tournamentId > 0 ? $this->printCenter->overview($tournamentId, $tenantId) : null,
            'tournamentId' => $tournamentId,
            'categoryOptions' => $categoryOptions,
            'courtOptions' => $courtOptions,
            'document' => in_array($document, TournamentPrintCenterService::DOCUMENT_TYPES, true) ? $document : 'schedule',
            'printCatalog' => TournamentPrintCenterService::DOCUMENT_CATALOG,
            'printOptions' => $this->printCenter->normalizePrintOptions($printOptions),
            'printScopeTitle' => $printScopeTitle,
        ]);
    }

    public function print()
    {
        $type = (string) ($this->request->getGet('document') ?: 'schedule');
        if ($type === 'all' || $type === 'pack') {
            $printData = $this->printCenter->getPrintPack($type, (int) $this->request->getGet('tournament_id'), (int) current_tenant_id(), $this->printRequestOptions());
            if (empty($printData)) {
                return redirect()->to('/admin/print-center')->with('error', 'Vui lòng chọn giải đấu hợp lệ.');
            }
            return view('admin/print_center/print', [
                'type' => 'pack',
                'pageTitle' => 'In bộ tài liệu',
                'printPack' => $printData,
                'printScopeTitle' => $this->printCenter->getDocumentPackMeta($this->printRequestOptions())['label'] ?? 'Toàn bộ dữ liệu',
            ]);
        }
        if (! in_array($type, TournamentPrintCenterService::DOCUMENT_TYPES, true)) {
            $type = 'schedule';
        }
        $data = $this->printCenter->getDocumentWithOptions(
            $type,
            (int) $this->request->getGet('tournament_id'),
            (int) current_tenant_id(),
            $this->printRequestOptions()
        );
        if (! $data) {
            return redirect()->to('/admin/print-center')->with('error', 'Vui lòng chọn giải đấu hợp lệ.');
        }
        return view('admin/print_center/print', $data + ['pageTitle' => 'In ' . $type]);
    }

    protected function printRequestOptions(): array
    {
        $types = $this->request->getGet('types');
        if (is_string($types)) {
            $types = array_filter(array_map('trim', explode(',', $types)));
        }
        return [
            'category_id' => (string) $this->request->getGet('category_id'),
            'court_id' => (string) $this->request->getGet('court_id'),
            'date_from' => (string) $this->request->getGet('date_from'),
            'date_to' => (string) $this->request->getGet('date_to'),
            'status' => (string) $this->request->getGet('status_match'),
            'checkin_status' => (string) $this->request->getGet('checkin_status'),
            'sequence' => (string) $this->request->getGet('sequence'),
            'types' => (array) ($types ?: []),
        ];
    }
}
