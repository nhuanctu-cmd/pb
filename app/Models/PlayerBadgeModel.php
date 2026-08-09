<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerBadgeModel extends Model
{
    protected $table = 'player_badges';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'player_id', 'badge_code', 'name', 'description',
        'rarity', 'icon', 'source', 'earned_at',
    ];
    protected $useTimestamps = true;

    public function getByPlayer(int $playerId): array
    {
        return $this->where('player_id', $playerId)
            ->orderBy('earned_at', 'DESC')
            ->findAll();
    }

    public function award(int $tenantId, int $playerId, string $code, string $name, array $options = []): bool
    {
        $exists = $this->where('player_id', $playerId)->where('badge_code', $code)->first();
        if ($exists) {
            return true;
        }

        return (bool) $this->insert([
            'tenant_id'   => $tenantId,
            'player_id'   => $playerId,
            'badge_code'  => $code,
            'name'        => $name,
            'description' => $options['description'] ?? null,
            'rarity'      => $options['rarity'] ?? 'common',
            'icon'        => $options['icon'] ?? 'bi-award',
            'source'      => $options['source'] ?? 'system',
            'earned_at'   => $options['earned_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }
}
