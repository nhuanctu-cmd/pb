<?php

namespace App\Controllers\Public;

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
        return $this->render('public/live_scores/index', [
            'pageTitle' => 'Live Scores',
            'data' => $this->liveScoreService->getPublicBracketData(current_tenant_id(), $this->request->getGet('tournament_id')),
        ]);
    }

    public function tv()
    {
        $queryOptions = $this->liveScoreService->tvQueryDefaults($this->request->getGet() ?? []);
        $sequence = $this->normalizeSequence((string) $this->request->getGet('sequence'));
        $rawSequence = $sequence ?: 'live,next,call,results';
        $tournamentId = (int) $this->request->getGet('tournament_id');
        $tenantId = (int) current_tenant_id();

        $queryOptions['sequence'] = $rawSequence;
        $displayData = $this->liveScoreService->getTvDisplayData(
            $tenantId,
            $tournamentId ?: null,
            $queryOptions
        );

        return view('public/live_scores/tv', [
            'pageTitle' => 'TV Live Scores',
            'data' => $displayData + [
                'api_endpoint' => $this->buildTvApiEndpoint(
                    (int) $tournamentId,
                    [
                        'sequence' => $rawSequence,
                        'refresh' => $queryOptions['refresh_seconds'],
                        'branch_id' => $queryOptions['branch_id'],
                        'date' => $queryOptions['date'],
                    ]
                ),
            ],
        ]);
    }

    protected function buildTvApiEndpoint(int $tournamentId, array $options): string
    {
        $query = [
            'sequence' => $options['sequence'] ?? 'live,next,call,results',
            'refresh' => is_numeric($options['refresh'] ?? null) ? (int) $options['refresh'] : null,
            'branch_id' => is_numeric($options['branch_id'] ?? null) ? (int) $options['branch_id'] : null,
            'date' => $options['date'] ?? null,
        ];

        $query = array_filter($query, static fn ($value) => $value !== null && $value !== '' && $value !== false);
        if ($tournamentId > 0) {
            $query['tournament_id'] = $tournamentId;
        }
        if (is_numeric(current_tenant_id())) {
            $query['tenant_id'] = current_tenant_id();
        }

        return base_url('api/v1/live-scores/tv' . (empty($query) ? '' : '?' . http_build_query($query)));
    }

    private function normalizeSequence(?string $raw): string
    {
        return $raw ? implode(',', array_filter(array_map('trim', preg_split('/\s*,\s*/', trim($raw), -1, PREG_SPLIT_NO_EMPTY)))) : '';
    }

    public function bracket()
    {
        return $this->index();
    }
}
