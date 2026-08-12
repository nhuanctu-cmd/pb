# Rating architecture

Rating measures current playing ability; ranking measures competitive position. They are separate ledgers and separate policies.

## Provider boundary

```php
interface RatingProviderInterface
{
    public function code(): string;
    public function getPlayerRating(int $tenantId, int $playerId, string $discipline): ?array;
    public function calculateMatchImpact(array $context): array;
    public function validateMatchEligibility(array $context): array;
    public function syncPlayer(int $tenantId, int $playerId, string $discipline): array;
    public function getExternalPlayerId(int $playerId): ?string;
}
```

Internal rating is never overwritten by an external provider. External records need provider ID, player mapping, rating, reliability, consent, source status and sync timestamps.

## Immutable transaction

Every rating transaction stores provider, discipline, before, after, delta, match ID, result version, policy/algorithm version, reason, actor/source and created time. Corrections append a compensation transaction or mark a rebuild range dirty.

## Reliability and skill

Reliability (0–100) uses volume, recency, verification, opponent/partner diversity. Skill bands are configurable (`NR`, `2.0` … `5.5+`) and must not replace the decimal rating. New players receive a seeded/provisional rating from declared skill, coach assessment, verified matches or permitted external data, with low reliability.

## Integrity

Repeated opponents, abnormal movement, mass self-reporting, result manipulation and sandbagging create review flags only. Policy thresholds are versioned configuration, not magic numbers.
