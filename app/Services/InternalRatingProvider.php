<?php

namespace App\Services;

use App\Contracts\RatingProviderInterface;

class InternalRatingProvider implements RatingProviderInterface
{
    public function code(): string { return 'internal-v1'; }

    public function findPlayer(string $query, string $discipline = 'singles'): array
    {
        return model(\App\Models\PlayerModel::class)
            ->groupStart()->like('full_name', $query)->orLike('player_code', $query)->groupEnd()
            ->where('status', 'active')->where('deleted_at', null)->limit(25)->find();
    }

    public function getPlayerRating(int $tenantId, int $playerId, string $discipline): ?array
    {
        return service('ratingEngine')->getPublicRating($tenantId, $playerId, $discipline);
    }

    public function getRatingHistory(int $tenantId, int $playerId, string $discipline, int $limit = 100): array
    {
        return service('ratingEngine')->history($tenantId, $playerId, $discipline, $limit);
    }

    public function calculateMatchImpact(array $context): array
    {
        return service('ratingCalculator')->calculate($context);
    }

    public function validateMatchEligibility(array $context): array
    {
        return service('ratingEligibilityService')->validate($context);
    }

    public function syncPlayer(int $tenantId, int $playerId, string $discipline): array
    {
        return ['success' => true, 'provider' => $this->code(), 'player_id' => $playerId, 'discipline' => $discipline];
    }

    public function submitMatchIfSupported(array $context): array
    {
        return ['success' => false, 'supported' => false, 'provider' => $this->code(), 'message' => 'Internal provider consumes official results.'];
    }

    public function getReliabilityIfAvailable(int $tenantId, int $playerId, string $discipline): ?float
    {
        $rating = $this->getPlayerRating($tenantId, $playerId, $discipline);
        return isset($rating['reliability']) ? (float) $rating['reliability'] : null;
    }

    public function getExternalPlayerId(int $playerId): ?string { return null; }
}
