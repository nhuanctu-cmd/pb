<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TournamentMatchScoreModel;
use App\Models\TournamentScoreLogModel;
use App\Services\ScoreService;

class Scores extends BaseController
{
    protected ScoreService $scoreService;

    public function __construct()
    {
        $this->scoreService = service('scoreService');
    }

    public function index()
    {
        return $this->render('admin/scores/index', [
            'pageTitle' => 'Nhập điểm trận đấu',
            'matches' => $this->matches(),
        ]);
    }

    public function edit(int $matchId)
    {
        $match = $this->scoreService->getMatch($matchId);
        if (! $match) {
            return redirect()->to('/admin/scores')->with('error', 'Không tìm thấy trận đấu.');
        }

        return $this->render('admin/scores/edit', [
            'pageTitle' => 'Nhập điểm trận #' . $matchId,
            'match' => $match,
            'scores' => model(TournamentMatchScoreModel::class)->getByMatch($matchId),
            'logs' => model(TournamentScoreLogModel::class)->where('match_id', $matchId)->orderBy('created_at', 'DESC')->findAll(20),
            'returnUrl' => '/admin/scores',
            'postBase' => '/admin/scores',
        ]);
    }

    public function start(int $matchId)
    {
        if (! $this->canEditScore()) {
            return redirect()->back()->with('error', 'Bạn không có quyền bắt đầu trận.');
        }

        $result = $this->scoreService->startMatch($matchId);
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function update(int $matchId)
    {
        if (! $this->canEditScore()) {
            return redirect()->back()->with('error', 'Bạn không có quyền nhập điểm.');
        }

        $result = $this->scoreService->updateScore($matchId, $this->request->getPost('sets') ?? []);
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function finish(int $matchId)
    {
        if (! $this->canEditScore()) {
            return redirect()->back()->with('error', 'Bạn không có quyền xác nhận kết quả.');
        }

        $result = $this->scoreService->finishMatch($matchId);
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function lock(int $matchId)
    {
        $match = $this->scoreService->getMatch($matchId, (int) current_tenant_id());
        if (! $match) return redirect()->back()->with('error', 'Không tìm thấy trận đấu.');
        if (($match->status ?? '') !== 'completed') return redirect()->back()->with('error', 'Chỉ được khóa trận đã hoàn tất.');
        $ok = (new \App\Services\TournamentSchedulerService())->lockMatch($matchId);
        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Đã khóa kết quả trận.' : 'Không thể khóa kết quả.');
    }

    public function unlock(int $matchId)
    {
        if (! is_superadmin() && ! has_role('admin')) return redirect()->back()->with('error', 'Bạn không có quyền mở khóa kết quả.');
        $match = $this->scoreService->getMatch($matchId, (int) current_tenant_id());
        if (! $match) return redirect()->back()->with('error', 'Không tìm thấy trận đấu.');
        $ok = (new \App\Services\TournamentSchedulerService())->unlockMatch($matchId);
        return redirect()->back()->with($ok ? 'success' : 'error', $ok ? 'Đã mở khóa kết quả trận.' : 'Không thể mở khóa kết quả.');
    }

    protected function matches(): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('tournament_matches')) {
            return [];
        }

        $builder = $db->table('tournament_matches')->orderBy('id', 'DESC')->limit(100);
        if (current_tenant_id() && $db->fieldExists('tenant_id', 'tournament_matches')) {
            $builder->where('tenant_id', current_tenant_id());
        }

        return $builder->get()->getResult();
    }

    protected function canEditScore(): bool
    {
        return is_superadmin() || has_role('admin') || has_role('referee') || has_role('superadmin');
    }
}
