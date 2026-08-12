<?php

namespace App\Services;

use App\Models\PlayerCompetitiveProfileModel;
use App\Models\ClubModel;
use App\Models\PlayerIdentityClaimModel;
use App\Models\PlayerClubMembershipModel;
use App\Models\PlayerModel;
use Config\Database;

class PlayerPassportService
{
    protected PlayerModel $playerModel;
    protected PlayerCompetitiveProfileModel $profileModel;
    protected PlayerIdentityClaimModel $claimModel;
    protected PlayerClubMembershipModel $membershipModel;
    protected ClubModel $clubModel;

    public function __construct()
    {
        $this->playerModel = model(PlayerModel::class);
        $this->profileModel = model(PlayerCompetitiveProfileModel::class);
        $this->claimModel = model(PlayerIdentityClaimModel::class);
        $this->membershipModel = model(PlayerClubMembershipModel::class);
        $this->clubModel = model(ClubModel::class);
    }

    /**
     * Tạo hoặc lấy hộ chiếu VĐV. Gán National Player ID nếu chưa có.
     */
    public function ensurePassport(int $playerId, ?array $data = null): array
    {
        $player = $this->playerModel->where('id', $playerId)->where('deleted_at', null)->first();
        if (! $player) {
            return ['success' => false, 'message' => 'Không tìm thấy VĐV.'];
        }

        $profile = $this->profileModel->findByPlayerId($playerId);
        if ($profile) {
            return ['success' => true, 'profile' => $profile];
        }

        $db = Database::connect();
        $db->transStart();

        $nationalId = $this->profileModel->generateNationalPlayerId();
        $slug = generate_slug($data['display_name'] ?? $player->full_name) ?: ('player-' . $playerId);
        $baseSlug = $slug;
        $i = 2;
        while ($this->profileModel->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $i++;
        }

        $insertData = [
            'player_id' => $playerId,
            'national_player_id' => $nationalId,
            'display_name' => $data['display_name'] ?? $player->full_name,
            'slug' => $slug,
            'province_id' => $data['province_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'club_id' => $data['club_id'] ?? null,
            'gender_category' => $data['gender_category'] ?? null,
            'status' => $data['status'] ?? 'unverified',
        ];

        $profileId = $this->profileModel->insert($insertData);

        // Tạo identity claim từ phone/email của player nếu có
        $this->syncPrimaryClaims($playerId, $player);

        $db->transComplete();

        if (! $db->transStatus() || ! $profileId) {
            return ['success' => false, 'message' => 'Không tạo được hộ chiếu VĐV.'];
        }

        return ['success' => true, 'profile' => $this->profileModel->find($profileId)];
    }

    public function findByNationalId(string $nationalPlayerId): ?object
    {
        return $this->profileModel->findByNationalId($nationalPlayerId);
    }

    public function findBySlug(string $slug): ?object
    {
        return $this->profileModel->findBySlug($slug);
    }

    /** Signed, short-lived token used by the national player QR card. */
    public function createCardToken(string $nationalPlayerId, int $ttlSeconds = 2592000): string
    {
        $payload = ['npi' => $nationalPlayerId, 'exp' => time() + max(300, min(31536000, $ttlSeconds))];
        $encoded = self::base64Url(json_encode($payload, JSON_UNESCAPED_SLASHES));
        return $encoded . '.' . hash_hmac('sha256', $encoded, (string) config('Encryption')->key);
    }

    public function verifyCardToken(string $token): ?array
    {
        [$encoded, $signature] = array_pad(explode('.', trim($token), 2), 2, '');
        if ($encoded === '' || $signature === '') return null;
        $expected = hash_hmac('sha256', $encoded, (string) config('Encryption')->key);
        if (! hash_equals($expected, $signature)) return null;
        $payload = json_decode(self::base64UrlDecode($encoded), true);
        if (! is_array($payload) || empty($payload['npi']) || (int) ($payload['exp'] ?? 0) < time()) return null;
        return $payload;
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/') . str_repeat('=', (4 - strlen($value) % 4) % 4), true);
    }

    public function updatePrivacy(int $playerId, string $privacyLevel): array
    {
        if (! in_array($privacyLevel, ['public', 'club', 'private'], true)) {
            return ['success' => false, 'message' => 'Mức độ riêng tư không hợp lệ.'];
        }
        $profile = $this->profileModel->findByPlayerId($playerId);
        if (! $profile) {
            return ['success' => false, 'message' => 'Chưa có hộ chiếu VĐV.'];
        }
        $this->profileModel->update($profile->id, ['privacy_level' => $privacyLevel]);
        return ['success' => true, 'message' => 'Đã cập nhật quyền riêng tư.'];
    }

    public function verifyPlayer(int $playerId, string $level = 'verified', ?int $verifiedBy = null): array
    {
        if (! in_array($level, ['verified', 'official'], true)) {
            return ['success' => false, 'message' => 'Cấp xác thực không hợp lệ.'];
        }
        $profile = $this->profileModel->findByPlayerId($playerId);
        if (! $profile) {
            return ['success' => false, 'message' => 'Chưa có hộ chiếu VĐV.'];
        }
        $this->profileModel->update($profile->id, [
            'status' => $level,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
        return ['success' => true, 'message' => 'Đã xác thực VĐV.'];
    }

    /**
     * Gắn VĐV vào một club thuộc đúng tenant. Club registry quốc gia sẽ dùng
     * adapter riêng ở phase P6; bảng này giữ quan hệ vận hành hiện tại an toàn.
     */
    public function upsertClubMembership(int $playerId, int $tenantId, int $clubId, array $data = []): array
    {
        if (! $this->profileModel->findByPlayerId($playerId)) {
            $passport = $this->ensurePassport($playerId);
            if (empty($passport['success'])) return $passport;
        }
        if (! $this->clubModel->findForTenant($clubId, $tenantId)) {
            return ['success' => false, 'message' => 'Club không thuộc tenant hiện tại.'];
        }

        $membership = $this->membershipModel
            ->where('tenant_id', $tenantId)
            ->where('club_id', $clubId)
            ->where('player_id', $playerId)
            ->first();
        $payload = [
            'tenant_id' => $tenantId,
            'club_id' => $clubId,
            'player_id' => $playerId,
            'role' => $data['role'] ?? 'member',
            'status' => $data['status'] ?? 'pending',
            'source' => $data['source'] ?? 'manual',
            'is_primary' => ! empty($data['is_primary']) ? 1 : 0,
            'joined_at' => $data['joined_at'] ?? date('Y-m-d H:i:s'),
            'verified_at' => $data['verified_at'] ?? null,
            'verified_by' => $data['verified_by'] ?? null,
            'metadata' => ! empty($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null,
        ];
        $id = $membership
            ? $this->membershipModel->update($membership->id, $payload) && $membership->id
            : $this->membershipModel->insert($payload);

        return $id
            ? ['success' => true, 'membership' => $this->membershipModel->find($id)]
            : ['success' => false, 'message' => 'Không lưu được quan hệ club.'];
    }

    public function potentialDuplicates(int $playerId): array
    {
        $player = $this->playerModel->find($playerId);
        if (! $player) return [];
        return $this->claimModel->findPotentialDuplicates(
            $playerId,
            ! empty($player->phone) ? (string) $player->phone : null,
            ! empty($player->email) ? (string) $player->email : null
        );
    }

    private function syncPrimaryClaims(int $playerId, object $player): void
    {
        if (! empty($player->phone)) {
            $existing = $this->claimModel->where('player_id', $playerId)
                ->where('claim_type', 'phone')
                ->where('claim_value', $player->phone)
                ->first();
            if (! $existing) {
                $this->claimModel->insert([
                    'player_id' => $playerId,
                    'claim_type' => 'phone',
                    'claim_value' => $player->phone,
                    'is_primary' => 1,
                ]);
            }
        }
        if (! empty($player->email)) {
            $existing = $this->claimModel->where('player_id', $playerId)
                ->where('claim_type', 'email')
                ->where('claim_value', $player->email)
                ->first();
            if (! $existing) {
                $this->claimModel->insert([
                    'player_id' => $playerId,
                    'claim_type' => 'email',
                    'claim_value' => $player->email,
                    'is_primary' => 1,
                ]);
            }
        }
    }
}
