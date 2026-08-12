<?php

namespace App\Services;

use App\Models\MatchDisputeModel;
use App\Models\MatchResultModel;
use App\Models\UnifiedMatchModel;

class MatchGovernanceService
{
    private MatchDisputeModel $disputeModel;
    private UnifiedMatchModel $matchModel;
    private MatchResultModel $resultModel;

    public function __construct()
    {
        $this->disputeModel = model(MatchDisputeModel::class);
        $this->matchModel = model(UnifiedMatchModel::class);
        $this->resultModel = model(MatchResultModel::class);
    }

    public function open(int $matchId, ?int $tenantId, int $userId, array $data): array
    {
        $match = $this->findMatch($matchId, $tenantId);
        if (! $match) return ['success' => false, 'message' => 'Match không tồn tại.'];
        if (! in_array($match->status, ['confirmed', 'official'], true)) return ['success' => false, 'message' => 'Chỉ match đã xác nhận mới được dispute.'];
        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') return ['success' => false, 'message' => 'Lý do dispute là bắt buộc.'];
        $active = $this->disputeModel->where('match_id', $matchId)->whereIn('status', ['open', 'reviewing'])->first();
        if ($active) return ['success' => false, 'message' => 'Match đang có dispute mở.'];
        $id = $this->disputeModel->insert([
            'tenant_id' => $match->tenant_id ?: $tenantId,
            'match_id' => $matchId,
            'opened_by' => $userId,
            'reason_code' => $data['reason_code'] ?? 'result_challenge',
            'reason' => $reason,
            'evidence' => ! empty($data['evidence']) ? json_encode($data['evidence'], JSON_UNESCAPED_UNICODE) : null,
            'status' => 'open',
        ]);
        if (! $id) return ['success' => false, 'message' => 'Không tạo được dispute.'];
        $this->matchModel->update($matchId, ['status' => 'disputed', 'verification_status' => 'pending']);
        $result = $this->resultModel->where('match_id', $matchId)->first();
        if ($result) $this->resultModel->update($result->id, ['status' => 'disputed']);
        return ['success' => true, 'dispute' => $this->disputeModel->find($id)];
    }

    public function resolve(int $disputeId, ?int $tenantId, int $reviewerId, string $status, string $resolution): array
    {
        $dispute = $this->disputeModel->where('id', $disputeId)->first();
        if (! $dispute || ($tenantId !== null && (int) $dispute->tenant_id !== $tenantId)) return ['success' => false, 'message' => 'Dispute không thuộc tenant hiện tại.'];
        if (! in_array($status, ['reviewing', 'upheld', 'rejected', 'resolved'], true)) return ['success' => false, 'message' => 'Trạng thái xử lý không hợp lệ.'];
        $this->disputeModel->update($disputeId, ['status' => $status, 'resolution' => $resolution, 'resolved_by' => $reviewerId, 'resolved_at' => in_array($status, ['upheld', 'rejected', 'resolved'], true) ? date('Y-m-d H:i:s') : null]);
        if ($status === 'rejected') {
            $this->matchModel->update($dispute->match_id, ['status' => 'official', 'verification_status' => 'official', 'verified_by' => $reviewerId]);
            $result = $this->resultModel->where('match_id', $dispute->match_id)->first();
            if ($result) $this->resultModel->update($result->id, ['status' => 'official']);
        } elseif ($status === 'upheld') {
            $result = $this->resultModel->where('match_id', $dispute->match_id)->first();
            if ($result && $result->current_version_id) {
                service('ratingEngine')->reverseMatch((int) $dispute->match_id, (int) $result->current_version_id, $tenantId);
            }
            $this->matchModel->update($dispute->match_id, ['status' => 'disputed', 'verification_status' => 'pending']);
        }
        return ['success' => true, 'dispute' => $this->disputeModel->find($disputeId)];
    }

    private function findMatch(int $matchId, ?int $tenantId): ?object
    {
        $builder = $this->matchModel->where('id', $matchId);
        if ($tenantId !== null && $tenantId > 0) $builder->where('tenant_id', $tenantId);
        return $builder->first();
    }
}
