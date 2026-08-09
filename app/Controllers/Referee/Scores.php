<?php

namespace App\Controllers\Referee;

use App\Controllers\Admin\Scores as AdminScores;

class Scores extends AdminScores
{
    public function index()
    {
        return $this->render('referee/scores/index', [
            'pageTitle' => 'Trận được phân công',
            'matches' => $this->assignedMatches(),
        ]);
    }

    public function edit(int $matchId)
    {
        $match = $this->scoreService->getMatch($matchId);
        if (! $match) {
            return redirect()->to('/referee/scores')->with('error', 'Không tìm thấy trận đấu.');
        }

        return $this->render('admin/scores/edit', [
            'pageTitle' => 'Nhập điểm trận #' . $matchId,
            'match' => $match,
            'scores' => model(\App\Models\TournamentMatchScoreModel::class)->getByMatch($matchId),
            'logs' => model(\App\Models\TournamentScoreLogModel::class)->where('match_id', $matchId)->orderBy('created_at', 'DESC')->findAll(20),
            'returnUrl' => '/referee/scores',
            'postBase' => '/referee/scores',
        ]);
    }

    protected function assignedMatches(): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('tournament_matches')) {
            return [];
        }

        $userId = session()->get('user_id') ?? session()->get('userId');
        $builder = $db->table('tournament_matches')->orderBy('id', 'ASC')->limit(100);
        if (current_tenant_id() && $db->fieldExists('tenant_id', 'tournament_matches')) {
            $builder->where('tenant_id', current_tenant_id());
        }
        if ($userId && $db->fieldExists('referee_id', 'tournament_matches')) {
            $builder->where('referee_id', $userId);
        }
        if ($db->fieldExists('status', 'tournament_matches')) {
            $builder->whereIn('status', ['scheduled', 'pending', 'running', 'in_progress']);
        }

        return $builder->get()->getResult();
    }
}
