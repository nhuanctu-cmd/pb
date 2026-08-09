<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BranchModel;
use App\Models\CoachAvailabilityModel;
use App\Models\CoachBlackoutModel;
use App\Models\CoachModel;
use App\Models\CourtModel;

class CoachingController extends BaseController
{
    private $service;
    private BranchModel $branchModel;
    private CourtModel $courtModel;
    private CoachModel $coachModel;
    private CoachAvailabilityModel $availabilityModel;
    private CoachBlackoutModel $blackoutModel;

    public function __construct()
    {
        $this->service = service('coachingService');
        $this->branchModel = new BranchModel();
        $this->courtModel = new CourtModel();
        $this->coachModel = new CoachModel();
        $this->availabilityModel = new CoachAvailabilityModel();
        $this->blackoutModel = new CoachBlackoutModel();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $branches = $tenantId ? $this->branchModel->getByTenant($tenantId) : [];
        $coaches = $tenantId ? $this->service->coaches($tenantId) : [];
        $courts = [];
        foreach ($branches as $branch) {
            $courts = array_merge($courts, $this->courtModel->getByBranch((int) $branch->id));
        }
        $date = $this->request->getGet('date') ?: date('Y-m-d');
        $availability = $tenantId ? $this->availabilityModel->where('tenant_id', $tenantId)->where('deleted_at', null)->orderBy('day_of_week')->orderBy('start_time')->findAll() : [];
        $blackouts = $tenantId ? $this->blackoutModel->where('tenant_id', $tenantId)->where('deleted_at', null)->where('status', 'active')->where('end_at >=', $date . ' 00:00:00')->orderBy('start_at')->findAll() : [];
        $sessions = $tenantId ? $this->service->sessions($tenantId, ['session_date' => $date]) : [];
        $players = [];
        $attendance = [];
        foreach ($sessions as $session) {
            $players[(int) $session->id] = $this->service->players((int) $session->id, $tenantId);
            $attendance[(int) $session->id] = $this->service->attendance((int) $session->id, $tenantId);
        }
        return $this->render('admin/coaching/index', compact('branches', 'coaches', 'courts', 'availability', 'blackouts', 'sessions', 'players', 'attendance', 'date') + ['pageTitle' => 'Coach & Clinic']);
    }

    public function storeCoach()
    {
        $result = $this->service->createCoach($this->request->getPost(), (int) current_tenant_id(), (int) user_id());
        return $this->message($result);
    }

    public function storeAvailability()
    {
        $result = $this->service->addAvailability($this->request->getPost(), (int) current_tenant_id());
        return $this->message($result);
    }

    public function storeBlackout()
    {
        $result = $this->service->addBlackout($this->request->getPost(), (int) current_tenant_id(), (int) user_id());
        return $this->message($result);
    }

    public function storeSession()
    {
        $result = $this->service->createSession($this->request->getPost(), (int) current_tenant_id(), (int) user_id());
        return $this->message($result);
    }

    public function approve(int $id)
    {
        return $this->message($this->service->approve($id, (int) current_tenant_id(), (int) user_id()));
    }

    public function cancel(int $id)
    {
        return $this->message($this->service->cancelSession($id, (int) current_tenant_id(), (int) user_id()));
    }

    public function attendance(int $id)
    {
        return $this->message($this->service->markAttendance($id, (string) $this->request->getPost('status'), (int) current_tenant_id(), (int) user_id()));
    }

    private function message(array $result)
    {
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Đã xử lý.');
    }
}
