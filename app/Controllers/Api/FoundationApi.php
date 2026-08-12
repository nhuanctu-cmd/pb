<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

/** API boundary for trust, governance, provenance and result-correction workflows. */
class FoundationApi extends BaseController
{
    public function provenance(string $entityType, int $entityId)
    {
        $records = service('provenanceService')->lineage(strtoupper($entityType), $entityId);
        return service('apiResponseService')->success(['entity_type' => strtoupper($entityType), 'entity_id' => $entityId, 'records' => $records]);
    }

    public function authority()
    {
        $result = service('governanceService')->createAuthority($this->payload(), $this->userId());
        return $this->writeResponse($result);
    }

    public function decision()
    {
        $data = $this->payload();
        $data['actor_id'] ??= $this->userId();
        $result = service('governanceService')->recordDecision($data);
        return $this->writeResponse($result);
    }

    public function sanction(int $sanctionId)
    {
        $data = $this->payload();
        $result = service('governanceService')->transitionSanction($sanctionId, (string) ($data['workflow_status'] ?? ''), $this->userId(), $data);
        return $this->writeResponse($result);
    }

    public function appeal()
    {
        $data = $this->payload();
        $data['opened_by'] ??= $this->userId();
        $result = service('governanceService')->openAppeal($data);
        return $this->writeResponse($result, true);
    }

    public function appealTransition(int $appealId)
    {
        $data = $this->payload();
        $result = service('governanceService')->transitionAppeal($appealId, (string) ($data['status'] ?? ''), $this->userId(), (string) ($data['reason'] ?? ''), (array) ($data['evidence'] ?? []));
        return $this->writeResponse($result);
    }

    public function correction(int $matchId)
    {
        $data = $this->payload();
        $result = service('resultCorrectionService')->request($matchId, $this->userId(), (array) ($data['requested_result'] ?? $data), (string) ($data['reason'] ?? ''), (array) ($data['evidence'] ?? []));
        return $this->writeResponse($result, true);
    }

    public function approveCorrection(int $requestId)
    {
        $data = $this->payload();
        $result = service('resultCorrectionService')->approve($requestId, $this->userId(), (string) ($data['reason'] ?? 'Approved by governance reviewer.'));
        return $this->writeResponse($result);
    }

    public function rejectCorrection(int $requestId)
    {
        $data = $this->payload();
        $result = service('resultCorrectionService')->reject($requestId, $this->userId(), (string) ($data['reason'] ?? 'Rejected by governance reviewer.'));
        return $this->writeResponse($result);
    }

    private function payload(): array
    {
        return $this->request->getJSON(true) ?: $this->request->getPost();
    }

    private function userId(): int
    {
        return (int) ($this->request->api_user_id ?? (user()->id ?? 0));
    }

    private function writeResponse(array $result, bool $created = false)
    {
        return ! empty($result['success'])
            ? ($created ? service('apiResponseService')->created($result) : service('apiResponseService')->updated($result))
            : service('apiResponseService')->error($result['message'] ?? 'Không thể xử lý workflow.');
    }
}
