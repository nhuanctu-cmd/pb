<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TournamentModel;
use App\Services\TournamentOperationsService;

class TournamentOperationsController extends BaseController
{
    private TournamentOperationsService $operations;

    public function __construct()
    {
        $this->operations = new TournamentOperationsService();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $tournaments = model(TournamentModel::class)->getByTenant($tenantId);
        $tournamentId = (int) $this->request->getGet('tournament_id');
        if (! $tournamentId && ! empty($tournaments)) {
            $today = date('Y-m-d');
            foreach ($tournaments as $tournament) {
                if ($tournament->start_date === $today || $tournament->status === 'running') {
                    $tournamentId = (int) $tournament->id;
                    break;
                }
            }
            $tournamentId = $tournamentId ?: (int) $tournaments[0]->id;
        }

        $dashboard = $tournamentId ? $this->operations->getDashboard(
            $tournamentId,
            $tenantId,
            $this->request->getGet('date') ?: null
        ) : null;

        return $this->render('admin/tournaments/control_room', [
            'pageTitle' => 'Tournament Control Room',
            'pageDescription' => 'Một màn hình vận hành toàn bộ ngày thi đấu.',
            'tournaments' => $tournaments,
            'tournamentId' => $tournamentId,
            'dashboard' => $dashboard,
        ]);
    }

    public function data()
    {
        $tournamentId = (int) $this->request->getGet('tournament_id');
        if (! $tournamentId) {
            $tournaments = model(TournamentModel::class)->getByTenant((int) current_tenant_id());
            $tournamentId = ! empty($tournaments) ? (int) $tournaments[0]->id : 0;
        }

        $dashboard = $this->operations->getDashboard(
            $tournamentId,
            (int) current_tenant_id(),
            $this->request->getGet('date') ?: null
        );

        if (! $dashboard) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Không tìm thấy giải đấu.']);
        }

        return $this->response->setJSON(['success' => true, 'data' => $dashboard]);
    }

    public function status(int $matchId)
    {
        $result = $this->operations->updateMatchStatus(
            $matchId,
            (int) current_tenant_id(),
            (string) $this->request->getPost('status'),
            $this->request->getPost('note') ?: null
        );
        return $this->request->isAJAX()
            ? $this->response->setJSON($result)
            : redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function call(int $matchId)
    {
        return $this->statusInternal($matchId, 'called');
    }

    private function statusInternal(int $matchId, string $status)
    {
        $result = $this->operations->updateMatchStatus($matchId, (int) current_tenant_id(), $status);
        return $this->request->isAJAX()
            ? $this->response->setJSON($result)
            : redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
