# Current System State

Ngày audit: 2026-08-09  
Repository: `pickball-system`  
Mục tiêu đối chiếu: Master Prompt — Pickleball National Sports Platform

## 1. Phạm vi và phương pháp

Audit này dùng source tree, cấu hình runtime, routes, migrations, models, services, controllers, views, seeders, database hiện tại và test suite. Quy tắc là reuse → refactor → migrate → create only when necessary; không rewrite CodeIgniter sang framework khác.

Thống kê source tree hiện tại:

| Khu vực | Số lượng/hiện trạng |
|---|---:|
| PHP/source files được inventory | 813 |
| Controllers | 93 |
| Models | 163 |
| Services | 89 |
| Migrations | 60 |
| Seeders | 21 |
| Views | 165 |
| Automated tests | 59 PHP test files; 174 tests / 545 assertions in latest run |
| Queue table | Có (`jobs`) |
| Dedicated Jobs/Events/Policies directory | Chưa có |

## 2. Runtime và hạ tầng hiện tại

| Thành phần | Hiện trạng |
|---|---|
| Framework | CodeIgniter 4.7.x |
| PHP | 8.2+ |
| Database | MySQL 8 / MySQLi, database `pickball_db` |
| UI | Server-rendered PHP views, Bootstrap/ERP CSS/JS hiện hữu |
| API | REST, `/api/v1`, `/api/public/v1`, partner surface trong `/api/v1` |
| Authentication | Session admin/player, bearer/API key cho API |
| Authorization | RBAC permission filter + tenant filter + plan filter |
| Queue | Database-backed `jobs`, CLI commands cho rating/ranking/webhook |
| Session | CodeIgniter file session |
| Storage | Local `public/uploads` |
| Cache | Chưa có cache abstraction/invalidation layer riêng |
| Search | Chưa có search abstraction; đang dùng MySQL query |
| Observability | Application log và audit log; chưa có metrics/request tracing hoàn chỉnh |
| i18n | Có `vi`/`en`, nhưng domain vẫn còn text tiếng Việt trực tiếp |
| Timezone/currency | App timezone `Asia/Ho_Chi_Minh`; cần chuẩn hóa theo tenant/venue và currency snapshot |

## 3. Module inventory

| Domain | Thành phần hiện có | Đánh giá |
|---|---|---|
| Identity/Auth | `UserModel`, auth controller/filter, sessions, password reset, login attempts | Có, cần hoàn thiện organization membership và request context |
| Organization/Tenant | `TenantModel`, tenant plans/subscriptions/usage, branches, memberships | Có nền tảng; cần ranh giới organization/venue rõ hơn |
| Venue | `FacilityModel`, `BranchModel`, `CourtModel`, court type/status, hours, holidays, maintenance, devices | Có; quan hệ facility–branch–court vừa được hoàn thiện |
| Club | tenant `clubs`, `platform_clubs`, aliases, memberships, facility-club assignments | Có hai lớp đúng mục tiêu nhưng cần quy tắc projection rõ |
| Booking | booking/items/log/QR/waitlist/recurring/walk-in, pricing, payments | Có; cần E2E conflict/concurrency với production-like MySQL |
| CRM | `CustomerModel`/`CustomerService`, `customers`, timeline, tags, booking/invoice links, reviews, membership, wallet | Customer aggregate đã có; segmentation/automation và full Customer 360 vẫn cần mở rộng |
| Player Registry | `players`, `player_competitive_profiles`, claims, merge foundations, club memberships | Có; cần dừng tạo identity mới ngoài registry |
| National Player ID | passport service/profile và public identity fields | Có nền tảng; cần acceptance test immutable/public-safe |
| Tournament | tournament/category/rules/registration/group/bracket/match/sponsor/sanction/check-in | Có; cần hoàn thiện publication/pre-publish validation và lifecycle parity |
| Match | `matches`, participants, games, results, versions, tournament adapter | Có canonical target; `tournament_matches` vẫn là legacy/adapter projection |
| Rating | legacy `player_ratings`/`PlayerRatingModel`, canonical profile/transactions/ledger/provider/reliability/integrity | Đang tồn tại song song; đây là P0 integrity risk |
| Ranking | authorities/policies/point ledger/snapshots/network APIs | Có; cần season/country/discipline contract và trace UI đầy đủ |
| Governance | authorities, sanctions, decisions, appeals, disputes, correction/integrity foundations | Có nền tảng; cần kiểm chứng workflow end-to-end và authority boundary |
| Ruleset/Provenance | rulesets/versions, data provenance, policy versions | Có nền tảng; cần link bắt buộc tới mọi official result/ledger write |
| Commerce | invoices, payments, refunds, wallet, POS, tenant plans/subscriptions | Có; cần event finance/P&L và multi-currency snapshots |
| Integrations | webhooks, partner API keys, provider records, rating import workflow | Có nền tảng; thiếu generic connector consent/circuit breaker hoàn chỉnh |
| Notifications | notification models/service/templates và queue rows | Có; cần typed domain events/outbox |
| Print | print center/controller/service/views, badge/name tag/schedule/match sheet/bracket/certificate/book surface | Có; cần kiểm tra PDF batch queue và render acceptance |
| Public Portal | public home, players, ranking, tournaments, live/TV, public API | Có; cần privacy contract tests và cache invalidation |
| Analytics | operations dashboard/reports, quality/queue monitors | Có nền tảng; thiếu metrics/aggregation layer cho scale |
| SaaS Entitlements | plans, subscriptions, usage, plan filter | Có nền tảng; cần catalog/price/feature snapshot đầy đủ |

## 4. API và UI surface

Public routes hiện có home, ranking, players, tournaments, live score/TV và registration. API routes có internal `/api/v1`, public `/api/public/v1` và partner-auth surface. Admin routes bao gồm venue/court, booking, player, tournament, tournament control room, scheduler, print center, rating governance, data quality, governance, queue, livestream, webhook và integrations.

Các API quan trọng đã được xác định:

- `FacilityApi`, `CourtApi`, `BookingApi` cho vận hành venue/booking.
- `PlayerApi`, `PublicPortalApi`, `RatingApi`, `RankingApi` cho player/rating/ranking.
- `UnifiedMatchApi`, `MatchGovernanceApi`, `TournamentMatchNetworkApi` cho match/result/governance.
- `PartnerApiController`, `PlatformClubApi`, `FoundationApi` cho platform/partner.

OpenAPI hiện có tại `docs/api/openapi.yaml` và `docs/OPENAPI.md`, nhưng cần đưa validation contract vào CI.

## 5. Database và migration state

Database hiện có các nhóm bảng chính:

- Identity/tenant: `users`, `roles`, `permissions`, `tenants`, `branches`, `organization_memberships`.
- Venue/club: `facilities`, `courts`, `court_types`, `court_maintenance`, `clubs`, `platform_clubs`, `facility_club_assignments`.
- Booking/commerce: `bookings`, `booking_items`, `booking_waitlist`, `booking_recurring_templates`, `invoices`, `payments`, `refunds`, POS/wallet tables.
- Player: `players`, `player_competitive_profiles`, `player_identity_claims`, `player_club_memberships`, passport/merge/provider tables.
- Competition: `tournaments`, categories, registrations, groups, brackets, `tournament_matches`, check-ins, sanctions, sponsors, templates.
- Unified match/result: `matches`, `match_participants`, `match_games`, `match_results`, `match_result_versions`, `match_disputes`.
- Rating/ranking: providers, profiles, ledgers, transactions, reliability, policies, integrity flags, ranking authorities/policies/point ledgers/snapshots.
- Governance/provenance: authorities, decisions, appeals/evidence, sanctions/reviews, `data_provenance`, `data_provenance_records`.
- Integration/operations: `jobs`, notifications, webhooks, partner keys, imports, AI scheduling, livestream.

Migration mới nhất đã chạy trên development database:

- `2026-08-10-090000_CreateFacilityClubOperations`
- `2026-08-10-091000_CompleteFacilityCourtRelations`
- `2026-08-10-100000_CreateCustomerCrmFoundation`
- `2026-08-10-101000_BackfillCustomersFromBookings`
- `2026-08-10-110000_AddMatchIdentityReview`

Không có migration nào được phép xóa dữ liệu production. Các backfill phải idempotent, có mapping, kiểm tra duplicate và rollback strategy.

## 6. Duplicate/responsibility findings

| Khái niệm | Nguồn song song | Quyết định audit |
|---|---|---|
| Player rating | `player_ratings`, `player_statistics`, `player_rating_profiles`, `rating_ledgers`, `rating_transactions` | Canonical là rating profile + ledger/transaction; legacy chỉ là read adapter trong migration period |
| Tournament match | `tournament_matches` và `matches` | Canonical là `matches`; tournament table là adapter/source projection cho tới khi parity hoàn tất |
| Club | tenant `clubs` và network `platform_clubs` | Operational club tách network identity; alias/membership là bridge, không trộn payment/private data |
| Customer | `customers`/timeline/tags + booking snapshot fields | Customer aggregate đã có; snapshot vẫn giữ lịch sử, không coi `customer_name` là Player |
| Result | tournament score tables và `match_results`/versions | Official result phải là versioned result của unified match |
| Ranking | legacy player ranking fields và ranking ledger/snapshot | Read từ ledger/snapshot; exceptional adjustment phải là ledger |

## 7. Critical gaps

### P0 — dữ liệu và integrity

1. Customer aggregate đã có nhưng segmentation, automation, payment/tournament timeline và Customer 360 read model chưa hoàn chỉnh.
2. Legacy rating model vẫn tồn tại cạnh canonical rating engine; direct write mới đã bị chặn ở service chính nhưng còn cần telemetry/backfill audit.
3. Tournament-to-unified-match/result parity còn dữ liệu conflict cần governance xử lý; match mới đã có idempotency guard.
4. Official result correction phải chứng minh được lineage old version → new version → rating/ranking compensation.
5. Đã có critical-flow acceptance test venue → booking → Customer CRM → unified match → rating → ranking; vẫn thiếu full chain tournament → payment → public → print.

### P1 — vận hành và scale

1. Chưa có typed domain event/outbox; queue hiện có nhưng event dispatch chưa là abstraction chung.
2. Chưa có cache abstraction/invalidation và metrics/request correlation hoàn chỉnh.
3. Import pipeline có schema/workflow nhưng cần UI preview/match/approve và test file upload bảo mật.
4. Scheduling đã có engine/surface nhưng cần test referee conflict, dependency và published schedule delay.
5. Print center có nhiều service surface nhưng cần asynchronous batch job và PDF render acceptance.

### P2 — product maturity

1. Customer 360/CRM automation/segmentation và post-event timeline chưa là một module hoàn chỉnh.
2. Event finance/P&L và sponsor lifecycle cần hoàn chỉnh theo tournament.
3. SaaS catalog/price/entitlement snapshot, white-label và multi-currency còn ở mức foundation.
4. International location/timezone/currency và public privacy contract cần harden.

## 8. Test baseline

Lần chạy cuối của toàn bộ tests hiện tại: **174 tests, 545 assertions, OK**. Trong đó có acceptance flow thực chạy qua booking/CRM/match/rating/ranking; đây vẫn chưa phải E2E acceptance đầy đủ theo Master Prompt.

## 9. Kết luận audit

Repository đã là một modular monolith khá rộng, không nên tạo thêm module trùng. Ưu tiên tiếp theo phải là canonicalization và test invariants của Customer/Player, Match/Result, Rating/Ranking; sau đó mới mở rộng CRM, event/outbox, cache/metrics và E2E.
