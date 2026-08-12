<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class LiveScores extends BaseController
{
    public function index()
    {
        return $this->render('public/live_scores/index', [
            'pageTitle' => 'Live Scores',
            'data' => service('liveScoreService')->getPublicBracketData(current_tenant_id(), $this->request->getGet('tournament_id')),
        ]);
    }

    public function tv()
    {
        $sequence = $this->normalizeSequence((string) $this->request->getGet('sequence'));
        $refresh = $this->request->getGet('refresh');

        return view('public/live_scores/tv', [
            'pageTitle' => 'TV Live Scores',
            'data' => service('liveScoreService')->getTvDisplayData(
                current_tenant_id(),
                $this->request->getGet('tournament_id'),
                [
                    'sequence' => $sequence,
                    'refresh_seconds' => is_numeric($refresh) ? (int) $refresh : null,
                ]
            ),
        ]);
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
