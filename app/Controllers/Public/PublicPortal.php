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

    public function players()
    {
        return redirect()->to('/#player-search');
    }

    public function player(string $identifier)
    {
        $profile = service('publicPortalService')->playerProfile($identifier, (int) (current_tenant_id() ?: 1));
        if (! $profile) {
            return redirect()->to('/players')->with('error', 'Không tìm thấy hồ sơ vận động viên công khai.');
        }

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

    public function venues()
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $filters = $this->request->getGet();

        return $this->render('public/venues/list', [
            'pageTitle' => 'Cơ sở Pickleball',
            'metaDescription' => 'Tra cứu danh sách cơ sở, chi nhánh và sân Pickleball.',
            'filters' => $filters,
            'venues' => service('publicPortalService')->venueList($tenantId, $filters),
        ]);
    }

    public function venue(string $identifier)
    {
        $activeTab = $this->resolveVenueActiveTab();
        $tenantId = (int) (current_tenant_id() ?: 1);
        $venue = service('publicPortalService')->venueDetail((string) $identifier, $tenantId, $activeTab);
        if (! $venue) {
            return redirect()->to('/venues')->with('error', 'Không tìm thấy cơ sở.');
        }

        return $this->render('public/venues/detail', [
            'pageTitle' => 'Chi tiết cơ sở Pickleball',
            'metaDescription' => 'Chi tiết cơ sở, chi nhánh và sân của nhà điều hành.',
            'venue' => $venue,
            'activeTab' => $activeTab,
        ]);
    }

    public function matches()
    {
        return redirect()->to('/live-scores');
    }

    public function clubs()
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $filters = $this->request->getGet();

        return $this->render('public/clubs/list', [
            'pageTitle' => 'Danh bạ câu lạc bộ',
            'metaDescription' => 'Tra cứu câu lạc bộ hoạt động, đội ngũ và lịch sử nổi bật.',
            'filters' => $filters,
            'clubs' => service('publicPortalService')->clubList($tenantId, $filters),
        ]);
    }

    public function club(string $identifier)
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $club = service('publicPortalService')->clubDetail((string) $identifier, $tenantId, 'overview');
        if (! $club) {
            return redirect()->to('/clubs')->with('error', 'Không tìm thấy câu lạc bộ.');
        }

        return $this->render('public/clubs/detail', [
            'pageTitle' => 'Chi tiết câu lạc bộ',
            'metaDescription' => 'Chi tiết câu lạc bộ, thành viên và lịch sử hoạt động.',
            'club' => $club,
            'activeTab' => 'overview',
        ]);
    }

    public function clubMembers(string $identifier)
    {
        return $this->renderClubWithTab($identifier, 'members');
    }

    public function clubHistory(string $identifier)
    {
        return $this->renderClubWithTab($identifier, 'history');
    }

    public function clubPosts(string $identifier)
    {
        return $this->renderClubWithTab($identifier, 'posts');
    }

    public function clubTournaments(string $identifier)
    {
        return $this->renderClubWithTab($identifier, 'tournaments');
    }

    private function renderClubWithTab(string $identifier, string $activeTab): \CodeIgniter\HTTP\ResponseInterface
    {
        $tenantId = (int) (current_tenant_id() ?: 1);
        $club = service('publicPortalService')->clubDetail((string) $identifier, $tenantId, $activeTab);
        if (! $club) {
            return redirect()->to('/clubs')->with('error', 'Không tìm thấy câu lạc bộ.');
        }

        return $this->render('public/clubs/detail', [
            'pageTitle' => 'Chi tiết câu lạc bộ',
            'metaDescription' => 'Chi tiết câu lạc bộ, thành viên và lịch sử hoạt động.',
            'club' => $club,
            'activeTab' => $activeTab,
        ]);
    }

    private function resolveVenueActiveTab(): string
    {
        $uri = service('uri');
        $segments = $uri->getSegments();
        $count = count($segments);
        if ($count >= 2 && in_array($segments[$count - 1], ['courts', 'schedule', 'members', 'history'], true)) {
            return $segments[$count - 1];
        }
        return 'overview';
    }

    public function calendar()
    {
        return redirect()->to('/tournaments');
    }

    public function live()
    {
        return redirect()->to('/live-scores');
    }

    public function verify()
    {
        return redirect()->to('/#verify');
    }
}
