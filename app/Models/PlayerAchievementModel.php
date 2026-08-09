<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerAchievementModel extends Model
{
    protected $table = 'player_achievements';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'player_id', 'code', 'name', 'description',
        'points', 'achieved_at',
    ];
    protected $useTimestamps = true;

    public function getByPlayer(int $playerId): array
    {
        return $this->where('player_id', $playerId)
            ->orderBy('achieved_at', 'DESC')
            ->findAll();
    }

    public function award(int $tenantId, int $playerId, string $code, string $name, array $options = []): bool
    {
        $exists = $this->where('player_id', $playerId)->where('code', $code)->first();
        if ($exists) {
            return true;
        }

        return (bool) $this->insert([
            'tenant_id' => $tenantId,
            'player_id' => $playerId,
            'code' => $code,
            'name' => $name,
            'description' => $options['description'] ?? null,
            'points' => $options['points'] ?? 0,
            'achieved_at' => $options['achieved_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }
}
