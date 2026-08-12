<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Database;

class RatingGovernanceController extends BaseController
{
    protected $db;
    public function __construct() { $this->db = Database::connect(); }

    public function index()
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $profiles = $this->db->table('player_rating_profiles r')->select('r.*, p.full_name, p.player_code, d.code AS discipline, b.code AS skill_band')->join('players p', 'p.id = r.player_id', 'left')->join('rating_disciplines d', 'd.id = r.discipline_id', 'left')->join('skill_level_bands b', 'b.id = r.skill_band_id', 'left')->where('r.tenant_id', $tenantId)->orderBy('r.rating_value', 'DESC')->limit(50)->get()->getResult();
        $claims = $this->db->table('player_skill_claims c')->select('c.*, p.full_name, d.code AS discipline')->join('players p', 'p.id = c.player_id', 'left')->join('rating_disciplines d', 'd.id = c.discipline_id', 'left')->where('c.tenant_id', $tenantId)->whereIn('c.verification_status', ['pending', 'verified'])->orderBy('c.created_at', 'DESC')->limit(50)->get()->getResult();
        $flags = $this->db->tableExists('rating_integrity_flags') ? $this->db->table('rating_integrity_flags f')->select('f.*, p.full_name')->join('players p', 'p.id = f.player_id', 'left')->where('f.tenant_id', $tenantId)->where('f.status', 'open')->orderBy('f.risk_score', 'DESC')->limit(50)->get()->getResult() : [];
        $imports = $this->db->tableExists('rating_import_jobs') ? $this->db->table('rating_import_jobs')->where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->limit(30)->get()->getResult() : [];
        return $this->render('admin/rating/index', ['pageTitle' => 'Rating Governance', 'profiles' => $profiles, 'claims' => $claims, 'flags' => $flags, 'imports' => $imports, 'tenantId' => $tenantId]);
    }

    public function verifyClaim(int $claimId)
    {
        $status = in_array($this->request->getPost('status'), ['verified', 'rejected'], true) ? $this->request->getPost('status') : 'rejected';
        $claim = $this->db->table('player_skill_claims')->where('id', $claimId)->where('tenant_id', (int) current_tenant_id())->get()->getRow();
        if (! $claim) return redirect()->back()->with('error', 'Claim không tồn tại.');
        $this->db->table('player_skill_claims')->where('id', $claimId)->update(['verification_status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        service('auditLogService')->log('verify', 'rating_claim', 'player_skill_claims', $claimId, ['verification_status' => $claim->verification_status], ['verification_status' => $status], 'Rating claim review', (int) $claim->tenant_id, null, (int) user_id());
        return redirect()->back()->with('success', 'Đã cập nhật claim.');
    }

    public function resolveFlag(int $flagId)
    {
        $status = in_array($this->request->getPost('status'), ['approved', 'rejected', 'blocked'], true) ? $this->request->getPost('status') : 'rejected';
        $reason = trim((string) $this->request->getPost('reason'));
        if ($reason === '') return redirect()->back()->with('error', 'Integrity review bắt buộc phải có lý do.');
        $flag = $this->db->table('rating_integrity_flags')->where('id', $flagId)->where('tenant_id', (int) current_tenant_id())->get()->getRow();
        if (! $flag) return redirect()->back()->with('error', 'Integrity flag không tồn tại.');
        $this->db->table('rating_integrity_flags')->where('id', $flagId)->update(['status' => $status, 'resolved_by' => (int) user_id(), 'resolved_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        service('auditLogService')->log('resolve', 'rating_integrity', 'rating_integrity_flags', $flagId, ['status' => $flag->status], ['status' => $status, 'reason' => $reason], 'Rating integrity review', (int) $flag->tenant_id, null, (int) user_id());
        return redirect()->back()->with('success', 'Đã xử lý integrity flag.');
    }

    public function approveImport(int $jobId)
    {
        return $this->reviewImport($jobId, 'approve');
    }

    public function rejectImport(int $jobId)
    {
        return $this->reviewImport($jobId, 'reject', true);
    }

    public function adjust()
    {
        $result = service('ratingAdjustmentService')->adjust((int) current_tenant_id(), (int) $this->request->getPost('player_id'), (string) $this->request->getPost('discipline'), (float) $this->request->getPost('rating'), (string) $this->request->getPost('reason'), (int) user_id());
        return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message'] ?? ($result['success'] ? 'Đã điều chỉnh rating.' : 'Không thể điều chỉnh rating.'));
    }

    private function reviewImport(int $jobId, string $decision, bool $requiresReason = false)
    {
        $tenantId = (int) current_tenant_id();
        $reason = $this->request->getPost('reason');
        $result = service('ratingImportService')->reviewForGovernance($tenantId, $jobId, $decision, $requiresReason ? (string) $reason : (string) ($reason ?: null), (int) user_id());
        if (! empty($result['success'])) {
            return redirect()->back()->with('success', $result['message'] ?? ('Đã ' . $decision . ' import job.'));
        }
        return redirect()->back()->with('error', $result['message'] ?? ('Không thể ' . $decision . ' import job.'));
    }
}
