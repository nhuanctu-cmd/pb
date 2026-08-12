<?php

namespace App\Controllers\Public;

use App\Controllers\BaseController;

class PublicPortal extends BaseController
{
    public function ranking()
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $discipline = (string) ($this->request->getGet('discipline') ?: 'singles');
        $discipline = in_array($discipline, ['singles', 'doubles', 'mixed_doubles'], true) ? $discipline : 'singles';

        return $this->render('public/ranking', [
            'pageTitle' => 'National Leaderboard | Xếp hạng Pickleball',
            'metaDescription' => 'Bảng xếp hạng Pickleball quốc gia với hồ sơ vận động viên, Rating, Reliability và lịch sử thi đấu.',
            'rankings' => service('publicPortalService')->topRankingsForPublic($tenantId, $discipline),
            'discipline' => $discipline,
            'lastUpdated' => date('Y-m-d H:i:s'),
        ]);
    }
    public function players() { return redirect()->to('/#player-search'); }
    public function player(string $identifier)
    {
        $profile = service('publicPortalService')->playerProfile($identifier, (int) (current_tenant_id() ?: 1));
        if (! $profile) return redirect()->to('/players')->with('error', 'Không tìm thấy hồ sơ vận động viên công khai.');

        return $this->render('public/players/profile', [
            'pageTitle' => $profile['player']->full_name . ' | Athlete Profile',
            'metaDescription' => 'Hồ sơ vận động viên, Rating, lịch sử thi đấu và bài viết liên quan.',
            ...$profile,
        ]);
    }

    public function article(int $id)
    {
        $article = service('publicPortalService')->publicArticle($id, (int) (current_tenant_id() ?: 1));
        if (! $article) {
            return redirect()->to('/')->with('error', 'Không tìm thấy bài viết công khai.');
        }

        return $this->render('public/articles/show', [
            'pageTitle' => $article['post']->title . ' | Pickleball Việt Nam',
            'metaDescription' => mb_strimwidth(strip_tags((string) $article['post']->body), 0, 155, '…', 'UTF-8'),
            ...$article,
        ]);
    }
    public function matches() { return redirect()->to('/live-scores'); }
    public function clubs() { return redirect()->to('/#club-ranking'); }
    public function calendar() { return redirect()->to('/tournaments'); }
    public function live() { return redirect()->to('/live-scores'); }
    public function verify() { return redirect()->to('/#verify'); }
}
