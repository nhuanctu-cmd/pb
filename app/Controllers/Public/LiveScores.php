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
        return view('public/live_scores/tv', [
            'pageTitle' => 'TV Live Scores',
            'data' => service('liveScoreService')->getTvDisplayData(current_tenant_id(), $this->request->getGet('tournament_id')),
        ]);
    }

    public function bracket()
    {
        return $this->index();
    }
}
