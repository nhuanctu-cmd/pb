<?php

namespace App\Services;

use App\Models\MatchResultModel;
use App\Models\MatchResultVersionModel;
use App\Models\ResultCorrectionRequestModel;
use App\Models\UnifiedMatchModel;
use Config\Database;

/** Appends corrected result versions and schedules derived ledger compensation. */
class ResultCorrectionService
{
    private ResultCorrectionRequestModel $requestModel;
    private MatchResultModel $resultModel;
    private MatchResultVersionModel $versionModel;
    private UnifiedMatchModel $matchModel;

    public function __construct()
    {
        $this->requestModel = model(ResultCorrectionRequestModel::class);
        $this->resultModel = model(MatchResultModel::class);
        $this->versionModel = model(MatchResultVersionModel::class);
        $this->matchModel = model(UnifiedMatchModel::class);
    }

    public function request(int $matchId, int $requesterId, array $requestedResult, string $reason, array $evidence = [], ?int $tenantId = null): array
    {
        $match = $this->matchModel->where('id', $matchId)->first();
        if (! $match || ($tenantId !== null && (int) $match->tenant_id !== $tenantId)) return ['success' => false, 'code' => 'TENANT_ISOLATION', 'message' => 'Match không thuộc tenant hiện tại.'];
        $result = $this->resultModel->where('match_id', $matchId)->first();
        if (! $result || ! $result->current_version_id) return ['success' => false, 'message' => 'No current result version exists.'];
        if (trim($reason) === '') return ['success' => false, 'message' => 'Correction reason is required.'];
        $active = $this->requestModel->where('match_id', $matchId)->whereIn('status', ['open', 'reviewing'])->first();
        if ($active) return ['success' => false, 'message' => 'A correction request is already under review.'];
        $id = $this->requestModel->insert(['match_id' => $matchId, 'original_result_version_id' => $result->current_version_id, 'requested_result' => json_encode($requestedResult, JSON_UNESCAPED_UNICODE), 'reason' => $reason, 'evidence' => $evidence ? json_encode($evidence, JSON_UNESCAPED_UNICODE) : null, 'requester_id' => $requesterId, 'status' => 'open', 'created_at' => date('Y-m-d H:i:s')]);
        return $id ? ['success' => true, 'request' => $this->requestModel->find($id)] : ['success' => false, 'message' => 'Unable to open correction request.'];
    }

    public function approve(int $requestId, int $reviewerId, string $reason, ?int $tenantId = null): array
    {
        $request = $this->requestModel->find($requestId);
        if (! $request || ! in_array($request->status, ['open', 'reviewing'], true)) return ['success' => false, 'message' => 'Correction request is not reviewable.'];
        $current = $this->resultModel->where('match_id', $request->match_id)->first();
        $originalVersion = $this->versionModel->find($request->original_result_version_id);
        $match = $this->matchModel->find($request->match_id);
        if (! $current || ! $originalVersion || ! $match) return ['success' => false, 'message' => 'Original result version not found.'];
        if ($tenantId !== null && (int) $match->tenant_id !== $tenantId) return ['success' => false, 'code' => 'TENANT_ISOLATION', 'message' => 'Correction không thuộc tenant hiện tại.'];
        $payload = is_string($request->requested_result) ? json_decode($request->requested_result, true) : (array) $request->requested_result;
        $versionNo = ((int) $current->version_no) + 1;
        $db = Database::connect();
        $db->transStart();
        $versionId = $this->versionModel->insert(['match_id' => $request->match_id, 'version_no' => $versionNo, 'status' => 'official', 'result_type' => $payload['result_type'] ?? $originalVersion->result_type, 'winner_side' => $payload['winner_side'] ?? null, 'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE), 'submitted_by' => $request->requester_id, 'confirmed_by' => $reviewerId, 'change_reason' => $reason, 'created_at' => date('Y-m-d H:i:s')]);
        $this->resultModel->update($current->id, ['current_version_id' => $versionId, 'version_no' => $versionNo, 'status' => 'official', 'winner_side' => $payload['winner_side'] ?? null, 'published_at' => date('Y-m-d H:i:s')]);
        $matchData = ['status' => 'official', 'verification_status' => 'official', 'verified_by' => $reviewerId];
        foreach (array_keys($matchData) as $field) if (! $this->hasColumn($db, 'matches', $field)) unset($matchData[$field]);
        $this->matchModel->update($request->match_id, $matchData);
        $this->requestModel->update($requestId, ['status' => 'approved', 'reviewer_id' => $reviewerId, 'decision_reason' => $reason, 'new_result_version_id' => $versionId, 'reviewed_at' => date('Y-m-d H:i:s')]);
        $db->transComplete();
        if (! $db->transStatus()) return ['success' => false, 'message' => 'Correction transaction failed.'];
        $reversal = service('ratingEngine')->reverseMatch((int) $request->match_id, (int) $originalVersion->id, $match->tenant_id ?? null);
        if (! empty($match->tenant_id) && (int) $match->tenant_id > 0) {
            $matchTenant = (int) $match->tenant_id;
            service('ratingRebuildService')->queueFromMatch($matchTenant, (int) $request->match_id, null, 'correction-approved', ['request_id' => (int) $requestId, 'result_version_id' => (int) $originalVersion->id, 'new_result_version_id' => $versionId]);
        }
        return ['success' => true, 'new_result_version_id' => (int) $versionId, 'reversal' => $reversal, 'request' => $this->requestModel->find($requestId)];
    }

    public function reject(int $requestId, int $reviewerId, string $reason, ?int $tenantId = null): array
    {
        if ($tenantId !== null) {
            $request = $this->requestModel->find($requestId);
            $match = $request ? $this->matchModel->find($request->match_id) : null;
            if (! $match || (int) $match->tenant_id !== $tenantId) return ['success' => false, 'code' => 'TENANT_ISOLATION', 'message' => 'Correction không thuộc tenant hiện tại.'];
        }
        $updated = $this->requestModel->update($requestId, ['status' => 'rejected', 'reviewer_id' => $reviewerId, 'decision_reason' => $reason, 'reviewed_at' => date('Y-m-d H:i:s')]);
        return ['success' => (bool) $updated, 'request' => $this->requestModel->find($requestId)];
    }

    private function hasColumn($db, string $table, string $column): bool
    {
        return $db->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '` LIKE ?', [$column])->getRow() !== null;
    }
}
