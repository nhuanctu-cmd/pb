# Source of Truth Matrix

Ngày phê duyệt đề xuất: 2026-08-09  
Phạm vi: Pickleball National Sports Platform hiện tại và target architecture

## 1. Canonical ownership

| Khái niệm | Canonical source | Các bảng/đối tượng liên quan | Quy tắc ghi |
|---|---|---|---|
| USER | Identity domain | `users`, roles, permissions, sessions | Chỉ identity/auth service ghi; không tạo user riêng trong domain khác |
| ORGANIZATION/TENANT | Tenant/organization domain | `tenants`, `organization_memberships`, subscriptions | Tenant context lấy từ session/API auth, không tin tenant ID tự do từ client |
| CUSTOMER | CRM domain | `customers`, `customer_timeline_events`, `customer_tags`, `customer_tag_links`, booking/invoice links | Không dùng Player thay Customer; booking snapshot giữ nguyên, CustomerService resolve theo player/phone/email |
| PLAYER | Player Registry | `players`, `player_competitive_profiles` | Một canonical player; tenant projection/membership chỉ tham chiếu |
| NATIONAL PLAYER ID | Player Passport | `player_competitive_profiles.public_player_id` và identity service | Immutable, unique, public-safe; không expose auto-increment làm public identity |
| PLAYER IDENTITY CLAIM | Identity/Governance | `player_identity_claims`, merge request/audit tables | Append-only claim/review; merge phải lưu lineage, không hard delete source |
| VENUE/FACILITY | Venue domain | `facilities`, `branches`, `courts` | Tenant-scoped; facility → branch → court là quan hệ vận hành chính |
| COURT | Venue domain | `courts`, court types/status/maintenance | Booking, tournament và device chỉ tham chiếu court; status transition qua service |
| CLUB OPERATIONAL | Tenant/Club domain | `clubs`, `player_club_memberships`, `facility_club_assignments` | Dữ liệu private/financial thuộc tenant; facility có thể liên kết nhiều club |
| CLUB NETWORK | Platform network domain | `platform_clubs`, aliases | Chỉ là network identity/public projection; không thay operational private data |
| BOOKING | Booking domain | `bookings`, items, logs, QR, waitlist, recurring | BookingService là write boundary; booking có thể tạo match nhưng không bắt buộc |
| OPEN PLAY/SESSION | Community/Booking domain | open-play/session/rotation tables | Player/match tham chiếu registry/unified graph, không tạo player riêng |
| TOURNAMENT/COMPETITION | Competition domain | `tournaments`, categories, registrations, rules, templates | Tournament lifecycle/policy version là nguồn chính; template không copy player/payment/result |
| REGISTRATION | Competition domain | `tournament_registrations`, participants/check-ins | Registration tham chiếu canonical player/team, không duplicate hồ sơ |
| DRAW/SEED/SCHEDULE | Competition operations | brackets, groups, seeding/draw policies, schedule locks, tournament matches | Manual change phải audit; published schedule không bị silent overwrite |
| MATCH | Unified Match Graph | `matches`, `match_participants`, `match_games` | Một canonical match; source là `source_type/source_id` (booking, club, tournament, import...) |
| MATCH PARTICIPANTS | Match domain | `match_participants`, side/team structures | Không dùng player1/player2/player3/player4 hard-code |
| RESULT | Match Result domain | `match_results`, `match_result_versions` | Official result immutable; correction tạo version mới, không update đè lịch sử |
| RATING | Rating domain | `player_rating_profiles`, `rating_transactions`, `rating_ledgers` | Mọi delta đi qua ledger/transaction có match/result/policy/provenance; không update trực tiếp rating |
| RATING PROVIDER | Rating network | `rating_providers`, provider links/records/imports | Internal/external/federation/partner tách biệt; sync lỗi không làm hỏng internal rating |
| SKILL | Skill classification domain | skill bands/claims/assessments | Skill khác rating; claim/assessment có provenance và không tự ghi đè evidence |
| RANKING | Ranking domain | authorities, policies, `ranking_point_ledgers`, snapshots | Điểm là ledger; snapshot là read model lịch sử; exceptional change là adjustment ledger |
| GOVERNANCE | Governance domain | authorities, sanctions, decisions, appeals, disputes, integrity | Heuristic chỉ tạo flag/case; decision phải có authority, actor, reason, audit |
| RULESET | Ruleset domain | rulesets/versions, competition/rating/ranking policy versions | Historical version immutable, có effective period và gắn vào event/result/ledger |
| PROVENANCE | Provenance domain | `data_provenance`, `data_provenance_records` | Ghi who/when/source/verifier/version cho identity, result, rating, ranking, sanction |
| PAYMENT | Commerce/payment domain | invoices, payments, refunds, wallet, POS | Payment ledger là nguồn tài chính; registration/booking/membership chỉ tham chiếu |
| NOTIFICATION | Notification domain | notifications/templates/jobs | Notification abstraction nhận domain event; không gửi trực tiếp trong controller |
| PRINT JOB | Print domain | print services + `jobs` | Batch print phải queue; request HTTP chỉ tạo job và trả trạng thái |
| PUBLIC TOURNAMENT | Public projection | published tournament DTO/API/cache | Chỉ expose public DTO: không phone, email, notes, payment/private finance |
| AUDIT | Audit domain | `audit_logs` | Mọi mutation nhạy cảm lưu actor, tenant, before/after, reason, request/job ID nếu có |
| QUEUE | Operations/integration | `jobs`, webhook deliveries, import/rating jobs | Idempotency key, retry/dead-letter; mọi job phải tenant-aware khi chứa tenant data |

## 2. Legacy-to-canonical mapping

| Legacy/source | Canonical target | Migration policy |
|---|---|---|
| `player_ratings` / `PlayerRatingModel` | `player_rating_profiles` + `rating_transactions` | Chuyển lịch sử thành `transaction_type=seed/legacy_backfill`; ngừng write mới vào legacy |
| `player_statistics` rating fields | Rating profile/reliability read model | Chỉ giữ compatibility read, không dùng làm authority |
| `tournament_matches` | `matches` với `source_type=tournament` và `source_id` | Adapter hiện hữu giữ tạm; invariant phải đảm bảo một canonical match |
| tournament score tables | `match_results`/versions | Official event phải tạo versioned result và provenance |
| tenant `clubs` | Operational club | Giữ tenant/private scope; network publication đi qua `platform_clubs` alias |
| booking customer columns | Customer aggregate | Snapshot booking giữ nguyên cho lịch sử; backfill Customer không sửa snapshot |
| player ranking columns/legacy ranking queries | ranking ledgers/snapshots | Leaderboard mới đọc canonical snapshot/profile, legacy chỉ compatibility |

## 3. Required lineage

```text
USER / ACTOR
  → CUSTOMER (nếu là khách hàng)
  → PLAYER REGISTRY / PUBLIC PLAYER ID (nếu là VĐV)
  → MEMBERSHIP / BOOKING / REGISTRATION
  → UNIFIED MATCH
  → MATCH RESULT VERSION
  → RATING TRANSACTION / RATING PROFILE
  → RANKING POINT LEDGER / SNAPSHOT
  → PUBLIC DTO / CRM TIMELINE / PRINT
```

Mọi nhánh phải giữ lại `tenant_id`, `source`, `provenance_id`, policy/ruleset version và correction lineage tương ứng.

## 4. Read/write boundaries

### Được phép ghi trực tiếp qua service canonical

- `PlayerPassportService` cho player identity/claim/merge.
- `FacilityService`/`CourtService` cho venue/court/club assignment.
- `BookingService` cho booking/availability/hold.
- `UnifiedMatchService` cho canonical match/participants.
- `ResultCorrectionService`/match governance cho result version.
- `RatingEngine`/`RatingAdjustmentService` cho rating ledger/profile.
- `RankingRebuildService`/ranking services cho ranking ledger/snapshot.
- `GovernanceService` cho sanction/appeal/integrity decisions.
- `PaymentService` cho invoice/payment/refund/wallet.

### Không được làm

- Controller tự tính rating/ranking/eligibility/availability.
- Ghi `rating`, `ranking_points` hoặc official result bằng câu lệnh update trực tiếp.
- Tạo player/customer/match riêng cho tournament hoặc booking.
- Dùng public DTO để trả private phone/email/payment/notes.
- Nhận tenant scope từ payload thay cho authenticated tenant context.
- Xóa result/rating/ranking history để sửa sai dữ liệu.

## 5. Data classification

| Classification | Ví dụ | Quyền |
|---|---|---|
| PLATFORM_PUBLIC | public player ID, verified player, published tournament, public ranking | Public API/cache |
| PLATFORM_MEMBER | controlled player history, club relationship | Player/authorized network |
| TENANT_PRIVATE | customer notes, bookings, staff operations | Tenant RBAC |
| FINANCIAL | invoice, payment, refund, wallet, subscription | Finance permission |
| RESTRICTED | identity evidence, fraud/integrity evidence, credentials | Governance/platform authority |
| AUDIT | actor, before/after, reason, request/job ID | Authorized audit users |

## 6. Approval state

Matrix này là baseline bắt buộc cho code mới. Những nguồn song song trong mục Legacy-to-canonical chỉ được tồn tại trong migration/compatibility period và phải có test ngăn phát sinh write mới.
