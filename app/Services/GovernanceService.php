<?php

namespace App\Services;

use App\Models\AppealEvidenceModel;
use App\Models\AppealModel;
use App\Models\GovernanceAuthorityModel;
use App\Models\GovernanceDecisionModel;
use App\Models\GovernancePolicyModel;
use App\Models\SanctionConditionModel;
use App\Models\SanctionReviewModel;
use App\Models\TournamentSanctionModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/** Domain workflow for authority-backed official decisions. */
class GovernanceService
{
    private BaseConnection $db;
    private GovernanceAuthorityModel $authorityModel;
    private GovernancePolicyModel $policyModel;
    private GovernanceDecisionModel $decisionModel;
    private AppealModel $appealModel;
    private AppealEvidenceModel $evidenceModel;
    private TournamentSanctionModel $sanctionModel;
    private SanctionReviewModel $reviewModel;
    private SanctionConditionModel $conditionModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->authorityModel = model(GovernanceAuthorityModel::class);
        $this->policyModel = model(GovernancePolicyModel::class);
        $this->decisionModel = model(GovernanceDecisionModel::class);
        $this->appealModel = model(AppealModel::class);
        $this->evidenceModel = model(AppealEvidenceModel::class);
        $this->sanctionModel = model(TournamentSanctionModel::class);
        $this->reviewModel = model(SanctionReviewModel::class);
        $this->conditionModel = model(SanctionConditionModel::class);
    }

    public function createAuthority(array $data, ?int $actorId = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $type = strtoupper(trim((string) ($data['authority_type'] ?? '')));
        if ($name === '' || $type === '') return ['success' => false, 'message' => 'Authority name and type are required.'];
        $uuid = $this->uuid();
        $id = $this->authorityModel->insert(['uuid' => $uuid, 'parent_id' => $data['parent_id'] ?? null, 'name' => $name, 'authority_type' => $type, 'country_code' => $data['country_code'] ?? null, 'scope_reference' => $data['scope_reference'] ?? null, 'status' => $data['status'] ?? 'active', 'effective_from' => $data['effective_from'] ?? date('Y-m-d H:i:s'), 'effective_to' => $data['effective_to'] ?? null, 'created_by' => $actorId]);
        return $id ? ['success' => true, 'authority' => $this->authorityModel->find($id)] : ['success' => false, 'message' => 'Unable to create authority.'];
    }

    public function recordDecision(array $data): array
    {
        foreach (['subject_type', 'subject_id', 'authority_id', 'actor_id', 'decision', 'reason'] as $required) if (empty($data[$required])) return ['success' => false, 'message' => $required . ' is required.'];
        $id = $this->decisionModel->insert(['uuid' => $this->uuid(), 'subject_type' => strtoupper($data['subject_type']), 'subject_id' => (int) $data['subject_id'], 'authority_id' => (int) $data['authority_id'], 'policy_id' => $data['policy_id'] ?? null, 'actor_id' => (int) $data['actor_id'], 'decision' => strtoupper($data['decision']), 'reason' => trim((string) $data['reason']), 'evidence' => $this->json($data['evidence'] ?? null), 'audit_log_id' => $data['audit_log_id'] ?? null, 'created_at' => date('Y-m-d H:i:s')]);
        return $id ? ['success' => true, 'decision' => $this->decisionModel->find($id)] : ['success' => false, 'message' => 'Unable to record governance decision.'];
    }

    public function transitionSanction(int $sanctionId, string $workflowStatus, int $actorId, array $data = []): array
    {
        $allowed = ['draft', 'submitted', 'under_review', 'approved', 'rejected', 'sanctioned'];
        if (! in_array($workflowStatus, $allowed, true)) return ['success' => false, 'message' => 'Invalid sanction workflow status.'];
        $sanction = $this->sanctionModel->find($sanctionId);
        if (! $sanction) return ['success' => false, 'message' => 'Sanction not found.'];
        $update = ['workflow_status' => $workflowStatus];
        if ($workflowStatus === 'submitted') $update += ['submitted_by' => $actorId, 'submitted_at' => date('Y-m-d H:i:s')];
        if ($workflowStatus === 'approved' || $workflowStatus === 'sanctioned') $update += ['approved_by' => $actorId, 'approved_at' => date('Y-m-d H:i:s'), 'authority_id' => $data['authority_id'] ?? ($sanction->authority_id ?? null), 'ruleset_version_id' => $data['ruleset_version_id'] ?? ($sanction->ruleset_version_id ?? null), 'policy_snapshot' => $this->json($data['policy_snapshot'] ?? null)];
        if (! $this->sanctionModel->update($sanctionId, $update)) return ['success' => false, 'message' => 'Unable to update sanction.'];
        return ['success' => true, 'sanction' => $this->sanctionModel->find($sanctionId)];
    }

    public function openAppeal(array $data): array
    {
        foreach (['subject_type', 'subject_id', 'opened_by', 'reason'] as $required) if (empty($data[$required])) return ['success' => false, 'message' => $required . ' is required.'];
        $id = $this->appealModel->insert(['uuid' => $this->uuid(), 'tenant_id' => $data['tenant_id'] ?? null, 'subject_type' => strtoupper($data['subject_type']), 'subject_id' => (int) $data['subject_id'], 'opened_by' => (int) $data['opened_by'], 'authority_id' => $data['authority_id'] ?? null, 'status' => 'open', 'reason' => trim((string) $data['reason']), 'parent_appeal_id' => $data['parent_appeal_id'] ?? null, 'created_at' => date('Y-m-d H:i:s')]);
        return $id ? ['success' => true, 'appeal' => $this->appealModel->find($id)] : ['success' => false, 'message' => 'Unable to open appeal.'];
    }

    public function transitionAppeal(int $appealId, string $status, int $actorId, string $reason, array $evidence = []): array
    {
        $allowed = ['open', 'evidence_collection', 'review', 'decision', 'appealed_again', 'final', 'rejected'];
        if (! in_array($status, $allowed, true)) return ['success' => false, 'message' => 'Invalid appeal status.'];
        $appeal = $this->appealModel->find($appealId);
        if (! $appeal) return ['success' => false, 'message' => 'Appeal not found.'];
        $decisionId = null;
        if (in_array($status, ['decision', 'final', 'rejected'], true) && $appeal->authority_id) {
            $decision = $this->recordDecision(['subject_type' => 'APPEAL', 'subject_id' => $appealId, 'authority_id' => $appeal->authority_id, 'actor_id' => $actorId, 'decision' => $status, 'reason' => $reason, 'evidence' => $evidence]);
            if (! $decision['success']) return $decision;
            $decisionId = $decision['decision']->id;
        }
        foreach ($evidence as $item) $this->evidenceModel->insert(['appeal_id' => $appealId, 'submitted_by' => $actorId, 'evidence_type' => $item['type'] ?? 'reference', 'reference' => $item['reference'] ?? '', 'notes' => $item['notes'] ?? null, 'created_at' => date('Y-m-d H:i:s')]);
        $this->appealModel->update($appealId, ['status' => $status, 'decision_id' => $decisionId, 'decided_by' => in_array($status, ['decision', 'final', 'rejected'], true) ? $actorId : null, 'decided_at' => in_array($status, ['final', 'rejected'], true) ? date('Y-m-d H:i:s') : null]);
        return ['success' => true, 'appeal' => $this->appealModel->find($appealId)];
    }

    private function json($value): ?string { return $value === null ? null : (is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)); }
    private function uuid(): string { $bytes = random_bytes(16); $bytes[6] = chr((ord($bytes[6]) & 15) | 64); $bytes[8] = chr((ord($bytes[8]) & 63) | 128); return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)); }
}
