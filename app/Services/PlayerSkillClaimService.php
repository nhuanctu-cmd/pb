<?php

namespace App\Services;

use Config\Database;

class PlayerSkillClaimService
{
    protected $db;

    public function __construct() { $this->db = Database::connect(); }

    public function submit(int $tenantId, int $playerId, string $discipline, array $data, ?int $actorId = null): array
    {
        if (! $this->db->tableExists('player_skill_claims')) return ['success' => false, 'message' => 'Rating foundation migration chưa được chạy.'];
        $disciplineRow = $this->db->table('rating_disciplines')->where('code', $discipline)->where('active', 1)->get()->getRow();
        if (! $disciplineRow) return ['success' => false, 'message' => 'Discipline không hợp lệ.'];
        $source = (string) ($data['source_type'] ?? 'self');
        if (! in_array($source, ['self', 'club', 'coach', 'tournament_organizer', 'external_provider'], true)) return ['success' => false, 'message' => 'Nguồn skill không hợp lệ.'];
        $claimedRating = isset($data['claimed_rating']) && $data['claimed_rating'] !== '' ? (float) $data['claimed_rating'] : null;
        if ($claimedRating !== null && ($claimedRating < 2 || $claimedRating > 6)) return ['success' => false, 'message' => 'Declared rating phải trong khoảng 2.000–6.000.'];
        $builder = $this->db->table('player_skill_claims');
        $saved = $builder->insert([
            'tenant_id' => $tenantId, 'player_id' => $playerId, 'discipline_id' => $disciplineRow->id, 'source_type' => $source,
            'source_organization_id' => $data['source_organization_id'] ?? null, 'source_user_id' => $actorId,
            'claimed_rating' => $claimedRating, 'skill_band_id' => $data['skill_band_id'] ?? service('skillBandResolver')->id($claimedRating),
            'external_provider' => $data['external_provider'] ?? null, 'external_reference' => $data['external_reference'] ?? null,
            'verification_status' => $source === 'self' ? 'pending' : ($data['verification_status'] ?? 'pending'),
            'evidence' => ! empty($data['evidence']) ? json_encode($data['evidence'], JSON_UNESCAPED_UNICODE) : null,
            'claimed_at' => date('Y-m-d H:i:s'), 'expires_at' => $data['expires_at'] ?? null, 'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $id = $saved ? $this->db->insertID() : 0;
        return $id ? ['success' => true, 'claim_id' => (int) $id, 'verification_status' => $source === 'self' ? 'pending' : 'pending'] : ['success' => false, 'message' => 'Không lưu được skill claim.'];
    }
}
