<?php

namespace App\Services;

use App\Models\AiSchedulingRequestModel;
use App\Models\BranchModel;
use App\Models\TournamentModel;
use App\Models\JobModel;

class AiSchedulingService
{
    private AiSchedulingRequestModel $requestModel;
    private BranchModel $branchModel;
    private TournamentModel $tournamentModel;
    private JobModel $jobModel;

    public function __construct()
    {
        $this->requestModel = new AiSchedulingRequestModel();
        $this->branchModel = new BranchModel();
        $this->tournamentModel = new TournamentModel();
        $this->jobModel = new JobModel();
    }

    public function requests(int $tenantId): array { return $this->requestModel->where('tenant_id', $tenantId)->orderBy('created_at', 'DESC')->findAll(50); }

    public function create(array $data, int $tenantId, ?int $userId = null): array
    {
        $from = (string) ($data['date_from'] ?? ''); $to = (string) ($data['date_to'] ?? '');
        $branchId = !empty($data['branch_id']) ? (int) $data['branch_id'] : null; $tournamentId = !empty($data['tournament_id']) ? (int) $data['tournament_id'] : null;
        $provider = (string) ($data['provider'] ?? 'local'); $matchMinutes = (int) ($data['match_minutes'] ?? 60); $restMinutes = (int) ($data['rest_minutes'] ?? 30);
        if (!$tenantId || !self::isValidDateRange($from, $to, 31) || ($branchId && !$this->branchModel->findForTenant($branchId, $tenantId)) || ($tournamentId && !$this->tournamentModel->findForTenant($tournamentId, $tenantId)) || !in_array($provider, ['local', 'or_tools'], true) || $matchMinutes < 15 || $matchMinutes > 180 || $restMinutes < 0 || $restMinutes > 240) return ['success' => false, 'message' => 'Yêu cầu AI scheduling không hợp lệ.'];
        $db = \Config\Database::connect(); $db->transStart();
        $id = $this->requestModel->insert(['tenant_id' => $tenantId, 'branch_id' => $branchId, 'tournament_id' => $tournamentId, 'requested_by' => $userId, 'date_from' => $from, 'date_to' => $to, 'match_minutes' => $matchMinutes, 'rest_minutes' => $restMinutes, 'provider' => $provider, 'constraints_json' => json_encode($data['constraints'] ?? [], JSON_UNESCAPED_UNICODE), 'status' => 'queued']);
        if (!$id) { $db->transRollback(); return ['success' => false, 'message' => 'Không thể tạo yêu cầu AI scheduling.']; }
        $jobId = $this->jobModel->push('ai_schedule', ['request_id' => (int) $id, 'tenant_id' => $tenantId], 0, 3);
        $db->transComplete(); if (!$db->transStatus() || !$jobId) return ['success' => false, 'message' => 'Không thể xếp hàng yêu cầu AI scheduling.'];
        $this->audit('request_created', (int) $id, $tenantId, ['job_id' => $jobId, 'provider' => $provider]);
        return ['success' => true, 'id' => (int) $id, 'job_id' => $jobId, 'message' => 'Đã đưa yêu cầu vào hàng đợi.'];
    }

    public function run(int $requestId, int $tenantId, ?int $userId = null): array
    {
        $db = \Config\Database::connect(); $db->transStart(); $request = $this->requestModel->findForUpdate($requestId, $tenantId);
        if (!$request || !in_array($request->status, ['queued', 'failed'], true)) { $db->transRollback(); return ['success' => false, 'message' => 'Yêu cầu không thể chạy lại.']; }
        $this->requestModel->update($requestId, ['status' => 'running', 'error_message' => null]); $db->transComplete();
        if (!$db->transStatus()) return ['success' => false, 'message' => 'Không thể khóa yêu cầu scheduling.'];
        try {
            $result = $this->buildSuggestion($request, $tenantId);
            $this->requestModel->update($requestId, ['status' => 'completed', 'result_json' => json_encode($result, JSON_UNESCAPED_UNICODE)]);
            $this->audit('request_completed', $requestId, $tenantId, ['user_id' => $userId, 'engine' => $result['engine'], 'matches' => count($result['suggestions'])]);
            return ['success' => true, 'result' => $result, 'message' => 'Đã tạo đề xuất lịch.'];
        } catch (\Throwable $exception) {
            $this->requestModel->update($requestId, ['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 1000)]);
            $this->audit('request_failed', $requestId, $tenantId, ['error' => $exception->getMessage()]);
            return ['success' => false, 'message' => 'AI scheduling thất bại: ' . $exception->getMessage()];
        }
    }

    public static function isValidDateRange(string $from, string $to, int $maxDays = 31): bool
    {
        $a = \DateTimeImmutable::createFromFormat('!Y-m-d', $from); $b = \DateTimeImmutable::createFromFormat('!Y-m-d', $to);
        return $a && $b && $a->format('Y-m-d') === $from && $b->format('Y-m-d') === $to && $a <= $b && ($b->diff($a)->days ?? PHP_INT_MAX) <= $maxDays;
    }

    public static function buildSlots(string $from, string $to, array $courtIds, int $matchMinutes = 60, int $restMinutes = 30): array
    {
        if (!self::isValidDateRange($from, $to) || !$courtIds || $matchMinutes < 15 || $restMinutes < 0) return [];
        $slots = []; $date = new \DateTimeImmutable($from); $endDate = new \DateTimeImmutable($to);
        while ($date <= $endDate) { foreach ($courtIds as $courtId) { $cursor = $date->setTime(8, 0); $limit = $date->setTime(22, 0); while ($cursor->modify('+' . $matchMinutes . ' minutes') <= $limit) { $finish = $cursor->modify('+' . $matchMinutes . ' minutes'); $slots[] = ['court_id' => (int) $courtId, 'date' => $date->format('Y-m-d'), 'start_time' => $cursor->format('H:i:s'), 'end_time' => $finish->format('H:i:s')]; $cursor = $finish->modify('+' . $restMinutes . ' minutes'); } } $date = $date->modify('+1 day'); }
        return $slots;
    }

    private function buildSuggestion(object $request, int $tenantId): array
    {
        $builder = \Config\Database::connect()->table('courts')->select('id')->where('tenant_id', $tenantId)->where('status', 'available')->where('deleted_at', null);
        if (!empty($request->branch_id)) $builder->where('branch_id', (int) $request->branch_id);
        $courtIds = array_map('intval', array_column($builder->get()->getResultArray(), 'id'));
        $slots = self::buildSlots($request->date_from, $request->date_to, $courtIds, (int) $request->match_minutes, (int) $request->rest_minutes);
        $matches = [];
        if (!empty($request->tournament_id)) $matches = \Config\Database::connect()->table('tournament_matches')->select('id, team_a_id, team_b_id')->where('tenant_id', $tenantId)->where('tournament_id', (int) $request->tournament_id)->where('status', 'scheduled')->orderBy('match_no', 'ASC')->get()->getResultArray();
        $suggestions = []; foreach (array_values($matches) as $index => $match) if (isset($slots[$index])) $suggestions[] = ['match_id' => (int) $match['id'], 'team_a_id' => (int) ($match['team_a_id'] ?? 0), 'team_b_id' => (int) ($match['team_b_id'] ?? 0), 'slot' => $slots[$index]];
        return ['engine' => $request->provider === 'or_tools' ? 'local_fallback' : 'local_heuristic', 'provider_requested' => $request->provider, 'court_count' => count($courtIds), 'match_count' => count($matches), 'suggestions' => $suggestions];
    }

    private function audit(string $action, int $id, int $tenantId, array $data): void { if (function_exists('log_audit')) log_audit(['action' => 'ai_scheduling_' . $action, 'entity_type' => 'ai_scheduling_request', 'entity_id' => $id, 'tenant_id' => $tenantId, 'metadata' => $data]); }
}
