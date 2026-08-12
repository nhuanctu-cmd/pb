# Rating Engine Audit & V1 Contract

The staged organization/import path is implemented in `imports.md`: organizations submit evidence and skill claims, while the canonical rating ledger remains platform-calculated from official immutable result versions.

Ngày audit: 09/08/2026

## A–F. Existing audit

### Rating đang ở đâu?

Project hiện có hai implementation:

1. Legacy `player_ratings.rating` (integer ELO khoảng 1000+) và `players.rating_score` / `player_statistics.elo_rating`.
2. `rating_ledgers` + `RatingNetworkService`, chỉ chạy khi `matches.status=official` và `match_results.status=official`, nhưng vẫn dùng thang ELO 1000, không có discipline, policy version, result version, score margin hoặc immutable correction flow.

`PlayerRatingService::recordMatch()` còn cho admin ghi trận trực tiếp vào `player_match_history` và cập nhật current rating; flow này không kiểm tra official result, không có idempotency và không tách singles/doubles.

### Player audit

- `players` là tenant-scoped CRM identity và có các field legacy `rating_score`, `level`.
- `player_competitive_profiles` đã có National Player ID, public privacy level và một số cached summary nhưng chưa phải rating source-of-truth per discipline.
- `player_identity_claims` đã tồn tại cho duplicate/identity resolution.
- Chưa có `player_skill_claims`, `club_player_skill_assessments`, self-assessment hoặc seed audit.

### Match/result audit

- `matches`, `match_participants`, `match_games`, `match_results`, `match_result_versions` đã có.
- Official publish được gọi từ `UnifiedMatchService`, sau đó gọi `RatingNetworkService`.
- `MatchGovernanceService` đã có dispute nhưng khi result bị sửa/chấp nhận dispute chưa tạo reversal/replacement rating transactions.

### Tournament audit

- Tournament categories đã có `discipline`, `eligibility_rules`, registration `eligibility_status`.
- Chưa có một eligibility engine dùng rating/reliability/official skill và policies STRICT, AVERAGE_WITH_CAP, OPEN, MANUAL_REVIEW.

## G. Proposed domain

Canonical rating gồm: Provider → Discipline → Policy Version → Player Rating Profile → Immutable Rating Transaction → Reliability Snapshot → Skill Band.

Legacy tables vẫn được đọc bởi compatibility surfaces nhưng không được phép ghi thành platform rating mới.

## H. Database plan

### Reuse

`players`, `player_competitive_profiles`, `player_identity_claims`, `matches`, `match_participants`, `match_games`, `match_results`, `match_result_versions`, `rating_providers`, `match_disputes`, `audit_logs`.

### Extend

Không đổi/ghi đè dữ liệu legacy. `rating_providers` được dùng lại; policy/discipline linkage được thêm bằng các bảng canonical riêng.

### New

`rating_disciplines`, `skill_level_bands`, `rating_policy_versions`, `rating_match_type_weights`, `player_rating_profiles`, `rating_transactions`, `rating_reliability_snapshots`, `player_skill_claims`, `club_player_skill_assessments`, `player_skill_assessments`, `rating_integrity_flags`.

## I. Algorithm V1

Rating scale là pickleball skill scale, DECIMAL(6,3), không phải national ranking.

```text
team_rating = average(player ratings)                  # TEAM_AVERAGE
expected    = 1 / (1 + 10 ^ ((opponent - team) / 2.0))
actual      = 0.5 + (winner_margin * 0.5)              # normalized [0,1]
delta       = clamp((actual - expected) * base_delta
                    * match_type_weight
                    * volatility(reliability), max_delta)
new_rating  = round(old_rating + delta, 3)
```

`base_delta`, score-margin impact, match weights, volatility, max delta and establishment threshold live in policy JSON. V1 is deterministic, explainable and not a claim to reproduce any external vendor.

Singles and doubles use separate profile rows. Doubles uses configurable `TEAM_AVERAGE` strategy.

## J. Reliability V1

Default policy weights:

```text
volume 30% · verification 25% · recency 20% · opponent diversity 15% · competition diversity 10%
```

Each component is normalized 0–100. Reliability decay uses exponential half-life from policy. Rating status is `NR`, `PROVISIONAL`, `ESTABLISHED`, `INACTIVE`, `UNDER_REVIEW`, or `SUSPENDED`; establishment threshold is policy data, not a service constant.

## K. Skill bands

Default configurable bands: NR, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0, 5.5+. Resolver uses range lookup, never `round(rating)`. A claimed/estimated skill is not official platform rating.

## L–M. Eligibility / anti-sandbagging

- `STRICT`: every player must be within category min/max.
- `AVERAGE_WITH_CAP`: team average within max and no player above individual cap.
- `OPEN`: no rating cap.
- `MANUAL_REVIEW`: return review flag, never silently pass.
- Play-up is allowed only by policy. Play-down is rejected by default.
- A declared tournament/club skill never overrides platform rating. Conflicts produce `RATING_CONFLICT` / `POSSIBLE_UNDERRATED_PLAYER` flags.

## N–O. Correction and rebuild

Rating transactions are insert-only. A correction invalidates the original impact and writes reversal/replacement transactions. Rebuild replays eligible official result versions in chronological order under an explicit policy/provider and supports dry-run.

## P–Q. API and test plan

- `GET /api/v1/players/{id}/ratings`
- `GET /api/v1/players/{id}/ratings/{discipline}/history`
- `GET /api/public/v1/players/{nationalId}/ratings`
- `POST /api/v1/player/skill-assessments`
- `POST /api/v1/player/rating-claims`
- `POST /api/v1/tournament-registrations/{id}/eligibility-check`

Test coverage must include discipline isolation, expected win/upset, doubles average, ineligible/disputed result, idempotency, correction, out-of-order rebuild, reliability decay, band boundaries, strict/average-with-cap eligibility, tenant privacy, and no private fields in public API.

## R. Implementation note

This repository is CodeIgniter 4, not Laravel 11. The implementation follows existing CodeIgniter MVC + service/model + migration + command conventions rather than introducing Laravel-only classes or a second user/match system.
