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
}
