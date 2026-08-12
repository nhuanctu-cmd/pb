# Master Prompt Gap Analysis

Ngày: 2026-08-09  
Quy ước: **YES** = có implementation và evidence đủ dùng; **PARTIAL** = có foundation nhưng chưa đạt acceptance; **NO** = chưa có canonical implementation hoặc chưa có test chứng minh.

## 50 câu hỏi final audit

| # | Câu hỏi | Status | Gap / impact | Recommended implementation / priority |
|---:|---|---|---|---|
| 1 | Có một Player Registry duy nhất? | PARTIAL | `players` và passport/profile còn là operational projection + canonical cạnh nhau; legacy reads còn tồn tại | Chốt passport làm canonical, tenant player là projection; P0 |
| 2 | Có National Player ID? | YES | Passport/public ID foundation đã có | Bổ sung immutable/public contract test; P1 |
| 3 | Customer và Player tách đúng? | PARTIAL | Customer aggregate/timeline đã có; segmentation, automation và mọi history source chưa hợp nhất | Hoàn thiện Customer 360 và mapping Player–Customer; P1 |
| 4 | Một Match chỉ có canonical record? | PARTIAL | `matches` và `tournament_matches` cùng tồn tại; đã ghi nhận 3 identity conflicts legacy | Governance resolve conflict, tiếp tục enforce adapter/idempotency; P0 |
| 5 | Booking có thể tạo Match nhưng không bắt buộc? | PARTIAL | Unified match source hỗ trợ booking nhưng E2E chưa chứng minh | Thêm integration test và explicit source adapter; P1 |
| 6 | Tournament dùng Unified Match Graph? | PARTIAL | Có `unified_match_id` và network adapter, nhưng parity chưa hoàn chỉnh | Migrate tournament writes về match service; P0 |
| 7 | Result có version? | YES | `match_results` và `match_result_versions` đã có | Enforce official immutable + correction test; P0 |
| 8 | Rating có ledger? | YES | Canonical profiles/transactions/ledgers/provider/policy đã có | Chặn legacy write và deterministic rebuild; P0 |
| 9 | Ranking có ledger? | YES | Ranking point ledger và snapshots đã có | Bổ sung season/country/discipline invariants; P1 |
| 10 | Có Governance? | YES | Authorities, decisions, sanctions, disputes, integrity foundations có | Kiểm tra authority/object-level authorization; P1 |
| 11 | Có Appeals? | YES | Appeal model/evidence và governance routes có | Hoàn thiện state machine + final decision test; P1 |
| 12 | Có Provenance? | PARTIAL | Schema/service có, nhưng chưa bắt buộc trên mọi write quan trọng | Add invariant trước official/rating/ranking commit; P0 |
| 13 | Có Ruleset Versioning? | YES | Ruleset/version và policy versions đã có | Enforce historical immutability; P1 |
| 14 | Có External Rating Provider abstraction? | YES | Provider/adapter/link/import surface đã có | Thêm consent, circuit breaker, retry contract; P1 |
| 15 | Có chống duplicate Player? | PARTIAL | Passport/identity claim foundation có, confidence workflow chưa đầy đủ | Exact + fuzzy match preview/approval; P0 |
| 16 | Có chống duplicate Match? | PARTIAL | Idempotency fields/services có nhưng chưa có invariant toàn graph | Unique source key + duplicate review; P0 |
| 17 | Có Auto Scheduling? | YES | Scheduler/AI scheduling service và API có | Test dependency/rest/optimization; P1 |
| 18 | Có Player Conflict Detection? | YES | Scheduler conflict surface có | Add referee/player matrix E2E; P1 |
| 19 | Có Court Conflict Detection? | YES | Availability/scheduler/court checks có | Add published schedule invariant; P1 |
| 20 | Có Referee Conflict Detection? | PARTIAL | Referee UI/score surface có, nhưng conflict contract chưa đủ evidence | Normalize referee assignment và conflict query; P1 |
| 21 | Có Tournament Control Room? | YES | Admin control room routes/views/service có | Add one-screen operations acceptance; P1 |
| 22 | Có Check-in? | YES | Tournament check-in tables/service/routes có | QR/name/player ID test; P1 |
| 23 | Có Live Score? | YES | Score/log/live display/API/TV có | Verify public realtime/cache behavior; P1 |
| 24 | Có Public Tournament? | YES | Public list/detail/register/live routes có | Privacy DTO contract tests; P1 |
| 25 | Có TV Mode? | YES | Public TV/live view có | Validate full-screen court board polling; P2 |
| 26 | Có Print Center? | YES | Admin print center/service surface có | Queue all batch output; P1 |
| 27 | Có Badge? | YES | Badge generator surface có | PDF/QR render test; P2 |
| 28 | Có Name Tag? | YES | Name tag generator surface có | Template/render test; P2 |
| 29 | Có Match Sheet? | YES | Match card/sheet surface có | Validate player/score/referee DTO; P2 |
| 30 | Có Bracket Print? | YES | Bracket print surface có | A4/A3/A2 landscape visual test; P2 |
| 31 | Có Certificate? | YES | Certificate generator surface có | Winner/runner-up/third templates; P2 |
| 32 | Có Tournament Book? | PARTIAL | Print service has book direction, batch composition not fully verified | Async composition job + PDF acceptance; P2 |
| 33 | Có CRM Customer 360? | PARTIAL | Có CustomerModel/aggregate/timeline/tags và booking backfill; thiếu payment/tournament/automation 360 read model | Hoàn thiện CRM projections + segmentation; P1 |
| 34 | Có CRM Segmentation? | PARTIAL | Một số membership/analytics signals có, chưa có dynamic segment engine | Segment definitions + materialized membership; P2 |
| 35 | Có Post-event CRM? | PARTIAL | Notification/booking/player history có nhưng event timeline chưa unified | Domain event → CRM timeline; P1 |
| 36 | Có Event Finance? | PARTIAL | Payment/sponsor/registration data có, chưa có tournament P&L aggregate | Event finance ledger/report; P1 |
| 37 | Có Sponsor? | YES | Tournament sponsor model/API/UI có | Link sponsor output tới public/print/TV; P2 |
| 38 | Có SaaS Entitlement? | PARTIAL | Plans/subscriptions/usage/plan filter có, feature catalog snapshot chưa đầy đủ | Product/price/feature/entitlement registry; P1 |
| 39 | Có Multi-tenant isolation? | PARTIAL | Filters/services/query scopes khá rộng, cần invariant audit toàn surface | Tenant boundary tests và deny-by-default; P0 |
| 40 | Có RBAC? | YES | Roles/permissions/filters/routes có | Permission matrix + object authorization audit; P1 |
| 41 | Có Audit Log? | YES | `audit_logs`, service và nhiều mutation audit có | Bắt buộc request/job/tenant context; P1 |
| 42 | Có Import/Export? | PARTIAL | Rating import workflow và một số CSV export có | Generic batch import/export + secure upload; P1 |
| 43 | Có API? | YES | Internal/public/partner API surfaces có | OpenAPI validation/contract CI; P1 |
| 44 | Có Queue? | YES | DB `jobs`, rating/ranking/webhook commands/jobs có | Typed queues, retry/dead-letter metrics; P1 |
| 45 | Có Cache? | NO | Chưa có cache abstraction/invalidation strategy thực thi | Cache provider + public ranking/tournament invalidation; P1 |
| 46 | Có i18n? | PARTIAL | `vi`/`en` language files có, domain text và locale/currency còn hard-code | BCP-47/timezone/currency normalization; P1 |
| 47 | Có migration strategy? | YES | Migration/backfill/seed strategy và non-destructive rules có | Add rollback/runbook per migration; P1 |
| 48 | Có automated tests? | YES | PHPUnit suite hiện đạt 174 tests/545 assertions | Expand critical contract/integration coverage; P1 |
| 49 | Có end-to-end tests? | PARTIAL | Đã có critical flow venue→booking→Customer CRM→unified match→rating→ranking; chưa xuyên tournament→payment→public→print | Build staged full E2E acceptance; P0 |
| 50 | Có documentation? | YES | Architecture, rating, ranking, governance, API và audit docs có; audit này bổ sung current-state/source matrix | Maintain docs in change control; P1 |

## Critical gap register

### GAP-001 — Customer 360 và CRM projections

- **Impact:** Customer aggregate đã giải quyết tách Customer/Player và booking history, nhưng chưa đủ payment/tournament/membership timeline để làm automation/segmentation tin cậy.
- **Dependency:** User/tenant context, booking/payment/membership history, player registry, domain events.
- **Priority:** P1.
- **Implementation:** Bổ sung event projections, segment definitions/materialization và Customer 360 read model; giữ snapshot lịch sử bất biến.

### GAP-002 — Canonical match/result invariant

- **Impact:** Tournament result có thể lệch khỏi unified graph, gây sai rating/ranking và correction lineage.
- **Dependency:** `UnifiedMatchService`, tournament adapter, result version/governance.
- **Priority:** P0.
- **Implementation:** Unique `(tenant_id, source_type, source_id)`, idempotent create, adapter read/write contract, correction compensation tests.

### GAP-003 — Legacy rating write isolation

- **Impact:** Legacy và canonical rating có thể diverge, làm public rating không giải thích được.
- **Dependency:** Existing legacy columns, canonical profiles/transactions, rebuild command.
- **Priority:** P0.
- **Implementation:** Backfill ledger seed, canonical read adapter, telemetry on legacy writes, then reject new legacy writes.

### GAP-004 — Full acceptance E2E

- **Impact:** Critical flow đã chứng minh được một phần lineage; vẫn chưa chứng minh được full domain chain và public/private boundary.
- **Dependency:** Customer, canonical match, payment, tournament operations, print jobs, CRM timeline.
- **Priority:** P0.
- **Implementation:** Mở rộng staged MySQL integration fixture từ critical flow hiện tại tới tournament/payment/public/print, sau đó thêm correction/failure paths.

### GAP-005 — Event/outbox, cache, metrics

- **Impact:** Eventual consistency, public invalidation, retries và observability không có contract chung.
- **Dependency:** Existing jobs/webhooks/notifications.
- **Priority:** P1.
- **Implementation:** Typed outbox/event registry, cache provider abstraction, request/job correlation and metrics sink.

## Acceptance decision

**Chưa đạt COMPLETE theo Master Prompt.** Nền tảng đã đủ rộng để tiếp tục mở rộng an toàn, nhưng các GAP-001 đến GAP-004 phải hoàn thiện trước khi tuyên bố production-complete cấp quốc gia.
