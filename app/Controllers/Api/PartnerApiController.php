<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PlayerCompetitiveProfileModel;
use App\Services\ApiResponseService;
use App\Services\PartnerApiService;
use Config\Database;

class PartnerApiController extends BaseController
{
    public function player(string $identifier)
    {
        $profileModel = model(PlayerCompetitiveProfileModel::class);
        $profile = ctype_digit($identifier)
            ? null
            : ($profileModel->findByNationalId($identifier) ?: $profileModel->findBySlug($identifier));
        if (! $profile || $profile->privacy_level !== 'public' || $profile->status === 'suspended') {
            return $this->api()->notFound('Không tìm thấy hồ sơ công khai.');
        }
        $player = Database::connect()->table('players')->select('id, full_name, region, avatar, status')->where('id', $profile->player_id)->where('status', 'active')->where('deleted_at', null)->get()->getRow();
        if (! $player) return $this->api()->notFound('Không tìm thấy vận động viên.');
        return $this->api()->success([
            'national_player_id' => $profile->national_player_id,
            'slug' => $profile->slug,
            'display_name' => $profile->display_name ?: $player->full_name,
            'province' => $player->region,
            'avatar_url' => $profile->avatar_url ?: $player->avatar,
            'status' => $profile->status,
            'rating' => $profile->internal_rating_summary,
            'ranking' => $profile->national_rank_summary,
        ]);
    }

    public function rankings()
    {
        $limit = max(1, min(100, (int) $this->request->getGet('limit')) ?: 20);
        $rows = service('rankingNetworkService')->leaderboard('national-pickleball', null, $limit);
        $data = array_map(static fn ($row, $index) => [
            'rank' => $index + 1,
            'player_id' => (int) $row->player_id,
            'name' => (string) ($row->full_name ?? ''),
            'player_code' => (string) ($row->player_code ?? ''),
            'points' => (float) ($row->points ?? 0),
            'match_count' => (int) ($row->match_count ?? 0),
        ], $rows, array_keys($rows));
        $api = $this->api();
        return $api->setMeta(['source' => 'official_ranking_ledger'])->success($data);
    }

    public function clubs()
    {
        $rows = Database::connect()->table('platform_clubs')->select('public_id, code, name, slug, province, city, logo_url, verification_status')->where('status', 'active')->whereIn('verification_status', ['verified', 'official'])->orderBy('name', 'ASC')->limit(100)->get()->getResult();
        return $this->api()->success($rows);
    }

    public function tournaments()
    {
        $db = Database::connect();
        $rows = $db->table('tournaments')->select('id, name_vi AS name, slug_vi AS slug, start_date, end_date, verification_level, status')->whereIn('status', ['open', 'closed', 'running', 'completed'])->where('deleted_at', null)->orderBy('start_date', 'DESC')->limit(100)->get()->getResult();
        return $this->api()->success($rows);
    }

    private function api(): ApiResponseService
    {
        return service('apiResponseService');
    }
}
