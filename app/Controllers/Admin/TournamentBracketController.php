<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TournamentCategoryModel;
use App\Models\TournamentModel;
use App\Services\TournamentSchedulerService;

class TournamentBracketController extends BaseController
{
    private TournamentModel $tournamentModel;
    private TournamentCategoryModel $categoryModel;
    private TournamentSchedulerService $scheduler;

    public function __construct()
    {
        $this->tournamentModel = new TournamentModel();
        $this->categoryModel = new TournamentCategoryModel();
        $this->scheduler = new TournamentSchedulerService();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $tournamentId = (int) $this->request->getGet('tournament_id');
        $categoryId = (int) $this->request->getGet('category_id');

        if ($categoryId) {
            $category = $this->categoryModel->where('id', $categoryId)->where('tenant_id', $tenantId)->first();
            $tournamentId = (int) ($category->tournament_id ?? 0);
        }

        $tournament = $tournamentId ? $this->tournamentModel->findForTenant($tournamentId, $tenantId) : null;
        $categories = $tournament ? $this->categoryModel->getByTournament($tournamentId) : [];
        $brackets = [];
        $athletes = [];
        $db = \Config\Database::connect();
        foreach ($categories as $category) {
            $categoryId = (int) $category->id;
            $brackets[$categoryId] = $this->scheduler->getVisualBracket($categoryId, $tenantId);
            $athletes[$categoryId] = $db->table('tournament_registrations r')
                ->select('r.*, r.player_id AS player_id, r.partner_player_id AS partner_player_id, p.full_name as player_name, p.player_code, p.rating_score, pp.full_name as partner_name, pp.player_code as partner_code')
                ->join('players p', 'p.id = r.player_id AND p.tenant_id = r.tenant_id', 'left')
                ->join('players pp', 'pp.id = r.partner_player_id AND pp.tenant_id = r.tenant_id', 'left')
                ->where('r.tenant_id', $tenantId)->where('r.tournament_id', $tournamentId)
                ->where('r.category_id', $categoryId)->where('r.deleted_at', null)
                ->orderBy('r.id', 'ASC')->get()->getResult();
        }

        return $this->render('admin/tournaments/bracket', [
            'pageTitle' => 'Cây đấu giải',
            'pageDescription' => 'Theo dõi nhánh đấu, chỉnh khung và nhập kết quả theo từng hạng mục.',
            'tournament' => $tournament,
            'tournamentId' => $tournamentId,
            'categories' => $categories,
            'brackets' => $brackets,
            'athletes' => $athletes,
        ]);
    }

    public function rerun(int $categoryId)
    {
        $category = $this->categoryModel->where('id', $categoryId)->where('tenant_id', (int) current_tenant_id())->first();
        if (! $category) {
            return redirect()->back()->with('error', 'Không tìm thấy hạng mục.');
        }

        $this->scheduler->generateKnockoutBracket($categoryId);
        return redirect()->to('/admin/tournaments/bracket?tournament_id=' . (int) $category->tournament_id)
            ->with('success', 'Đã chạy lại cây đấu cho hạng mục.');
    }

    public function export(int $categoryId)
    {
        $tenantId = (int) current_tenant_id();
        $category = $this->categoryModel->findForTenant($categoryId, $tenantId);
        if (! $category) return redirect()->back()->with('error', 'Không tìm thấy hạng mục.');
        $csv = "\xEF\xBB\xBF" . implode(',', ['Vòng', 'Vị trí', 'Trận', 'Đội A', 'Đội B', 'Đội thắng', 'Ngày', 'Giờ', 'Sân', 'Trạng thái']) . "\n";
        foreach ($this->scheduler->getVisualBracket($categoryId, $tenantId) as $roundNo => $matches) {
            foreach ($matches as $match) {
                $values = [$roundNo, $match->bracket_position, $match->match_no, $match->team_a_label, $match->team_b_label, $match->winner_team_id ? ($match->winner_team_id == $match->team_a_id ? $match->team_a_label : $match->team_b_label) : '', $match->scheduled_date, $match->start_time, $match->court_id, $match->status_label];
                $csv .= implode(',', array_map(static fn ($value) => '"' . str_replace('"', '""', (string) $value) . '"', $values)) . "\n";
            }
        }
        return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')->setHeader('Content-Disposition', 'attachment; filename="bracket-category-' . $categoryId . '.csv"')->setBody($csv);
    }
}
