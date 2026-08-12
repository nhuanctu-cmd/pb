# Competition ruleset architecture

Competition formats and rulesets are data/configuration, not controller conditionals.

```text
competition
  ├── category
  ├── ruleset_id
  ├── ruleset_version_id  ← immutable event snapshot
  ├── format
  └── draw_version
```

## Ruleset dimensions

- game format: singles/doubles/mixed;
- best-of and games-to;
- win-by and score cap;
- side switch and timeout rules;
- retirement, walkover and disqualification policy;
- result eligibility for rating/ranking.

## Format contract

```php
interface CompetitionFormatInterface
{
    public function generate(array $context): array;
    public function validate(array $context): array;
    public function standings(array $matches): array;
}
```

The same deterministic input seed, policy version and participant snapshot must reproduce the same draw. A locked draw can only be replaced by an explicit new version with actor, reason and audit.
