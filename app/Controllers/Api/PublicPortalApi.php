<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PlayerCompetitiveProfileModel;
use App\Models\PlayerModel;

class PublicPortalApi extends BaseController
{
    public function home()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => service('publicPortalService')->home($this->tenantId(), (string) ($this->request->getGet('discipline') ?: 'singles')),
        ]);
    }

    public function search()
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => service('publicPortalService')->search((string) $this->request->getGet('q'), $this->tenantId()),
        ]);
    }

    public function ratingHistory(int $playerId)
    {
        $tenantId = (int) ($this->tenantId() ?: 1);
        $player = model(PlayerModel::class)->where('id', $playerId)->where('tenant_id', $tenantId)->where('status', 'active')->first();
        if (! $player) return service('apiResponseService')->notFound('Không tìm thấy vận động viên công khai.');
        $discipline = strtolower(trim((string) ($this->request->getGet('discipline') ?: 'singles')));
        if ($discipline === 'mixed') $discipline = 'mixed_doubles';
        if (! in_array($discipline, ['singles', 'doubles', 'mixed_doubles'], true)) $discipline = 'singles';
        $rows = service('ratingEngine')->history($tenantId, $playerId, $discipline, (int) ($this->request->getGet('limit') ?: 50));
        return service('apiResponseService')->success(['player_id' => $playerId, 'discipline' => $discipline, 'items' => array_map(static fn (object $row): array => ['id' => (int) $row->id, 'before_rating' => $row->before_rating !== null ? (float) $row->before_rating : null, 'after_rating' => $row->after_rating !== null ? (float) $row->after_rating : null, 'delta' => (float) $row->rating_delta, 'processed_at' => $row->processed_at, 'reason' => $row->reason], $rows)]);
    }

    public function countries()
    {
        return service('apiResponseService')->success(array_map(static fn (object $country): array => [
            'code' => $country->code, 'name' => $country->name_en, 'name_local' => $country->name_vi,
            'currency' => $country->default_currency, 'timezone' => $country->default_timezone,
        ], service('internationalFoundationService')->countries()));
    }

    public function playerCard(string $identifier)
    {
        $profile = $this->profile($identifier);
        if (! $profile || $profile->privacy_level !== 'public' || $profile->status === 'suspended') return service('apiResponseService')->notFound('Hồ sơ công khai không tồn tại.');
        $token = service('playerPassportService')->createCardToken((string) $profile->national_player_id);
        $verifyUrl = site_url('api/public/v1/players/card/verify?token=' . rawurlencode($token));
        return service('apiResponseService')->success([
            'national_player_id' => $profile->national_player_id,
            'display_name' => $profile->display_name,
            'slug' => $profile->slug,
            'card_status' => $profile->status,
            'issued_at' => date('c'),
            'expires_at' => date('c', time() + 2592000),
            'qr_payload' => $verifyUrl,
            'verify_url' => $verifyUrl,
        ]);
    }

    public function verifyPlayerCard()
    {
        $payload = service('playerPassportService')->verifyCardToken((string) $this->request->getGet('token'));
        if (! $payload) return service('apiResponseService')->unauthorized('Mã QR không hợp lệ hoặc đã hết hạn.');
        $profile = service('playerPassportService')->findByNationalId((string) $payload['npi']);
        if (! $profile || $profile->privacy_level !== 'public' || $profile->status === 'suspended') return service('apiResponseService')->notFound('Thẻ VĐV không còn công khai.');
        return service('apiResponseService')->success([
            'verified' => true, 'national_player_id' => $profile->national_player_id,
            'display_name' => $profile->display_name, 'slug' => $profile->slug,
            'status' => $profile->status, 'verified_at' => $profile->verified_at,
        ]);
    }

    private function profile(string $identifier): ?object
    {
        $model = model(PlayerCompetitiveProfileModel::class);
        return ctype_digit($identifier) ? null : ($model->findByNationalId($identifier) ?: $model->findBySlug($identifier));
    }

    protected function tenantId(): ?int
    {
        return $this->request->api_tenant_id ?? current_tenant_id();
    }
}
