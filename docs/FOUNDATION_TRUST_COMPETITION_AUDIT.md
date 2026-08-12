# Trust & Competition Foundations — architecture outputs

Date: 2026-08-09

This is the design checkpoint required by the foundation specification. It records the current gaps, ownership boundaries and implementation order before additive code changes.

## 1. Current Governance Gap

Existing: `ranking_authorities`, `tournament_sanctions`, `match_disputes`, audit logs and official result statuses. Missing: generic authority hierarchy, scoped policies, sanction request/review/conditions, generic appeals, result-correction requests and a decision record containing actor, authority, policy, reason and evidence.

## 2. Current Ruleset Gap

Rulesets exist only as documentation and JSON/default logic. There are no immutable `rulesets`/`ruleset_versions` records linked to every event, category, match or result. Eligibility, seeding and draw policies are not consistently versioned or snapshotted.

## 3. Current Provenance Gap

Selected records contain `created_by`, `verified_by`, source or policy fields, but there is no generic `data_provenance_records` lineage. A reviewer cannot yet trace skill claim → match → result version → sanction → rating/ranking policy from one model. Historical origin cannot be guaranteed through merge/correction workflows.

## 4. Current Rating Provider Coupling

`RatingProviderInterface`, `InternalRatingProvider`, `rating_providers`, provider-aware profiles and policies already exist. The interface is still platform-specific and lacks player discovery, history, external consent/link records, provider rating storage, sync state and context-level primary/fallback selection.

## 5. Current Match Duplication Map

| Domain | Current records | Target owner | Gap |
|---|---|---|---|
| Tournament | `tournament_matches` | canonical `matches` | Link exists; migration/backfill and read-path enforcement remain |
| Open Play | sessions/rotations | `matches` + sides + participants + games | Not every rotation projects to the graph |
| Club/League | fixtures and module histories | `matches` | Separate result/standings paths remain |
| Rating | rating transactions | derived from official result versions | Provenance/correction links need strengthening |
| Ranking | point ledgers | derived from placement and official evidence | Authority/policy chain is partial |

## 6. Proposed Governance ERD

`governance_authorities` self-references a parent authority and owns scoped `governance_policies`. Authorities have many `tournament_sanctions`; sanctions have many `sanction_reviews` and `sanction_conditions`. `governance_decisions` references authority, policy, actor and subject. `appeals` have many `appeal_evidence` and decisions. `result_correction_requests` reference original result versions and decisions.

## 7. Proposed Ruleset ERD

`rulesets` → many immutable `ruleset_versions`. A version owns category, eligibility, seeding and draw policy versions. Tournaments, categories, tournament matches and result versions keep the selected snapshot IDs. Every version has effective dates, actor, status and content hash.

## 8. Provenance Data Flow

`source/import/user/authority → data_provenance_records → skill claim/match/result version → rating transaction/ranking ledger → snapshot/public response`. Every record stores entity, source, organization, creator, verifier, verification level, import batch, external reference, policy version and timestamps. Corrections append; they never erase the chain.

## 9. Unified Match Graph ERD

`players → match_participants → match_sides → matches → match_games` and `matches → match_results → match_result_versions`. Matches retain source event/organization/venue references and have many integrity flags. Side-based participants support singles, doubles and future team formats.

## 10. Rating Provider Architecture

`RatingProviderRegistry` resolves internal, external API, manual external, federation and partner adapters. A context policy chooses primary provider, then an explicit fallback chain. Player consent creates `player_rating_provider_links`; sync runs through queue/cache and stores ACTIVE/STALE/ERROR/REVOKED state. Provider values are never averaged automatically.

## 11. Result Correction Flow

`OPEN → REVIEWING → APPROVED/REJECTED`. Approval appends a result version, reverses old rating impact, recalculates affected ranking and records the governance outcome. Official versions and derived ledger rows are never updated destructively.

## 12. Appeal Flow

`OPEN → EVIDENCE_COLLECTION → REVIEW → DECISION → APPEALED_AGAIN (policy permitting) → FINAL`. Subjects: result, identity, eligibility, rating, ranking points, sanction and disciplinary action. Every transition creates a decision and audit event.

## 13. Sanction Flow

`DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED/REJECTED → SANCTIONED`. Approval generates a public sanction ID, freezes ruleset/policy snapshots and verifies conditions before official ranking eligibility.

## 14. Tournament → Rating Flow

`sanctioned event → ruleset/eligibility snapshot → canonical matches → official result version → provenance → provider eligibility → rating ledger`. Only official eligible results enter rating; corrections create reversal/replacement transactions.

## 15. Tournament → Ranking Flow

`sanction + tier + authority → verified placement → ranking policy version → ranking point ledger → ranking snapshot`. Tournament owns placement evidence, match graph owns match evidence, ranking ledger owns points.

## 16. Source-of-Truth Matrix

| Concept | Single owner |
|---|---|
| Player identity | Player Registry/Passport |
| Match | Unified Match Graph (`matches`) |
| Participants | `match_sides` + `match_participants` |
| Scores | `match_games` |
| Official result | `match_result_versions` |
| Correction | `result_correction_requests` |
| Skill claim | `player_skill_claims` |
| Internal rating | rating profile + `rating_transactions` |
| External rating | provider rating store |
| Provider choice | context rating policy |
| Ranking points | `ranking_point_ledgers` |
| Current ranking | `ranking_snapshots` |
| Tournament rules | immutable ruleset version |
| Official status | Governance Engine/authority decision |
| Data origin | `data_provenance_records` |
| Appeals | Appeal Engine |

Controllers do not calculate or overwrite derived rating/ranking values.

## 17. Migration Plan

G1 unified match graph constraints and canonical-source metadata; G2 provenance records and ledger links; G3 ruleset/policy versions and event snapshots; G4 authority/sanction/decision/appeal/correction workflows; G5 provider links/consent/external values/sync; G6 tournament/open-play/club adapters; G7 rating/ranking preconditions; G8 external adapters; G9 integrity graph and reviewer dashboards.

## 18. Tests

Authority hierarchy and scope; sanction lifecycle/conditions/public ID; ruleset immutability/effective dates; snapshot preservation; append-only provenance; provider consent/link/fallback/error states; singles/doubles validation; duplicate flag without silent merge; official certification preconditions; correction reversal; appeal evidence/transitions; tournament-to-rating-to-ranking traceability; tenant isolation; no auto-convict/auto-ban.

## 19. Risks

Installations may have migrations out of order. MySQL enums require controlled migrations. Legacy tournament/competition reads may bypass the graph. Historical data may lack provenance and must remain `UNKNOWN`, never receive fabricated verification. External provider outages must not block booking or core tournament operations.

## 20. Implementation Phases

The first code delivery is additive G1–G5: foundation tables, models, contracts, services and tests for critical transitions. Existing flows remain operational until explicit adapters migrate them. G6–G9 are separate cutovers with data audits between phases.
