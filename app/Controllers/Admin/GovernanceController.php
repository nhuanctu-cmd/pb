<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class GovernanceController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function index()
    {
        $tenantId = (int) current_tenant_id();
        $disputes = $this->db->table('match_disputes d')->select('d.*, m.public_id, m.status AS match_status')->join('matches m', 'm.id = d.match_id', 'left')->where('d.tenant_id', $tenantId)->whereIn('d.status', ['open', 'reviewing'])->orderBy('d.created_at', 'ASC')->limit(100)->get()->getResult();
        $corrections = $this->db->table('result_correction_requests r')->select('r.*, m.public_id, m.status AS match_status')->join('matches m', 'm.id = r.match_id', 'left')->where('m.tenant_id', $tenantId)->whereIn('r.status', ['open', 'reviewing'])->orderBy('r.created_at', 'ASC')->limit(100)->get()->getResult();
        return $this->render('admin/governance/index', ['pageTitle' => 'Match Governance', 'tenantId' => $tenantId, 'disputes' => $disputes, 'corrections' => $corrections]);
    }

    public function resolveDispute(int $id)
    {
        $status = (string) $this->request->getPost('status');
        $result = service('matchGovernanceService')->resolve($id, (int) current_tenant_id(), (int) user_id(), $status, trim((string) $this->request->getPost('resolution')));
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['success'] ? 'Đã cập nhật dispute.' : ($result['message'] ?? 'Không thể cập nhật dispute.'));
    }

    public function approveCorrection(int $id)
    {
        $result = service('resultCorrectionService')->approve($id, (int) user_id(), trim((string) $this->request->getPost('reason')), (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['success'] ? 'Đã duyệt correction và tạo reversal.' : ($result['message'] ?? 'Không thể duyệt correction.'));
    }

    public function rejectCorrection(int $id)
    {
        $result = service('resultCorrectionService')->reject($id, (int) user_id(), trim((string) $this->request->getPost('reason')), (int) current_tenant_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['success'] ? 'Đã từ chối correction.' : ($result['message'] ?? 'Không thể từ chối correction.'));
    }
}
