<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\TournamentSchedulerService;

class TournamentSchedulerController extends BaseController
{
    private TournamentSchedulerService $scheduler;

    public function __construct()
    {
        $this->scheduler = new TournamentSchedulerService();
    }

    public function index()
    {
        $categoryId = (int) ($this->request->getGet('category_id') ?? 0);
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

        $this->scheduler->rerunUnlockedSchedule($categoryId);

        return redirect()->to('/admin/tournaments/scheduler?category_id=' . $categoryId)->with('success', 'Đã chạy lại các trận chưa khóa.');
    }

    public function lock(int $id)
    {
        $this->scheduler->lockMatch($id);
        return redirect()->back()->with('success', 'Đã khóa trận.');
    }

    public function unlock(int $id)
    {
        $this->scheduler->unlockMatch($id);
        return redirect()->back()->with('success', 'Đã mở khóa trận.');
    }

    public function moveTeam()
    {
        $teamId = (int) $this->request->getPost('team_id');
        $groupId = (int) $this->request->getPost('group_id');
        $success = $teamId && $groupId && $this->scheduler->moveTeamToGroup($teamId, $groupId);

        return $this->response->setJSON(['success' => $success]);
    }
}
