<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerFavoriteModel extends Model
{
    protected $table = 'player_favorites';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['tenant_id', 'player_id', 'entity_type', 'entity_id'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    public function findFavorite(int $tenantId, int $playerId, string $type, int $entityId): ?object
    {
        return $this->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('entity_type', $type)->where('entity_id', $entityId)->where('deleted_at', null)->first();
    }

    public function getByPlayer(int $tenantId, int $playerId): array
    {
        return $this->where('tenant_id', $tenantId)->where('player_id', $playerId)->where('deleted_at', null)->orderBy('created_at', 'DESC')->findAll();
    }
}
