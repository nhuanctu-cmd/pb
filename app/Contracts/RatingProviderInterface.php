<?php

namespace App\Contracts;

interface RatingProviderInterface
{
    public function code(): string;
    public function findPlayer(string $query, string $discipline = 'singles'): array;
    public function getPlayerRating(int $tenantId, int $playerId, string $discipline): ?array;
    public function getRatingHistory(int $tenantId, int $playerId, string $discipline, int $limit = 100): array;
    public function calculateMatchImpact(array $context): array;
    public function validateMatchEligibility(array $context): array;
    public function syncPlayer(int $tenantId, int $playerId, string $discipline): array;
    public function submitMatchIfSupported(array $context): array;
    public function getReliabilityIfAvailable(int $tenantId, int $playerId, string $discipline): ?float;
    public function getExternalPlayerId(int $playerId): ?string;
}
