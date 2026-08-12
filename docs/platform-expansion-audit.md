# Platform Expansion Audit

## Scope

Audit baseline for expanding the current court-booking product into a multi-tenant Booking SaaS, Tournament SaaS and national pickleball network. This document deliberately maps existing capabilities before adding new domains so that existing booking, tournament, rating and player modules remain the source of truth until a controlled migration is completed.

## Current architecture

- Runtime: CodeIgniter 4.7.3, PHP 8.2, MySQL, server-rendered admin/player pages and JSON API under `/api/v1`.
- Tenant boundary: most operational tables carry `tenant_id`; API authentication exposes `api_tenant_id`; admin uses tenant session/permissions.
- Existing operational domains: tenants, branches, courts, bookings, payments, memberships, notifications/jobs, waitlist, recurring booking, walk-in/open-play, coaching, community and social matching.
- Existing competition domains: tournaments, categories, registrations, teams, scheduling/brackets, live scores, competition formats, entry fees, sanctions/check-ins/disputes.
- Existing identity/rating domains: `players`, `player_competitive_profiles`, `player_identity_claims`, `player_merge_requests`, `player_merge_audits`, player ratings/statistics/history, `PlayerPassportService`.
- Existing integration foundation: webhook integrations, job model, audit logging and API response/auth services.

## Capability matrix

| Domain | Status | Existing source of truth | Decision |
|---|---|---|---|
| Identity and auth | EXIST | users, auth/security migration, API auth | Extend with platform roles and MFA later |
| National player registry | PARTIAL | player passport/profile/claims | Harden ID generation, add membership and merge workflow |
| Organization/tenant | PARTIAL | tenants, branches, clubs | Keep tenant isolation; add platform club registry without breaking tenant clubs |
| Venue/court | EXIST | facilities, branches, courts, availability | Reuse for booking and tournament scheduling |
| Booking/availability | EXIST | bookings, booking items, waitlist, recurring templates | Reuse; expose week availability API |
| Payment/commerce | EXIST | invoices, payments, wallets, entry fees | Reuse; add idempotency/ledger reconciliation |
| Membership/loyalty | EXIST | memberships, wallets, growth module | Reuse and normalize entitlements later |
| Tournament SaaS | PARTIAL | tournaments, categories, registrations, scheduler, scores | Connect to unified match domain |
| Unified match | MISSING | tournament_matches and social matches are separate | Add platform match read/write model |
| Rating | PARTIAL | player_ratings, player_rating_history, external ratings | Add provider-aware rating ledger after official match events |
| National ranking | PARTIAL | player statistics/ranking summaries | Add policy, point ledger and snapshots |
| Notifications/jobs | EXIST | notifications, jobs | Use for match/result/ranking events |
| Admin/RBAC | EXIST | auth filters, permissions, tenant context | Add platform governance permissions |
| Public API | PARTIAL | `/api/v1` | Add stable registry and official match endpoints |
| Queue/event bus | PARTIAL | jobs and services | Introduce domain event/outbox before high-volume recalculation |
| Redis/cache | NEEDS_REVIEW | no single authoritative abstraction found in audit | Add only behind cache service |
| Search | MISSING | database queries only | Start with indexed lookup; add search engine after scale evidence |
| File storage | PARTIAL | avatar/media paths | Normalize storage adapter and signed URLs |

## Do not duplicate

The following are existing equivalents and should be extended/refactored instead of recreated:

- `PlayerPassportService`, `PlayerCompetitiveProfileModel`, `PlayerIdentityClaimModel` for player registry.
- `Tournament*Model`, `TournamentSchedulerService`, `ScoreService` and live-score tables for tournament execution.
- `PlayerRatingService`, player statistics/history and `player_external_ratings` for rating inputs.
- `TenantPlanService`, subscription/plan tables, API auth and permission filters for SaaS tenancy.
- `JobModel`, notification services, webhook integration models/services and audit log for asynchronous delivery and traceability.

## P1–P2 implementation delivered by this expansion

1. Keep the existing passport tables as the identity source of truth.
2. Replace sequential National Player ID generation with collision-safe random public IDs while retaining old IDs for compatibility.
3. Add `player_club_memberships` with tenant scoping, roles, verification, dates and privacy metadata.
4. Add an immutable unified `matches` model, participants, games, current result and result versions.
5. Add result state transitions: draft → submitted → confirmed → official, with correction versions rather than destructive updates.
6. Add authenticated API endpoints for creating, submitting, confirming and reading matches.

## Platform versus tenant data

| Platform/network data | Tenant/club operational data |
|---|---|
| national player ID, verified identity claims | bookings, prices, payments |
| official match/result versions | court schedules and check-ins |
| rating/ranking ledgers and snapshots | local memberships and wallet |
| public player profile/privacy policy | tournament operations before publication |
| governance, sanctions, appeals | staff permissions and tenant reports |

Every write must validate tenant ownership where a tenant is present. A platform-global official result may be read across tenants only after its publication state is `official`.

## Dependency map

`player registry → unified match → verified/official result → rating ledger → ranking ledger → public profile/API`

Tournament matches should initially publish an adapter event into the unified match service. A later migration can replace `tournament_matches` as the write source after parity tests and backfill verification.

## P3–P8 backlog and delivery status

- P3 Rating network: **core delivered** with `rating_providers`, `rating_ledgers`, `rating_rebuild_jobs`, `RatingNetworkService` and official-result consumer. External-provider adapters, fraud scoring and full rebuild worker remain next.
- P4 Ranking: **core delivered** with `ranking_authorities`, `ranking_policies`, `ranking_point_ledgers`, `ranking_snapshots` and `RankingNetworkService`. Appeals, policy admin and scheduled snapshots remain next.
- P5 Tournament SaaS: **match integration delivered** with `TournamentMatchNetworkAdapter`, sync/official-result APIs and `tournament_matches.unified_match_id`; registration/eligibility, seeding, draw and entry-fee reconciliation continue next.
- P6 National network: **club registry core delivered** with `platform_clubs`, platform-club aliases and public API; public calendar, player-club discovery and SEO pages continue next.
- P7 Governance: sanctioning, moderation, disputes, appeals, referee roles, qualification wallet and audit trail.
- P8 Integrations: **delivered baseline + advanced partner access** — outbound webhooks with retry/dead-letter, tenant-scoped partner API keys with scopes, privacy-safe public player/ranking/club/tournament endpoints, integration registry, health check, admin key lifecycle and signed national player QR card.

## Suggested implementation order

```text
P1 Registry/Privacy
  └─ P2 Match/Official Result
      ├─ P3 Rating Ledger
      │   └─ P4 Ranking Ledger/Snapshot
      ├─ P5 Tournament Adapter + SaaS workflow
      └─ P6 Public Network/API
          └─ P7 Governance + P8 Integrations
```

For every phase, add migration → model → service → API/admin surface → job/event consumer → feature tests. Avoid writing rating or ranking directly from controllers; consume only an official-result event so corrections can be replayed.

## Acceptance gates

- Existing booking/tournament pages continue to load with tenant isolation.
- New migrations are idempotent and do not rename/drop existing tables.
- Official result versions are append-only and auditable.
- Rating/ranking jobs consume only official results and are replayable.
- Public profile fields honor privacy level and never expose identity claims.

## P8 advanced acceptance evidence

- Migration `2026-08-09-371000_CreatePartnerIntegrations` creates `platform_integrations`, `tenant_integrations` and hashed `partner_api_keys` without duplicating webhook tables.
- Demo seed creates three integrations and one scoped partner key for every active tenant; the key secret is never stored in plaintext.
- Partner API is versioned at `/api/partner/v1`, requires `X-Partner-Key`, applies scope checks and returns only privacy-safe network data.
- Webhook event catalog now covers `match.official`, `rating.updated`, `ranking.updated`, `player.updated` and `tournament.completed`; delivery remains asynchronous through the existing queue worker.
- National player card uses a signed expiring token and exposes only public profile fields through `/api/public/v1/players/{id}/card` and `/players/card/verify`.
