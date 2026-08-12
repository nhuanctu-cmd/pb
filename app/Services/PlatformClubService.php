<?php

namespace App\Services;

use App\Models\ClubModel;
use App\Models\PlatformClubAliasModel;
use App\Models\PlatformClubModel;

class PlatformClubService
{
    private PlatformClubModel $clubModel;
    private PlatformClubAliasModel $aliasModel;
    private ClubModel $tenantClubModel;

    public function __construct()
    {
        $this->clubModel = model(PlatformClubModel::class);
        $this->aliasModel = model(PlatformClubAliasModel::class);
        $this->tenantClubModel = model(ClubModel::class);
    }

    public function list(array $filters = []): array
    {
        $builder = $this->clubModel->where('status', 'active')->where('deleted_at', null);
        if (! empty($filters['q'])) $builder->groupStart()->like('name', $filters['q'])->orLike('city', $filters['q'])->orLike('province', $filters['q'])->groupEnd();
        if (! empty($filters['province'])) $builder->where('province', $filters['province']);
        return $builder->orderBy('name', 'ASC')->findAll();
    }

    public function create(array $data, ?int $userId = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') return ['success' => false, 'message' => 'Tên club là bắt buộc.'];
        $slug = generate_slug($data['slug'] ?? $name) ?: 'club-' . bin2hex(random_bytes(4));
        $baseSlug = $slug;
        $suffix = 2;
        while ($this->clubModel->withDeleted()->where('slug', $slug)->first()) $slug = $baseSlug . '-' . $suffix++;
        $id = $this->clubModel->insert([
            'public_id' => $this->uuid(),
            'code' => $data['code'] ?? null,
            'name' => $name,
            'slug' => $slug,
            'province' => $data['province'] ?? null,
            'city' => $data['city'] ?? null,
            'logo_url' => $data['logo_url'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'verification_status' => 'unverified',
            'metadata' => ! empty($data['metadata']) ? json_encode($data['metadata'], JSON_UNESCAPED_UNICODE) : null,
        ]);
        return $id ? ['success' => true, 'club' => $this->clubModel->find($id)] : ['success' => false, 'message' => 'Không tạo được platform club.'];
    }

    public function linkTenantClub(int $platformClubId, int $tenantId, int $clubId, ?int $userId = null): array
    {
        $platform = $this->clubModel->where('id', $platformClubId)->where('deleted_at', null)->first();
        if (! $platform) return ['success' => false, 'message' => 'Platform club không tồn tại.'];
        if (! $this->tenantClubModel->findForTenant($clubId, $tenantId)) return ['success' => false, 'message' => 'Club tenant không hợp lệ.'];
        $existing = $this->aliasModel->where('tenant_id', $tenantId)->where('club_id', $clubId)->first();
        $data = ['platform_club_id' => $platformClubId, 'tenant_id' => $tenantId, 'club_id' => $clubId, 'status' => 'pending', 'linked_by' => $userId];
        $id = $existing ? ($this->aliasModel->update($existing->id, $data) ? $existing->id : 0) : $this->aliasModel->insert($data);
        return $id ? ['success' => true, 'alias' => $this->aliasModel->find($id)] : ['success' => false, 'message' => 'Không liên kết được club.'];
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
