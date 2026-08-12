# 🏓 NATIONAL PICKLEBALL OPERATING SYSTEM — MASTER ARCHITECTURE

> **Product:** Pickleball National Digital Infrastructure
> **From:** Pickleball Booking Platform → National Pickleball Operating System
> **Stack:** CodeIgniter 4.7 + PHP 8.2 + MySQL 8.0 + Redis
> **Status:** Architecture Analysis Complete — No Competition code until this document is approved
> **Version:** 3.0.0
> **Last Updated:** 2026-08-09

---

## Executive Summary

This document re-audits the platform architecture after the decision to expand from a **Court Booking & SaaS Club** system into a **National Pickleball Operating System** with six layers:

1. Court Booking
2. Club Management
3. Tournament Management
4. Player Rating
5. National Ranking
6. Pickleball SaaS Network

The most important design principle is:

> **RATING ≠ RANKING**
>
> Rating reflects current skill. Ranking reflects tournament achievement. They are stored, computed, and versioned independently.

This document produces the 21 required architecture outputs and the database migration plan. **No Competition module code is started until this architecture is finalized.**

---

## Table of Contents

1. [Platform Domain Map](#1-platform-domain-map)
2. [Tenant Model](#2-tenant-model)
3. [Player Identity Model](#3-player-identity-model)
4. [Booking Domain](#4-booking-domain)
5. [Competition Domain](#5-competition-domain)
6. [Tournament Domain](#6-tournament-domain)
7. [Rating Architecture](#7-rating-architecture)
8. [Ranking Architecture](#8-ranking-architecture)
9. [Rating vs Ranking Data Flow](#9-rating-vs-ranking-data-flow)
10. [Tournament → Result → Rating Flow](#10-tournament--result--rating-flow)
11. [Tournament → Result → Ranking Flow](#11-tournament--result--ranking-flow)
12. [SaaS Entitlement Model](#12-saas-entitlement-model)
13. [Data Ownership Model](#13-data-ownership-model)
14. [Public vs Private Data Model](#14-public-vs-private-data-model)
15. [National Player ID Strategy](#15-national-player-id-strategy)
16. [Database ERD](#16-database-erd)
17. [API Architecture](#17-api-architecture)
18. [Queue / Job Architecture](#18-queue--job-architecture)
19. [Security Architecture](#19-security-architecture)
20. [Scaling Strategy](#20-scaling-strategy)
21. [Implementation Roadmap](#21-implementation-roadmap)
22. [Database Migration Plan](#22-database-migration-plan)

---

## 1. PLATFORM DOMAIN MAP

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         NATIONAL PICKLEBALL OS                              │
│                     (Platform Network + SaaS Tenants)                       │
├─────────────────────────────────────────────────────────────────────────────┤
│  PLAYER NETWORK LAYER                                                        │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ Player       │ │ Player       │ │ Passport     │ │ Achievements     │  │
│  │ Identity     │ │ Rating       │ │ Profile      │ │ Badges           │  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
├─────────────────────────────────────────────────────────────────────────────┤
│  COMPETITION LAYER                                                           │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ Tournament   │ │ Rating       │ │ Ranking      │ │ Sanctioning      │  │
│  │ Management   │ │ Engine       │ │ Engine       │ │ & Qualification  │  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ Seeding      │ │ Draw Engine  │ │ Scheduling   │ │ Live Score       │  │
│  │ Engine       │ │              │ │ Engine       │ │ & Result         │  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
├─────────────────────────────────────────────────────────────────────────────┤
│  BOOKING & CLUB LAYER                                                        │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ Court        │ │ Booking      │ │ Membership   │ │ Open Play /      │  │
│  │ Management   │ │ Engine       │ │ Management   │ │ Matchmaking      │  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐                        │
│  │ POS & Kho    │ │ Coach/Clinic │ │ Wallet &     │                        │
│  │              │ │              │ │ Payment      │                        │
│  └──────────────┘ └──────────────┘ └──────────────┘                        │
├─────────────────────────────────────────────────────────────────────────────┤
│  SAAS PLATFORM LAYER                                                         │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ Tenant       │ │ Plan /       │ │ RBAC &       │ │ Notification     │  │
│  │ Management   │ │ Subscription │ │ Permissions  │ │ Engine           │  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ Audit Log    │ │ Media        │ │ Settings     │ │ Job Queue        │  │
│  │              │ │ Storage      │ │              │ │                  │  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
├─────────────────────────────────────────────────────────────────────────────┤
│  RANKING AUTHORITY LAYER                                                     │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ National     │ │ Regional     │ │ Provincial   │ │ Club             │  │
│  │ Ranking      │ │ Ranking      │ │ Ranking      │ │ Ranking          │  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
├─────────────────────────────────────────────────────────────────────────────┤
│  EXTERNAL INTEGRATION LAYER                                                  │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │ External     │ │ Tournament   │ │ Livestream   │ │ Public API       │  │
│  │ Rating       │ │ Result       │ │ Adapters     │ │ Partners         │  │
│  │ Connectors   │ │ Import       │ │              │ │                  │  │
│  └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Domain Dependency Rules

- **Core** (Tenant, Auth, RBAC) has no upstream dependencies.
- **Booking / Club** depends on Core + Facility.
- **Competition** depends on Booking (court/time), Player, and Payment.
- **Rating** depends only on Official Match data.
- **Ranking** depends on Tournament results and Ranking Policy.
- **Player Network** reads from Rating + Ranking but owns Identity + Privacy.
- **Ranking Authority** owns policy + ledger; does not directly mutate Rating.

---

## 2. TENANT MODEL

### 2.1 Tenant Types

| Tenant Type | Code | Purpose |
|-------------|------|---------|
| Club | `club` | Manages courts, bookings, members |
| Venue Owner | `venue` | Single or multi-court facility operator |
| Tournament Organizer | `tournament_org` | Runs events, applies for sanctioning |
| Association / Federation | `association` | Province, region, or national ranking authority |

### 2.2 Tenant Isolation (Existing)

- Shared MySQL database.
- Every business table has `tenant_id`.
- `TenantFilter` injects tenant scope automatically.
- SaaS plan via `PlanFilter` gates features (`tournament`, `pos`, `ai_scheduling`, etc.).

### 2.3 Tenant vs Ranking Authority

A tenant can be **both** a Club and a Tournament Organizer. A Federation tenant is special:

- It can define `ranking_authorities`.
- It owns `ranking_policies`, `ranking_seasons`, and `tournament_tiers`.
- It can sanction tournaments created by other tenants.

### 2.4 Multi-tenancy Additions Needed

```php
// organizations table (rename/extend from existing tenants if needed)
organizations {
    id, code, name, organization_type, // club/venue/organizer/association
    status, domain, logo, province_id, region_id,
    verified_status, data_quality_score,
    plan_id, subscription_status,
    created_at, updated_at, deleted_at
}

// Organization can act in multiple roles
organization_roles {
    id, organization_id, role, // club, tournament_organizer, association
    is_primary, verified_at, ranking_authority_id
}
```

---

## 3. PLAYER IDENTITY MODEL

### 3.1 Core Principle

Every competitive player has exactly **one primary competitive profile** linked to a **National Player ID**.

Examples:
- Internal: `VN-PKL-0000123456`
- UUID: `550e8400-e29b-41d4-a716-446655440000`

### 3.2 Player Status

```
UNVERIFIED → VERIFIED → OFFICIAL
    ↓           ↓
SUSPENDED  SUSPENDED
```

- **UNVERIFIED**: Can book courts, join open play.
- **VERIFIED**: Phone/email confirmed, can register low-tier tournaments.
- **OFFICIAL**: Identity verified for national ranking eligibility.
- **SUSPENDED**: Fraud or disciplinary action.

### 3.3 Identity Claims & Duplicate Detection

```php
player_identity_claims {
    id, player_id, claim_type, // phone, email, facebook_id, google_id, passport
    claim_value, verified_at, verification_source,
    is_primary, created_at, updated_at
}

player_merge_requests {
    id, source_player_id, target_player_id,
    status, // pending, approved, rejected
    reason, requested_by, reviewed_by, reviewed_at,
    created_at, updated_at
}

player_merge_audits {
    id, merge_request_id, action, // merge_data, undo
    source_snapshot, target_snapshot,
    actor_id, created_at
}
```

Rules:
- Do **not** auto-merge by name.
- Recommend matches based on: National Player ID, external ID, verified phone/email, organizer mapping.
- Merge requires moderator review and full audit.

### 3.4 Player Passport

The **Player Passport** is the public competitive identity:

```php
player_competitive_profiles {
    id, player_id, national_player_id,
    display_name, slug, avatar_url,
    province_id, city_id, club_id,
    gender_category, // men's / women's / open per tournament rules
    age_category_public, // nullable, privacy-controlled
    internal_rating_summary, // cached derived value only
    external_rating_summary, // e.g. DUPR display
    national_rank_summary, // cached derived
    reliability_score,
    privacy_level, // public, club, private
    status, // unverified, verified, official, suspended
    verified_at, created_at, updated_at
}
```

Passport displays:
- National Player ID
- Name, province, club
- Internal Rating (Singles / Doubles / Mixed / Overall)
- External Rating (DUPR, etc.)
- National / Regional / Province / Club Ranking
- Tournament history, match history, medals, titles
- Win/Loss, recent form, achievements

---

## 4. BOOKING DOMAIN

### 4.1 Current State (from existing migrations)

Existing tables:
- `tenants`, `branches`, `courts`, `court_types`
- `bookings`, `booking_items`, `booking_item_players`
- `booking_qr_codes`, `booking_logs`, `booking_payments`
- `booking_waitlist`, `booking_recurring_templates`
- `price_tiers`, `dynamic_pricing`

### 4.2 Booking Domain Responsibilities

| Concern | Owner |
|---------|-------|
| Court availability | Booking Engine + Facility |
| Hold & expire slots | Booking Engine |
| Payment & wallet | Payment Domain |
| QR check-in | Booking Engine |
| Recurring bookings | RecurringBookingService |
| Waitlist | BookingWaitlistService |
| Court review | Growth/Review module |

### 4.3 Boundary with Competition

Tournament matches **may reuse** court booking slots, but a match result is **not** a booking. Link via `tournament_match_booking_links` if needed.

```php
tournament_match_booking_links {
    id, tournament_match_id, booking_id,
    link_type, // primary_court, warmup_court
    created_at
}
```

---

## 5. COMPETITION DOMAIN

### 5.1 Scope

The Competition Domain covers **all structured play beyond casual booking**:

- Tournaments
- Leagues / Ladders (existing `competition_events`)
- Club championships
- Sanctioned ranking events
- Open play sessions with official results

### 5.2 Competition Sub-domains

```
Competition/
├── Tournament/        # Event lifecycle, categories, registration
├── Match/             # Match creation, scoring, validation, disputes
├── Seeding/           # Seed score calculation, seed protection
├── Draw/              # Bracket generation, draw audit
├── Scheduling/        # Court + time assignment, rest constraints
├── Rating/            # Rating calculation, history, reliability
├── Ranking/           # Points, ledger, snapshots, leaderboards
├── Qualification/     # Eligibility rules, qualification slots
├── Sanctioning/       # Ranking authority approval workflow
└── Leaderboard/       # Public display, filters, trends
```

### 5.3 Existing Competition Entities

Existing tables that remain but may need extension:
- `tournaments`, `tournament_categories`, `tournament_registrations`
- `tournament_matches`, `tournament_match_scores`, `tournament_score_logs`
- `tournament_brackets`, `tournament_groups`, `tournament_group_teams`
- `competition_events`, `competition_participants`, `competition_fixtures`, `competition_standings`

New official-match abstraction will unify tournament matches, league fixtures, and ladder results.

---

## 6. TOURNAMENT DOMAIN

### 6.1 Tournament Lifecycle

```
CREATE EVENT
    ↓
CREATE CATEGORIES (with eligibility rules)
    ↓
REGISTRATION + ELIGIBILITY CHECK + PAYMENT
    ↓
CHECK-IN
    ↓
SEEDING
    ↓
DRAW (versioned, auditable)
    ↓
COURT ASSIGNMENT + MATCH SCHEDULING
    ↓
LIVE SCORING
    ↓
RESULT VALIDATION (PENDING → OFFICIAL)
    ↓
RATING UPDATE + RANKING POINTS
    ↓
AWARDS
```

### 6.2 Tournament Verification Levels

| Level | Code | Rating Weight | Ranking Eligible |
|-------|------|---------------|------------------|
| Community | `community` | None | No |
| Club | `club` | Low | No |
| Verified | `verified` | Medium | Conditional |
| Official | `official` | High | Yes |
| National | `national` | Highest | Yes |

### 6.3 Tournament Sanctioning

```php
tournament_sanctions {
    id, tournament_id, ranking_authority_id,
    sanction_id, // VNPKL-2027-HCM-00128
    status, // pending, approved, rejected
    tier_id, point_multiplier,
    approved_by, approved_at, expires_at,
    created_at, updated_at
}
```

Only sanctioned events can feed Ranking. Rating can be calculated for lower verification levels with reduced weight.

### 6.4 Tournament Entities (New / Extended)

```php
tournament_tiers {
    id, ranking_authority_id, code, name,
    point_multiplier, default_rating_weight,
    sort_order, is_active
}

tournament_categories {
    // extend existing
    gender_category, // men's, women's, mixed, open
    age_category, // open, u18, 35+, 40+, etc.
    rating_min, rating_max,
    ranking_min, ranking_max,
    discipline, // singles, doubles, mixed
    team_size, entry_capacity, waitlist_capacity,
    eligibility_rules: JSON
}

tournament_registrations {
    // extend existing
    partner_player_id, // for doubles
    status, // draft, pending, confirmed, waitlisted, cancelled
    eligibility_status, // pending, passed, flagged
    payment_status,
    checked_in_at, no_show,
    created_at, updated_at
}

tournament_checkins {
    id, tournament_id, category_id, registration_id,
    player_id, qr_code, status, // checked_in, no_show, override
    checked_in_by, checked_in_at,
    created_at
}

tournament_disputes {
    id, tournament_id, match_id, raised_by,
    reason, status, // open, reviewing, upheld, corrected
    resolution, resolved_by, resolved_at,
    created_at
}
```

### 6.5 Draw Engine

```php
tournament_draw_versions {
    id, tournament_id, category_id,
    version_no, reason, generated_by, generated_at,
    draw_config: JSON, // seed weights, social separation rules
    is_locked, locked_at,
    created_at
}

tournament_seeding {
    id, tournament_id, category_id, draw_version_id,
    participant_id, seed_no, seed_score,
    source, // national_rank, rating, recent, wildcard
    created_at
}
```

Draw formats:
- Round Robin
- Single Elimination
- Double Elimination
- Group + Knockout
- Swiss System (future)
- League Format

### 6.6 Scheduling Engine

```php
tournament_schedules {
    id, tournament_id, match_id, court_id,
    scheduled_date, start_time, end_time,
    estimated_duration_minutes,
    broadcast_court, championship_court,
    created_by, created_at, updated_at
}
```

Constraints:
- Player rest time (configurable minimum_rest_minutes)
- Multi-category conflict (same player cannot play two matches simultaneously)
- Court availability + operating hours
- Broadcast/championship court preference

---

## 7. RATING ARCHITECTURE

### 7.1 Rating ≠ Ranking

| | Rating | Ranking |
|---|---|---|
| Meaning | Current skill | Tournament achievement |
| Examples | 3.74, 4.21 | #7 Vietnam Men's Singles |
| Inputs | Match outcomes | Tournament points |
| Used for | Matchmaking, divisions | Seeding, qualification |
| Changes | After every eligible match | After official tournament |

### 7.2 Rating Providers

```php
interface RatingProviderInterface {
    public function calculateMatchImpact(array $input): RatingImpact;
    public function calculateNewRating(PlayerRating $rating, RatingImpact $impact): PlayerRating;
    public function calculateReliability(PlayerRating $rating): ReliabilityScore;
}

class InternalRatingProvider implements RatingProviderInterface { }
class DuprRatingProvider implements RatingProviderInterface { }     // future, authorized
class FutureInternationalProvider implements RatingProviderInterface { }
```

### 7.3 External Ratings

```php
player_external_ratings {
    id, player_id,
    provider, // DUPR, IPTPA, etc.
    external_player_id,
    rating, reliability, match_count,
    last_synced_at, sync_payload: JSON,
    created_at, updated_at
}
```

External rating is **display only** unless integrated via official API and user consent.

### 7.4 Player Ratings by Discipline

```php
player_ratings {
    id, player_id,
    discipline, // singles, doubles, mixed, overall (derived)
    rating_value, // DECIMAL(4,3) e.g. 4.125
    confidence, // low, medium, high
    reliability_score, // 0-100
    match_count,
    verified_match_count,
    last_match_at,
    policy_version, // rating_policy version
    created_at, updated_at
}
```

Overall rating is **derived** from discipline ratings, never used to override them.

### 7.5 Rating Policies

```php
rating_policies {
    id, code, name,
    provider_class, // InternalRatingProvider
    config: JSON, // K-factor, weight per verification level, decay
    effective_from, effective_to,
    created_at, updated_at
}
```

### 7.6 Rating Engine Interface

```php
interface RatingEngineInterface {
    public function calculateMatchImpact(
        PlayerRating $playerA,
        PlayerRating $playerB,
        MatchResult $result,
        string $matchType,
        string $verificationLevel,
        float $competitionLevel,
        string $score,
        float $confidence
    ): RatingImpact;

    public function calculateNewRating(PlayerRating $rating, RatingImpact $impact): PlayerRating;
    public function calculateReliability(PlayerRating $rating): ReliabilityScore;
    public function getPlayerRating(int $playerId, string $discipline): PlayerRating;
    public function getRatingHistory(int $playerId, string $discipline): array;
}
```

No formula hard-coded in controllers.

### 7.7 Match Verification Weight

```php
MatchVerificationWeight::SELF_REPORTED  = 0.25;
MatchVerificationWeight::CLUB_VERIFIED  = 0.50;
MatchVerificationWeight::LEAGUE_VERIFIED = 0.75;
MatchVerificationWeight::TOURNAMENT_VERIFIED = 1.00;
MatchVerificationWeight::FEDERATION_VERIFIED = 1.25;
```

Configurable per `rating_policy`.

### 7.8 Anti-Manipulation / Fraud Detection

```php
fraud_detection_rules {
    id, code, name,
    rule_type, // repeated_opponents, abnormal_movement, mass_self_report, etc.
    config: JSON, thresholds
    severity, // normal, review, suspicious, invalid
    is_active
}
```

Detected cases create `fraud_flags`:

```php
fraud_flags {
    id, player_id, rule_code,
    severity, status, // open, reviewing, cleared, confirmed
    evidence: JSON, // matches, rating movement, etc.
    reviewed_by, reviewed_at, resolution,
    created_at
}
```

System flags for moderation. Does **not** auto-lock players.

### 7.9 Rating History

```php
player_rating_histories {
    id, player_id, discipline,
    match_id, official_match_id,
    rating_before, rating_after,
    expected_result, actual_result,
    impact, confidence,
    policy_version, calculation_date,
    created_at
}
```

Immutable. Never overwrite.

---

## 8. RANKING ARCHITECTURE

### 8.1 Ranking Principles

- Ranking is **not** computed from Rating.
- Ranking is based on **Ranking Points** from official tournaments.
- Ranking has independent leaderboards: National, Regional, Province, City, Club.
- Ranking is seasonal and rolling.

### 8.2 Ranking Authorities

```php
ranking_authorities {
    id, code, name, organization_id,
    scope, // national, regional, provincial, club
    region_id, province_id, city_id,
    status, created_at, updated_at
}
```

### 8.3 Ranking Disciplines

```php
ranking_disciplines {
    id, code, name,
    team_size, gender_category, // men's, women's, mixed, open
    sort_order, is_active
}
```

Examples:
- `mens_singles`
- `womens_singles`
- `mens_doubles`
- `womens_doubles`
- `mixed_doubles`

Not hard-coded anywhere; referenced by code.

### 8.4 Ranking Categories (Age)

```php
ranking_categories {
    id, ranking_authority_id, code, name,
    min_age, max_age, // nullable
    is_open, sort_order, is_active
}
```

Examples: OPEN, U18, 35+, 40+, 45+, 50+, 55+, 60+, 65+.

### 8.5 Ranking Seasons

```php
ranking_seasons {
    id, ranking_authority_id,
    code, name, // Vietnam Pickleball Ranking 2027
    start_date, end_date,
    ranking_policy_id,
    point_expiration_policy, // rolling_52_weeks, season_end, none
    is_active, created_at, updated_at
}
```

### 8.6 Ranking Policies & Versions

```php
ranking_policies {
    id, ranking_authority_id, code, name,
    description, is_active, created_at
}

ranking_policy_versions {
    id, ranking_policy_id, version_no,
    effective_from, effective_to,
    config: JSON, // tier multipliers, age rules, discipline rules
    created_at
}
```

Example:
- VN Ranking Rules V1 — effective 2027-01-01
- VN Ranking Rules V2 — effective 2028-01-01

Historical calculations remain tied to the version used at calculation time.

### 8.7 Point Tables

```php
ranking_point_tables {
    id, ranking_policy_version_id, tournament_tier_id,
    placement, // champion, runner_up, semi_final, etc.
    points, label,
    created_at
}
```

Example (National Major):

| Placement | Points |
|---|---|
| Champion | 2,000 |
| Runner-up | 1,400 |
| Semi-final | 900 |
| Quarter-final | 500 |
| Round 16 | 200 |
| Participation | 50 |

### 8.8 Ranking Point Ledger

```php
ranking_point_transactions {
    id, player_id,
    discipline_id, category_id,
    tournament_id, event_id, official_match_id,
    reason, // championship, regional_open, expired, penalty, adjustment
    points, // positive or negative
    effective_at, expires_at,
    policy_version_id,
    created_by, // system or admin
    created_at
}
```

Current points = sum of non-expired ledger entries per player/discipline/category/season.

### 8.9 Ranking Snapshots

```php
ranking_snapshots {
    id, ranking_authority_id, season_id,
    discipline_id, category_id,
    snapshot_date, period_type, // weekly, monthly, season
    created_at
}

ranking_snapshot_entries {
    id, ranking_snapshot_id,
    player_id, rank, previous_rank,
    points, rating, events_played,
    highest_rank, lowest_rank,
    trend, // up, down, same, new
    created_at
}
```

Used for:
- Public leaderboards
- Trend arrows
- Historical charts

### 8.10 Ranking History

```php
player_rank_histories {
    id, player_id,
    ranking_authority_id, discipline_id, category_id,
    rank, points, snapshot_date,
    created_at
}
```

---

## 9. RATING VS RANKING DATA FLOW

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      RATING vs RANKING SEPARATION                        │
└─────────────────────────────────────────────────────────────────────────┘

                            OFFICIAL MATCH
                                 │
            ┌────────────────────┼────────────────────┐
            │                    │                    │
            ▼                    ▼                    ▼
   ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
   │ Match Result    │  │ Match Result    │  │ Tournament      │
   │ (Outcome)       │  │ (Score)         │  │ Placement       │
   └────────┬────────┘  └────────┬────────┘  └────────┬────────┘
            │                    │                    │
            ▼                    ▼                    ▼
   ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
   │ Rating Engine   │  │ Statistics      │  │ Point Table     │
   │ - Player skill  │  │ - Win/Loss      │  │ - Tier * Place  │
   │ - Confidence    │  │ - Head-to-head  │  │ - Policy ver    │
   └────────┬────────┘  └────────┬────────┘  └────────┬────────┘
            │                    │                    │
            ▼                    ▼                    ▼
   ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
   │ player_ratings  │  │ player_stats    │  │ ranking_ledger  │
   │ rating_history  │  │ achievements    │  │ ranking_points  │
   └─────────────────┘  └─────────────────┘  └─────────────────┘
            │                    │                    │
            ▼                    ▼                    ▼
   ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
   │ Matchmaking     │  │ Player Passport │  │ Leaderboards    │
   │ Divisioning     │  │ Profile UX      │  │ National/Region │
   └─────────────────┘  └─────────────────┘  └─────────────────┘
```

Key rule:
- Rating and Ranking both read from `official_matches`, but they write to **separate** tables.
- Rating changes do not affect Ranking points.
- Ranking points do not affect Rating value.
- Both can be displayed on the Player Passport.

---

## 10. TOURNAMENT → RESULT → RATING FLOW

```
TOURNAMENT MATCH COMPLETED
    │
    ▼
Score entered by referee / official
    │
    ▼
RESULT STATUS: PENDING → SUBMITTED → CONFIRMED → OFFICIAL
    │
    ▼
[Event] MatchResultBecameOfficial
    │
    ▼
ProcessOfficialMatchRatingJob (queue)
    │
    ├── Idempotency check: has this match already been processed?
    │
    ├── Load Rating Policy version active at match date
    │
    ├── Determine verification weight (tournament level)
    │
    ├── For each player/team:
    │   ├── Get current discipline rating
    │   ├── Calculate expected result
    │   ├── Calculate actual result
    │   ├── Calculate impact
    │   ├── Write player_rating_histories (before/after)
    │   └── Update player_ratings
    │
    └── Fraud detection rules evaluate match
        ├── If suspicious → create fraud_flag
        └── Do not auto-lock
```

Re-calculation safety:
- If official result is corrected, run compensation:
  - Reverse previous rating impact via new history entry
  - Re-process with corrected result
  - Mark re-processed in history

---

## 11. TOURNAMENT → RESULT → RANKING FLOW

```
TOURNAMENT COMPLETED
    │
    ▼
Final placements confirmed per category
    │
    ▼
Tournament has valid SANCTION (ranking eligible)
    │
    ▼
[Event] TournamentResultsOfficial
    │
    ▼
CalculateRankingPointsJob (queue)
    │
    ├── Idempotency check per player/discipline/category
    │
    ├── Load active Ranking Policy version
    │
    ├── Map placement to point table
    │
    ├── For each ranked player:
    │   ├── Create ranking_point_transaction (+points)
    │   ├── Set expires_at based on policy
    │   └── Link to tournament/category
    │
    └── Refresh Ranking Snapshot
        ├── Recalculate leaderboard
        ├── Cache in Redis
        └── Update ranking_snapshots
```

Rolling 52-week:
- Scheduler runs `ExpireRankingPointsJob` periodically.
- Expired points create negative ledger entries.
- Leaderboard refresh triggered after expiration batch.

---

## 12. SAAS ENTITLEMENT MODEL

### 12.1 Customer Types

| Customer | Plan Examples | Key Features |
|----------|---------------|--------------|
| Club | CLUB_STARTER, CLUB_PRO | Booking, membership, open play, league, coach |
| Venue Owner | VENUE_BASIC, VENUE_PRO | Multi-branch, dynamic pricing, reporting |
| Tournament Organizer | PER_EVENT, MONTHLY, ANNUAL | Registration, bracket, scheduling, live score, ranking submission |
| Association / Federation | ASSOCIATION_PROVINCE, ASSOCIATION_NATIONAL | Player registry, club registry, sanctioning, ranking, calendar |

### 12.2 Entitlement Engine

Existing `tenant_plans.features` JSON controls access.

New features to add:

```json
{
  "competition": true,
  "tournament_sanctioning": false,
  "ranking_authority": false,
  "national_ranking_view": true,
  "live_score": true,
  "ranking_submission": true,
  "external_rating_sync": false,
  "white_label": false,
  "custom_domain": false
}
```

### 12.3 Federation Tenant Entitlements

- Can create `ranking_authorities`
- Can define `ranking_policies`
- Can approve `tournament_sanctions`
- Can manage qualification rules
- Can access national player registry (read-only with audit)

---

## 13. DATA OWNERSHIP MODEL

### 13.1 Tenant Private Data

Owned by tenant; other tenants cannot access:

- Customer contact info (phone, email, internal notes)
- Bookings, payments, invoices
- Staff, schedules, payroll
- POS orders, inventory, revenue
- Internal CRM notes
- Member benefits usage

### 13.2 Platform Shared Data

Shared across tenants according to policy and privacy settings:

- Public tournament listings
- Official match results
- Public player passport (respecting privacy_level)
- National/Regional/Province rankings
- Sanctioned events
- Club directory (basic info)

### 13.3 Data Ownership Matrix

| Data | Owner | Shared? | Notes |
|------|-------|---------|-------|
| Player identity | Platform | With consent | National Player ID |
| Player private profile | Player / Tenant | No | Contact, internal notes |
| Player passport public data | Player | Yes | Configurable privacy |
| Tournament results | Organizer + Authority | Yes (official) | Immutable ledger |
| Ranking points | Ranking Authority | Yes | Public leaderboard |
| Rating | Platform engine | Yes (summary) | Full history private |
| Booking records | Tenant | No | |
| Payments | Tenant | No | |

---

## 14. PUBLIC VS PRIVATE DATA MODEL

### 14.1 Public Data

Accessible without login (rate limited):

- National ranking leaderboards
- Tournament public pages / brackets / schedules / live scores
- Player passport public fields
- National competition calendar
- Club directory

### 14.2 Authenticated Data

Requires login and appropriate permissions:

- Player full match history
- Player rating history
- Tournament registration and payment
- Club internal dashboards
- Admin functions

### 14.3 Privacy Levels on Player Passport

```php
enum PlayerPrivacyLevel: string {
    case PUBLIC = 'public';     // anyone can view
    case CLUB = 'club';         // club members + verified players
    case PRIVATE = 'private';   // only player + admins
}
```

Public fields always visible:
- National Player ID
- Display name
- Province
- Internal rating summary
- National ranking summary
- Official match results (as opponent)

Private fields:
- Email, phone
- Detailed rating history
- Booking history
- Non-official match results

---

## 15. NATIONAL PLAYER ID STRATEGY

### 15.1 ID Format

Recommended:

```
VN-PKL-0000123456
```

Components:
- `VN` — country code
- `PKL` — sport code
- `0000123456` — zero-padded sequential number

Alternative internal: UUID v7 for global uniqueness and sortability.

### 15.2 ID Assignment

- Generated when a player is verified or registers for first official event.
- Stored in `player_competitive_profiles.national_player_id`.
- Unique index.
- Used on QR code, public profile, tournament registration.

### 15.3 Duplicate Resolution Workflow

```
New registration / import
    │
    ▼
Identity check against player_identity_claims
    │
    ├── Exact National Player ID match → link
    ├── Verified phone/email match → recommendation
    ├── External provider ID match → recommendation
    ├── Name-only match → recommendation (low confidence)
    └── No match → create new profile
    │
    ▼
If recommendation → create player_identity_claim (pending)
    │
    ▼
Moderator review → approve / reject / merge_request
    │
    ▼
Merge approved → write player_merge_audits
```

---

## 16. DATABASE ERD

### 16.1 Core Entity Graph

```
organizations 1──N organization_roles
   │
   ├──N tenants (legacy alias, migrate to organizations)
   │
   ├──N branches 1──N courts
   │
   ├──N ranking_authorities
   │
   └──N tournament_sanctions

players 1──1 player_competitive_profiles
   │
   ├──N player_identity_claims
   ├──N player_external_ratings
   ├──N player_ratings
   ├──N player_rating_histories
   ├──N player_rank_histories
   ├──N ranking_point_transactions
   ├──N player_achievements
   └──N player_badges

ranking_authorities 1──N ranking_seasons
   ├──N ranking_policies 1──N ranking_policy_versions
   │                          ├──N ranking_point_tables
   │                          └──N ranking_categories
   └──N ranking_disciplines

official_matches N──N match_participants
   │
   ├──N match_results
   ├──N tournament_matches (optional link)
   └──N competition_fixtures (optional link)

tournaments 1──N tournament_categories
   ├──N tournament_registrations
   ├──N tournament_checkins
   ├──N tournament_draw_versions
   ├──N tournament_seeding
   ├──N tournament_schedules
   └──1 tournament_sanctions

ranking_snapshots 1──N ranking_snapshot_entries
```

### 16.2 Full Table Inventory for Competition + Ranking

```sql
-- PLAYER IDENTITY & PASSPORT
players
player_competitive_profiles
player_identity_claims
player_merge_requests
player_merge_audits

-- RATING
player_external_ratings
player_ratings
player_rating_histories
rating_providers
rating_policies

-- MATCHES (official abstraction)
official_matches
match_participants
match_results
match_result_audit_logs

-- FRAUD
fraud_detection_rules
fraud_flags

-- RANKING AUTHORITY
ranking_authorities
ranking_seasons
ranking_disciplines
ranking_categories
ranking_policies
ranking_policy_versions
ranking_point_tables

-- RANKING DATA
ranking_point_transactions
ranking_snapshots
ranking_snapshot_entries
player_rank_histories

-- TOURNAMENT
tournament_tiers
tournament_sanctions
qualification_rules
player_qualifications
tournament_draw_versions
tournament_seeding
tournament_schedules
tournament_checkins
tournament_disputes

-- ORGANIZATION
organizations
organization_roles
```

### 16.3 Key Indexes

```sql
-- Player identity
CREATE UNIQUE INDEX idx_player_competitive_profile_national_id
    ON player_competitive_profiles(national_player_id);

CREATE INDEX idx_player_identity_claims_value ON player_identity_claims(claim_type, claim_value);

-- Rating
CREATE INDEX idx_player_ratings_player_discipline ON player_ratings(player_id, discipline);
CREATE INDEX idx_player_rating_histories_player_date ON player_rating_histories(player_id, discipline, created_at);

-- Ranking ledger
CREATE INDEX idx_ranking_point_transactions_player ON ranking_point_transactions(
    player_id, discipline_id, category_id, effective_at, expires_at
);

-- Official matches
CREATE INDEX idx_official_matches_date ON official_matches(match_date, status);
CREATE INDEX idx_match_participants_player ON match_participants(player_id, official_match_id);

-- Snapshots
CREATE INDEX idx_ranking_snapshots_lookup ON ranking_snapshots(
    ranking_authority_id, season_id, discipline_id, category_id, snapshot_date
);
CREATE INDEX idx_ranking_snapshot_entries_rank ON ranking_snapshot_entries(ranking_snapshot_id, rank);
```

---

## 17. API ARCHITECTURE

### 17.1 Public API Endpoints

```
GET /api/v1/rankings
GET /api/v1/rankings/national
GET /api/v1/rankings/{authority}/{discipline}/{category}

GET /api/v1/players/{id}/rating
GET /api/v1/players/{id}/ranking
GET /api/v1/players/{id}/matches
GET /api/v1/players/{id}/tournaments
GET /api/v1/players/{id}/head-to-head/{opponent_id}

GET /api/v1/tournaments
GET /api/v1/tournaments/{id}
GET /api/v1/tournaments/{id}/results
GET /api/v1/tournaments/{id}/brackets
GET /api/v1/tournaments/{id}/schedule

GET /api/v1/clubs/{id}/ranking
GET /api/v1/clubs

GET /api/v1/calendar
```

### 17.2 Authenticated API Endpoints

```
POST /api/v1/tournaments/{id}/register
POST /api/v1/tournaments/{id}/withdraw
POST /api/v1/tournaments/{id}/check-in

POST /api/v1/matches/{id}/score           # referee / official
POST /api/v1/matches/{id}/dispute

POST /api/v1/ranking-authorities/{id}/sanction  # federation admin
POST /api/v1/ranking-authorities/{id}/recalculate

GET /api/v1/me/rating
GET /api/v1/me/ranking
GET /api/v1/me/passport
```

### 17.3 API Design Rules

- Versioned URLs `/api/v1`.
- Standard response envelope `{success, data, meta/error}`.
- Rate limiting: public 30/min, authenticated 60/min, premium 300/min.
- Public ranking endpoints served from cache/snapshots, not real-time calculation.
- Player search supports name, National Player ID, province, club.

### 17.4 SEO & Public Pages

```
/ranking
/ranking/national
/ranking/men-singles
/ranking/women-singles
/ranking/mixed
/player/{slug}
/clubs
/tournaments
/tournaments/{slug}/bracket
/tournaments/{slug}/schedule
```

Server-rendered or static-cache compatible.

---

## 18. QUEUE / JOB ARCHITECTURE

### 18.1 New Queues

| Queue | Priority | Purpose |
|-------|----------|---------|
| `rating` | high | Process official match rating updates |
| `ranking` | high | Calculate ranking points, refresh snapshots |
| `ranking_expiry` | default | Expire old ranking points (rolling windows) |
| `fraud_detection` | default | Run anti-manipulation rules |
| `tournament_draw` | default | Generate draw versions |
| `tournament_schedule` | default | Optimize schedules |
| `external_rating_sync` | low | Sync DUPR / external ratings |
| `leaderboard_cache` | low | Warm ranking caches |

### 18.2 Key Jobs

```php
class ProcessOfficialMatchRatingJob {
    public $queue = 'rating';
    public $tries = 3;
    // Idempotent: check processed flag before re-running
}

class CalculateRankingPointsJob {
    public $queue = 'ranking';
    public $tries = 3;
}

class RefreshRankingSnapshotJob {
    public $queue = 'ranking';
    public $tries = 2;
}

class ExpireRankingPointsJob {
    public $queue = 'ranking_expiry';
}

class RunFraudDetectionRulesJob {
    public $queue = 'fraud_detection';
}

class GenerateTournamentDrawJob {
    public $queue = 'tournament_draw';
}

class OptimizeTournamentScheduleJob {
    public $queue = 'tournament_schedule';
}

class SyncExternalRatingJob {
    public $queue = 'external_rating_sync';
}
```

### 18.3 Event Flow

```
MatchResultBecameOfficial
    ├── ProcessOfficialMatchRatingJob
    └── RunFraudDetectionRulesJob

TournamentResultsOfficial
    ├── CalculateRankingPointsJob
    ├── RefreshRankingSnapshotJob
    └── UpdatePlayerAchievementsJob

RankingPolicyChanged
    └── ScheduleRankingRebuildJob
```

### 18.4 Artisan Commands

```bash
php artisan rating:rebuild --season= --discipline= --from= --player=
php artisan ranking:rebuild --season= --discipline= --from= --player=
php artisan ranking:snapshot --authority= --date=
php artisan fraud:review --player= --rule=
php artisan tournament:lock-draw --tournament= --category=
```

---

## 19. SECURITY ARCHITECTURE

### 19.1 New Roles

| Role | Code | Permissions |
|------|------|-------------|
| Ranking Admin | `ranking_admin` | ranking.policy.manage, ranking.recalculate, ranking.adjust |
| Ranking Reviewer | `ranking_reviewer` | ranking.audit, fraud.review |
| Tournament Verifier | `tournament_verifier` | tournament.sanction, result.audit |
| National Data Admin | `national_data_admin` | player.merge, national player registry |

### 19.2 Permission Additions

```php
'ranking.policy.manage',
'ranking.recalculate',
'ranking.adjust',
'ranking.audit',
'tournament.sanction',
'tournament.result.audit',
'player.merge',
'player.identity.manage',
'fraud.review',
'fraud.override',
```

### 19.3 Audit Requirements

All actions below must write audit logs:
- Player merge / identity claim approval
- Tournament sanction approve/reject
- Match result correction
- Ranking manual adjustment
- Rating policy / ranking policy change
- Draw regeneration
- Dispute resolution

### 19.4 Anti-Cheat Safeguards

- Referee permission required to confirm official results.
- Tournament directors cannot silently redraw after lock.
- Draw versions immutable; new version requires reason.
- Mass result import requires validation preview.
- Fraud flags require human review before action.

---

## 20. SCALING STRATEGY

### 20.1 Target Scale

Design must not require rewrite for:
- 1,000,000 players
- 100,000 tournaments
- 50,000,000 matches

### 20.2 Database Scaling

- Use **BIGINT unsigned** primary keys for high-volume tables (`official_matches`, `match_participants`, `ranking_point_transactions`, `player_rating_histories`).
- Partition by time:
  - `official_matches` by `match_date`
  - `ranking_point_transactions` by `effective_at`
  - `player_rating_histories` by `created_at`
- Archive cold history to compressed tables or object storage.

### 20.3 Caching Strategy

| Data | Cache | TTL |
|------|-------|-----|
| National leaderboard | Redis sorted set + snapshot | 1 hour / weekly snapshot |
| Player passport | Redis hash | 15 min |
| Tournament public page | Full-page cache | 5 min |
| Rating summary | Redis hash | 5 min |
| Ranking policy active version | Redis string | 1 hour |

### 20.4 Read Replicas

- Ranking snapshot reads from read replicas.
- Public API served from cache or replica.
- Rating/Ranking writes go to primary.

### 20.5 Batch Processing

- Ranking rebuild runs in batches by player ID range.
- Rating rebuild processes matches in chronological order.
- Use idempotency keys to allow resume on failure.

---

## 21. IMPLEMENTATION ROADMAP

### Phase C1 — Tournament Core
- Tournament CRUD with verification levels
- Categories with eligibility rules
- Registration + payment + waitlist
- Player/team check-in

### Phase C2 — Seeding, Draw, Scheduling, Live Score
- SmartSeedingEngine
- TournamentDrawEngine with audit
- TournamentScheduleService with rest/conflict constraints
- Live score input + public scoreboard

### Phase C3 — Internal Rating
- RatingEngine interface + InternalRatingProvider
- player_ratings, player_rating_histories
- Official match abstraction
- Fraud detection rules
- rating:rebuild command

### Phase C4 — National Ranking
- Ranking authorities, seasons, policies, versions
- Point tables + ranking ledger
- Leaderboard snapshots
- ranking:rebuild command
- Public ranking portal

### Phase C5 — Sanctioning, Qualification, Multi-level Rankings
- Tournament sanctioning workflow
- Qualification rules + player_qualifications
- Regional / Provincial / Club rankings

### Phase C6 — External Integration & Federation SaaS
- ExternalRatingConnectorInterface
- DUPR connector (when authorized)
- Federation/Association SaaS features
- API ecosystem + partners

---

## 22. DATABASE MIGRATION PLAN

### 22.1 Migration Strategy

- Each phase gets one or more migrations.
- Migrations use `if (! $this->db->tableExists(...))` for safety.
- Add new tables before modifying existing tables.
- Existing `player_ratings` and `player_match_history` are **replaced** by new design; data migration script provided.
- No destructive drop of legacy tables until Phase C4 completes and verified.

### 22.2 Migration Files

```text
2026-08-10-100000_CreateOrganizationsAndRoles.php
2026-08-10-110000_CreatePlayerCompetitiveProfiles.php
2026-08-10-120000_CreatePlayerIdentityClaims.php
2026-08-10-130000_CreatePlayerExternalRatings.php
2026-08-10-140000_CreateRatingPolicies.php
2026-08-10-150000_CreateOfficialMatches.php
2026-08-10-160000_CreatePlayerRatingsV2.php
2026-08-10-170000_CreatePlayerRatingHistories.php
2026-08-10-180000_CreateFraudDetection.php
2026-08-10-190000_CreateRankingAuthorities.php
2026-08-10-200000_CreateRankingSeasonsAndPolicies.php
2026-08-10-210000_CreateRankingPointTables.php
2026-08-10-220000_CreateRankingPointTransactions.php
2026-08-10-230000_CreateRankingSnapshots.php
2026-08-10-240000_CreateTournamentTiersAndSanctions.php
2026-08-10-250000_CreateTournamentDrawVersions.php
2026-08-10-260000_CreateTournamentSchedules.php
2026-08-10-270000_CreateQualificationRules.php
2026-08-10-280000_MigrateLegacyPlayerRatings.php
2026-08-10-290000_AddCompetitionPermissions.php
```

### 22.3 Data Migration: Legacy Player Ratings

Legacy tables:
- `player_ratings` (old ELO scope-based)
- `player_match_history` (old match records)
- `player_statistics.elo_rating`
- `players.rating_score`

Migration steps:
1. Create new `player_competitive_profiles` for all `players`.
2. For each old `player_ratings` row with `scope_type='global'`:
   - Create `player_ratings` discipline row (default to `singles` or infer from data).
   - Copy value to `player_rating_histories` as initial baseline.
3. For each `player_match_history` row:
   - Create `official_matches` + `match_participants`.
   - Link to tournament if `tournament_id` is set.
4. Leave old tables intact; drop in later release after validation.

### 22.4 Seed Data

```php
// Required seeders
TenantTypeSeeder            // club, venue, organizer, association
RankingAuthoritySeeder      // national, regional, provincial samples
RankingDisciplineSeeder     // mens_singles, womens_singles, etc.
RankingCategorySeeder       // OPEN, U18, 35+, ...
RatingPolicySeeder          // InternalRatingProvider default config
TournamentTierSeeder        // Tier S/A/B/C/D examples
PointTableSeeder            // Example point tables
FraudRuleSeeder             // Default detection rules
PermissionSeeder            // Add competition/ranking permissions
```

### 22.5 Rollback Plan

- Every migration has `down()` method.
- Before running migration on production:
  - Backup database.
  - Run migrations on staging with full dataset.
  - Verify `rating:rebuild` and `ranking:rebuild` produce expected results.
- Feature flags allow disabling new Competition features if issues arise.

---

## Appendix A: Code Location Mapping (CodeIgniter 4)

| Layer | Directory |
|-------|-----------|
| Migrations | `app/Database/Migrations/` |
| Seeders | `app/Database/Seeds/` |
| Entities | `app/Entities/` |
| Models | `app/Models/` |
| Services | `app/Services/Competition/`, `app/Services/Rating/`, `app/Services/Ranking/` |
| Controllers | `app/Controllers/Admin/`, `app/Controllers/Api/`, `app/Controllers/Player/` |
| Views | `app/Views/admin/competition/`, `app/Views/public/ranking/`, etc. |
| Jobs | `app/Jobs/` (or `app/Services/Jobs/` depending on existing convention) |
| Commands | `app/Commands/` |
| Filters | `app/Filters/` |
| Languages | `app/Language/vi/`, `app/Language/en/` |

---

## Appendix B: Glossary Additions

| Tiếng Việt | English | Ghi chú |
|---|---|---|
| Hộ chiếu VĐV | Player Passport | Hồ sơ cạnh tranh công khai |
| Mã VĐV quốc gia | National Player ID | Định danh toàn hệ thống |
| Điểm trình | Rating | Trình độ hiện tại |
| Xếp hạng | Ranking | Thành tích thi đấu |
| Hạng mục | Category / Division | Nội dung thi đấu |
| Nội dung | Discipline | singles/doubles/mixed |
| Cấp độ giải | Tournament Tier | Tier S/A/B/C/D... |
| Phê duyệt giải | Sanctioning | Cấp phép tính BXH |
| Bảng điểm xếp hạng | Point Table | Điểm theo thứ hạng |
| Sổ cái điểm | Ranking Ledger | Giao dịch điểm BXH |
| Ảnh chụp BXH | Ranking Snapshot | Bảng xếp hạng theo kỳ |
| Độ tin cậy | Confidence / Reliability | Độ tin cậy rating |
| Phát hiện gian lận | Fraud Detection | Chống thao túng rating |

---

> **Next Step:** Review and approve this architecture document. Once approved, begin Phase C1 migrations and services. No Competition module code is written before this approval.
