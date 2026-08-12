<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\TournamentSchedulerService;
use App\Models\TournamentCategoryModel;
use App\Models\TournamentMatchModel;
use App\Models\TournamentGroupModel;

class TournamentSchedulerController extends BaseController
{
    private TournamentSchedulerService $scheduler;
    private TournamentCategoryModel $categoryModel;
    private TournamentMatchModel $matchModel;
    private TournamentGroupModel $groupModel;

    public function __construct()
    {
        $this->scheduler = new TournamentSchedulerService();
        $this->categoryModel = new TournamentCategoryModel();
        $this->matchModel = new TournamentMatchModel();
        $this->groupModel = new TournamentGroupModel();
    }

    public function index()
    {
        $categoryId = (int) ($this->request->getGet('category_id') ?? 0);
        if ($categoryId && !$this->categoryForTenant($categoryId)) {
            $categoryId = 0;
        }
        $this->viewData['pageTitle'] = 'Điều phối giải';
        $this->viewData['categoryId'] = $categoryId;
        $this->viewData['groups'] = $categoryId ? $this->scheduler->getGroupsWithTeams($categoryId) : [];
        $this->viewData['matches'] = $categoryId ? $this->scheduler->getMatches($categoryId) : [];
        $this->viewData['conflicts'] = $categoryId ? $this->scheduler->detectConflicts($categoryId) : [];
        $tenantId = (int) current_tenant_id();
        $this->viewData['categories'] = $tenantId ? $this->categoryModel->select('tournament_categories.*, tournaments.name_vi AS tournament_name')->join('tournaments', 'tournaments.id = tournament_categories.tournament_id AND tournaments.tenant_id = tournament_categories.tenant_id', 'left')->where('tournament_categories.tenant_id', $tenantId)->where('tournament_categories.deleted_at', null)->where('tournaments.deleted_at', null)->orderBy('tournaments.start_date', 'DESC')->orderBy('tournament_categories.name_vi', 'ASC')->findAll() : [];
        $this->viewData['courts'] = $tenantId ? \Config\Database::connect()->table('courts')->where('tenant_id', $tenantId)->where('deleted_at', null)->whereIn('status', ['available', 'occupied'])->orderBy('sort_order', 'ASC')->get()->getResult() : [];
        $category = $categoryId ? $this->categoryForTenant($categoryId) : null;
        $this->viewData['category'] = $category;
        $this->viewData['participants'] = $category ? $this->participantOptions($category) : [];
        $publishedLock = $category && $tenantId ? \Config\Database::connect()->table('tournament_schedule_locks')->where('tenant_id', $tenantId)->where('tournament_id', (int) $category->tournament_id)->where('lock_type', 'time')->where('ref_id', $categoryId)->get(1)->getRow() : null;
        $this->viewData['published'] = (bool) $publishedLock;

        return $this->render('admin/tournaments/scheduler', $this->viewData);
    }

    public function autoSchedule()
    {
        $categoryId = (int) $this->request->getPost('category_id');
        $groups = (int) ($this->request->getPost('groups') ?: 2);
        if (! $categoryId) {
            return redirect()->back()->with('error', 'Vui lòng nhập category_id.');
        }
        if (!$this->categoryForTenant($categoryId)) {
            return redirect()->back()->with('error', 'Không tìm thấy nội dung thuộc tenant hiện tại.');
        }

        $createdGroups = $this->scheduler->generateGroups($categoryId, $groups);
        $this->scheduler->seedTeams($categoryId);
        foreach ($createdGroups as $group) {
            $this->scheduler->generateRoundRobinMatches((int) $group->id);
        }
        $this->scheduler->generateKnockoutBracket($categoryId);
        $this->scheduler->assignCourts($categoryId);
        $this->scheduler->assignTimeSlots($categoryId);

        return redirect()->to('/admin/tournaments/scheduler?category_id=' . $categoryId)->with('success', 'Đã xếp lịch tự động.');
    }

    public function rerunUnlocked()
    {
        $categoryId = (int) $this->request->getPost('category_id');
        if (! $categoryId) {
            return redirect()->back()->with('error', 'Vui lòng nhập category_id.');
        }
        if (!$this->categoryForTenant($categoryId)) {
            return redirect()->back()->with('error', 'Không tìm thấy nội dung thuộc tenant hiện tại.');
        }

        $this->scheduler->rerunUnlockedSchedule($categoryId);

        return redirect()->to('/admin/tournaments/scheduler?category_id=' . $categoryId)->with('success', 'Đã chạy lại các trận chưa khóa.');
    }

    public function schedule(int $matchId)
    {
        $match = $this->matchForTenant($matchId);
        if (! $match) return redirect()->back()->with('error', 'Không tìm thấy trận đấu.');
        if ((int) $match->is_locked === 1) return redirect()->back()->with('error', 'Trận đã khóa, hãy mở khóa trước khi chỉnh lịch.');

        $courtId = (int) $this->request->getPost('court_id');
        $date = trim((string) $this->request->getPost('scheduled_date'));
        $start = trim((string) $this->request->getPost('start_time'));
        $tenantId = (int) current_tenant_id();
        $court = $courtId ? \Config\Database::connect()->table('courts')->where('id', $courtId)->where('tenant_id', $tenantId)->where('deleted_at', null)->get(1)->getRow() : null;
        if (! $court || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! preg_match('/^\d{2}:\d{2}$/', $start)) {
            return redirect()->back()->with('error', 'Sân, ngày hoặc giờ thi đấu không hợp lệ.');
        }
        $this->scheduler->moveMatch($matchId, $courtId, $date, $start . ':00');
        return redirect()->to('/admin/tournaments/scheduler?category_id=' . (int) $match->category_id)->with('success', 'Đã lưu lịch trận.');
    }

    public function manualMatch()
    {
        $categoryId = (int) $this->request->getPost('category_id');
        $category = $this->categoryForTenant($categoryId);
        if (! $category) return redirect()->back()->with('error', 'Không tìm thấy hạng mục thi đấu.');

        $a = (int) ($this->request->getPost('participant_a') ?: 0);
        $b = (int) ($this->request->getPost('participant_b') ?: 0);
        if ($a && $a === $b) return redirect()->back()->with('error', 'Hai bên thi đấu phải khác nhau.');
        $participants = $this->participantOptions($category);
        foreach ([$a, $b] as $participant) {
            if ($participant && ! isset($participants[$participant])) return redirect()->back()->with('error', 'VĐV/đội chưa được duyệt trong hạng mục này.');
        }

        $db = \Config\Database::connect();
        $courtId = (int) ($this->request->getPost('court_id') ?: 0);
        $date = trim((string) $this->request->getPost('scheduled_date'));
        $start = trim((string) $this->request->getPost('start_time'));
        $tenantId = (int) current_tenant_id();
        if ($courtId && ! $db->table('courts')->where('id', $courtId)->where('tenant_id', $tenantId)->where('deleted_at', null)->get(1)->getRow()) {
            return redirect()->back()->with('error', 'Sân thi đấu không thuộc tenant hiện tại.');
        }
        if (($date !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) || ($start !== '' && ! preg_match('/^\d{2}:\d{2}$/', $start))) {
            return redirect()->back()->with('error', 'Ngày hoặc giờ thi đấu không hợp lệ.');
        }
        if ($courtId && $date && $start && $db->table('tournament_matches')->where('tenant_id', $tenantId)->where('court_id', $courtId)->where('scheduled_date', $date)->where('start_time', $start . ':00')->whereNotIn('status', ['cancelled', 'completed'])->countAllResults() > 0) {
            return redirect()->back()->with('error', 'Sân đã có trận khác cùng thời điểm.');
        }

        $roundNo = max(1, min(20, (int) ($this->request->getPost('round_no') ?: 1)));
        $matchNo = (int) (($db->table('tournament_matches')->selectMax('match_no')->where('category_id', $categoryId)->get()->getRow('match_no') ?? 0) + 1);
        $matchId = $this->matchModel->insert([
            'tenant_id' => $tenantId, 'tournament_id' => (int) $category->tournament_id, 'category_id' => $categoryId,
            'group_id' => null, 'round_name' => trim((string) ($this->request->getPost('round_name') ?: 'Trận thủ công')), 'match_no' => $matchNo,
            'court_id' => $courtId ?: null, 'scheduled_date' => $date ?: null, 'start_time' => $start ? $start . ':00' : null,
            'team_a_id' => $a ?: null, 'team_b_id' => $b ?: null, 'status' => 'scheduled', 'is_locked' => 0,
        ]);
        if (! $matchId) return redirect()->back()->with('error', 'Không thể tạo trận đấu thủ công.');
        if ($db->tableExists('tournament_brackets')) {
            $db->table('tournament_brackets')->insert([
                'tenant_id' => $tenantId, 'tournament_id' => (int) $category->tournament_id, 'category_id' => $categoryId,
                'match_id' => (int) $matchId, 'bracket_position' => 'M-' . $matchNo, 'round_no' => $roundNo,
            ]);
        }
        return redirect()->to('/admin/tournaments/scheduler?category_id=' . $categoryId)->with('success', 'Đã thêm trận đấu thủ công.');
    }

    public function assignParticipants(int $matchId)
    {
        $match = $this->matchForTenant($matchId);
        if (! $match) return redirect()->back()->with('error', 'Không tìm thấy trận đấu.');
        if ((int) $match->is_locked === 1) return redirect()->back()->with('error', 'Trận đã khóa, hãy mở khóa trước khi gán VĐV/đội.');
        $category = $this->categoryForTenant((int) $match->category_id);
        $a = (int) ($this->request->getPost('participant_a') ?: 0);
        $b = (int) ($this->request->getPost('participant_b') ?: 0);
        $participants = $category ? $this->participantOptions($category) : [];
        if (($a && ! isset($participants[$a])) || ($b && ! isset($participants[$b])) || ($a && $a === $b)) {
            return redirect()->back()->with('error', 'VĐV/đội không hợp lệ hoặc bị trùng.');
        }
        $this->matchModel->update($matchId, ['team_a_id' => $a ?: null, 'team_b_id' => $b ?: null, 'winner_team_id' => null]);
        return redirect()->to('/admin/tournaments/scheduler?category_id=' . (int) $match->category_id)->with('success', 'Đã cập nhật VĐV/đội cho trận.');
    }

    public function publish(int $categoryId)
    {
        $category = $this->categoryForTenant($categoryId);
        if (! $category) return redirect()->back()->with('error', 'Không tìm thấy hạng mục thi đấu.');
        $tenantId = (int) current_tenant_id();
        $db = \Config\Database::connect();
        $db->table('tournament_matches')->where('tenant_id', $tenantId)->where('category_id', $categoryId)->whereNotIn('status', ['completed', 'cancelled'])->update(['is_locked' => 1]);
        $lock = $db->table('tournament_schedule_locks')->where('tenant_id', $tenantId)->where('tournament_id', (int) $category->tournament_id)->where('lock_type', 'time')->where('ref_id', $categoryId)->get(1)->getRow();
        if (! $lock) $db->table('tournament_schedule_locks')->insert(['tenant_id' => $tenantId, 'tournament_id' => (int) $category->tournament_id, 'lock_type' => 'time', 'ref_id' => $categoryId, 'reason' => 'Published tournament schedule']);
        return redirect()->to('/admin/tournaments/scheduler?category_id=' . $categoryId)->with('success', 'Đã công bố và khóa lịch thi đấu.');
    }

    public function unpublish(int $categoryId)
    {
        $category = $this->categoryForTenant($categoryId);
        if (! $category) return redirect()->back()->with('error', 'Không tìm thấy hạng mục thi đấu.');
        $tenantId = (int) current_tenant_id();
        $db = \Config\Database::connect();
        $db->table('tournament_matches')->where('tenant_id', $tenantId)->where('category_id', $categoryId)->whereNotIn('status', ['completed', 'cancelled'])->update(['is_locked' => 0]);
        $db->table('tournament_schedule_locks')->where('tenant_id', $tenantId)->where('tournament_id', (int) $category->tournament_id)->where('lock_type', 'time')->where('ref_id', $categoryId)->delete();
        return redirect()->to('/admin/tournaments/scheduler?category_id=' . $categoryId)->with('success', 'Đã mở khóa lịch thi đấu để chỉnh sửa.');
    }

    public function export()
    {
        $categoryId = (int) $this->request->getGet('category_id');
        $category = $this->categoryForTenant($categoryId);
        if (! $category) return redirect()->back()->with('error', 'Không tìm thấy hạng mục thi đấu.');
        $rows = $this->scheduler->getMatches($categoryId);
        $csv = "\xEF\xBB\xBF" . implode(',', ['Trận', 'Vòng', 'VĐV/đội A', 'VĐV/đội B', 'Sân', 'Ngày', 'Giờ', 'Trạng thái']) . "\n";
        foreach ($rows as $row) {
            $values = [$row->match_no ?? '', $row->round_name ?? '', $row->team_a_label ?? $row->team_a_id ?? '', $row->team_b_label ?? $row->team_b_id ?? '', $row->court_name ?? $row->court_id ?? '', $row->scheduled_date ?? '', $row->start_time ?? '', $row->status ?? ''];
            $csv .= implode(',', array_map(static fn ($value) => '"' . str_replace('"', '""', (string) $value) . '"', $values)) . "\n";
        }
        return $this->response->setHeader('Content-Type', 'text/csv; charset=UTF-8')->setHeader('Content-Disposition', 'attachment; filename="category-' . $categoryId . '-schedule.csv"')->setBody($csv);
    }

    public function lock(int $id)
    {
        if (!$this->matchForTenant($id)) {
            return redirect()->back()->with('error', 'Không tìm thấy trận đấu.');
        }
        $this->scheduler->lockMatch($id);
        return redirect()->back()->with('success', 'Đã khóa trận.');
    }

    public function unlock(int $id)
    {
        if (!$this->matchForTenant($id)) {
            return redirect()->back()->with('error', 'Không tìm thấy trận đấu.');
        }
        $this->scheduler->unlockMatch($id);
        return redirect()->back()->with('success', 'Đã mở khóa trận.');
    }

    public function moveTeam()
    {
        $teamId = (int) $this->request->getPost('team_id');
        $groupId = (int) $this->request->getPost('group_id');
        $tenantId = (int) current_tenant_id();
        $group = $tenantId ? $this->groupModel->where('id', $groupId)->where('tenant_id', $tenantId)->first() : null;
        $success = $teamId && $group && $this->scheduler->moveTeamToGroup($teamId, $groupId);

        return $this->response->setJSON(['success' => $success]);
    }

    private function categoryForTenant(int $categoryId): ?object
    {
        $tenantId = (int) current_tenant_id();
        return $tenantId ? $this->categoryModel->findForTenant($categoryId, $tenantId) : null;
    }

    private function matchForTenant(int $matchId): ?object
    {
        $tenantId = (int) current_tenant_id();
        return $tenantId ? $this->matchModel->findForTenant($matchId, $tenantId) : null;
    }

    private function participantOptions(object $category): array
    {
        $db = \Config\Database::connect();
        $tenantId = (int) current_tenant_id();
        $builder = $db->table('tournament_registrations r')
            ->where('r.tenant_id', $tenantId)->where('r.tournament_id', (int) $category->tournament_id)
            ->where('r.category_id', (int) $category->id)->where('r.approval_status', 'approved')
            ->where('r.registration_status !=', 'cancelled')->where('r.deleted_at', null);
        $options = [];
        if (str_starts_with((string) ($category->category_type ?? ''), 'single_')) {
            $rows = $builder->select('r.player_id AS participant_id, COALESCE(p.full_name, r.contact_name) AS participant_label')
                ->join('players p', 'p.id = r.player_id AND p.tenant_id = r.tenant_id AND p.deleted_at IS NULL', 'left')->where('r.player_id IS NOT NULL', null, false)->get()->getResult();
        } else {
            $rows = $builder->select('r.team_id AS participant_id, COALESCE(t.team_name, CONCAT("Đội #", r.team_id)) AS participant_label')
                ->join('teams t', 't.id = r.team_id AND t.tenant_id = r.tenant_id AND t.deleted_at IS NULL', 'left')->where('r.team_id IS NOT NULL', null, false)->get()->getResult();
        }
        foreach ($rows as $row) if ((int) $row->participant_id) $options[(int) $row->participant_id] = (string) $row->participant_label;
        return $options;
    }
}
