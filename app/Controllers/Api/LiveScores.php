<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class LiveScores extends BaseController
{
    public function index()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => service('liveScoreService')->getLiveMatches($this->tenantId(), $this->request->getGet('tournament_id')),
        ]);
    }

    public function tv()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => service('liveScoreService')->getTvDisplayData($this->tenantId(), $this->request->getGet('tournament_id')),
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
}
