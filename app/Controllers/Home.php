<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $discipline = (string) ($this->request->getGet('discipline') ?: 'singles');

        return $this->render('public/home', [
            'pageTitle' => service('language')->getLocale() === 'en'
                ? 'National Pickleball Ranking | National leaderboard'
                : 'National Pickleball Ranking | Bảng xếp hạng toàn quốc',
            'metaDescription' => service('language')->getLocale() === 'en'
                ? 'Explore national pickleball rankings, players, ratings, tournaments, schedules and official results.'
                : 'Tra cứu bảng xếp hạng Pickleball toàn quốc, VĐV, Rating, giải đấu, lịch thi đấu và kết quả chính thức.',
            'portal' => service('publicPortalService')->home($tenantId, $discipline),
        ]);
    }
}
