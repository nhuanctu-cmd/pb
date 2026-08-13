<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\LiveScoreService;

class LiveScores extends BaseController
{
    protected LiveScoreService $liveScoreService;

    public function __construct()
    {
        $this->liveScoreService = service('liveScoreService');
    }

    public function index()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->liveScoreService->getLiveMatches(
                $this->tenantId(),
                $this->request->getGet('tournament_id') ?: null,
                $this->liveScoreService->tvQueryDefaults($this->request->getGet() ?? [])
            ),
        ]);
    }

    public function tv()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $this->liveScoreService->getTvDisplayData(
                (int) ($this->request->getGet('tenant_id') ?: $this->tenantId()),
                $this->request->getGet('tournament_id') ?: null,
                array_merge(
                    $this->liveScoreService->tvQueryDefaults($this->request->getGet() ?? []),
                    ['sequence' => $this->normalizeSequence((string) $this->request->getGet('sequence'))]
                )
            ),
        ]);
    }

    public function bracket()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => service('liveScoreService')->getPublicBracketData($this->tenantId(), $this->request->getGet('tournament_id')),
        ]);
    }

    public function update(int $matchId)
    {
        if (! (is_superadmin() || has_role('admin') || has_role('referee'))) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Forbidden']);
        }

        $json = $this->request->getJSON(true) ?: [];
        return $this->response->setJSON(service('scoreService')->updateScore(
            $matchId,
            $json['sets'] ?? $this->request->getPost('sets') ?? [],
            $this->tenantId()
        ));
    }

    protected function tenantId(): ?int
    {
        return $this->request->api_tenant_id ?? current_tenant_id();
    }

    private function normalizeSequence(?string $raw): string
    {
        return $raw ? implode(',', array_filter(array_map('trim', preg_split('/\s*,\s*/', trim($raw), -1, PREG_SPLIT_NO_EMPTY)))) : '';
    }
}
