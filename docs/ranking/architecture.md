# Ranking architecture

Ranking points are event/competition outcomes. No leaderboard may infer ranking directly from a current rating.

## Dimensions

```text
authority → policy version → country/scope → season → discipline/category
```

Supported target scopes: world/future, continent, country, region, province/state and club. Country uses ISO code; Vietnam is deployment data, not a hard-coded ranking assumption.

## Point ledger

Each transaction stores player, event, placement/reason, points, multiplier, policy version, effective time, expiry, provenance and idempotency key. Expired points are compensating/expiration transactions, never silent deletes.

## Snapshots

Daily/weekly snapshots are the read model for rank history, rank change and top movers. Rebuild supports season, discipline, player and dry-run/checksum. Closed seasons are frozen except for a privileged recalculation process that creates a new snapshot version.
