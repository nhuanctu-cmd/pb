<?php

namespace App\Models;

use CodeIgniter\Model;

class PlayerRatingModel extends Model
{
    protected $table = 'player_ratings';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'tenant_id', 'player_id', 'scope_type', 'scope_id', 'region',
        'rating_type', 'rating', 'games_played', 'wins', 'losses',
        'rank_position', 'last_match_at',
    ];
    protected $useTimestamps = true;

    public function findOrCreate(int $tenantId, int $playerId, string $scopeType = 'global', ?int $scopeId = null, ?string $region = null)
    {
        $rating = $this->where('tenant_id', $tenantId)
            ->where('player_id', $playerId)
            ->where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('region', $region)
            ->first();

        if ($rating) {
            return $rating;
        }

        $this->insert([
            'tenant_id'   => $tenantId,
            'player_id'   => $playerId,
            'scope_type'  => $scopeType,
            'scope_id'    => $scopeId,
            'region'      => $region,
            'rating_type' => 'elo',
            'rating'      => 1000,
        ]);

        return $this->find($this->insertID());
    }

    public function getRanking(int $tenantId, string $scopeType = 'global', ?int $scopeId = null, ?string $region = null, int $limit = 50): array
    {
        if ($scopeType === 'region') {
            return $this->select('player_ratings.*, players.full_name, players.player_code, players.level, players.region, players.home_branch_id')
                ->join('players', 'players.id = player_ratings.player_id')
                ->where('player_ratings.tenant_id', $tenantId)
                ->where('player_ratings.scope_type', 'global')
                ->where('players.region', $region)
                ->where('players.deleted_at', null)
                ->orderBy('player_ratings.rating', 'DESC')
                ->orderBy('player_ratings.games_played', 'DESC')
                ->limit($limit)
                ->findAll();
        }

        $builder = $this->select('player_ratings.*, players.full_name, players.player_code, players.level, players.region, players.home_branch_id')
            ->join('players', 'players.id = player_ratings.player_id')
            ->where('player_ratings.tenant_id', $tenantId)
            ->where('player_ratings.scope_type', $scopeType)
            ->where('player_ratings.scope_id', $scopeId)
            ->where('player_ratings.region', $region)
            ->where('players.deleted_at', null);

        return $builder->orderBy('player_ratings.rating', 'DESC')
            ->orderBy('player_ratings.games_played', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
