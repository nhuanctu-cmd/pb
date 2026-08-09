# Current System Audit — Pickleball System

Ngày audit: 2026-08-09

## 1. Kết luận công nghệ

Project hiện tại là **CodeIgniter 4.7.3**, PHP 8.2+, MySQL/Mysqli và PHPUnit 10. Không chuyển sang Laravel. Kiến trúc hiện tại là MVC + Service Layer + Model/Entity, dùng migration/seeder của CodeIgniter.

## 2. Những gì đã có

### Core và vận hành

- Tenant, branch, user, role, permission, setting, audit log, media.
- Facility → branch → court, loại sân, trạng thái sân, lịch mở cửa, ngày nghỉ, bảo trì, thiết bị và session.
- Booking, booking items, QR booking, booking log, recurring template, status động.
- Pricing rules, conditions, dynamic price log và pricing test.
- Invoice, payment, refund, bank QR, POS, inventory và wallet.

### Người chơi và cộng đồng

- Player CRM, level, rating, statistics, achievements, badges, membership và membership package.
- Club, team, team member, match request, social match và matching service.
- Player portal gồm dashboard, booking, profile, wallet, membership, team và match.

### Giải đấu và nền tảng

- Tournament, registration, category, rule, sponsor, bracket, group, match, scoring và scheduler.
- SaaS plan, subscription, usage, auth security, password reset, session tracking.
- Notification center, notification templates, job queue đơn giản và media library.

## 3. Bản đồ file chính

| Lớp | Hiện trạng |
|---|---|
| Routes | `app/Config/Routes.php`, đã có public/API/admin/player/referee |
| Controllers | `app/Controllers/Admin`, `Api`, `Player`, `Public`, `Referee` |
| Services | `app/Services`, booking/pricing/payment/facility/player/tournament là các lõi có thể tái sử dụng |
| Models | `app/Models`, bám theo các bảng migration hiện có |
| Views | `app/Views/layouts`, `admin`, `player`, `public`, `referee` |
| Database | Các migration module đã migrate đến `2026-08-09-270000` trên môi trường phát triển |
| Tests | 124 tests, 342 assertions cho auth, RBAC, booking, payment, social, coaching, competition, growth, community và vận hành |

## 4. Reuse map

- Booking mới phải dùng `BookingService`, `BookingModel`, `BookingItemModel`, `PricingService`, `BookingQrCodeModel` và `BookingLogModel`.
- Availability phải mở rộng `BookingService::checkCourtAvailable()` và facility/court schedule, không tạo engine trùng.
- Multi-tenant phải dùng `current_tenant_id()`, `TenantFilter`, `PermissionFilter`, `PermissionService` và các cột `tenant_id` hiện có.
- Payment phải dùng `PaymentService`, `InvoiceModel`, `PaymentModel`, `RefundModel`.
- Người chơi phải dùng `PlayerService`, `PlayerModel`, membership/wallet/rating hiện có.
- Notification phải dùng `NotificationService`, notification models và job queue hiện có.
- Menu dùng `app/Views/layouts/partials/sidebar.php`, đã có lọc theo permission; chỉ bổ sung các route/module đã tồn tại.

## 5. Gap analysis theo ưu tiên

### P0 — cần xử lý trong Booking Core

- Cần tiếp tục bổ sung kiểm thử concurrency thực tế trên production-like MySQL và chuẩn hóa timezone theo tenant/branch; các flow lõi hiện đã có transaction, row-lock, state machine, idempotency, tenant boundary và test hồi quy.

### P1 — club operations

- Report vận hành sâu ở POS/membership vẫn có thể mở rộng; operational dashboard, booking report, recurring occurrence, waitlist, walk-in, coaching/competition KPI và invoice aging snapshot đã có domain/service/admin flow nền tảng.
- Notification center đã có route/RBAC và menu; tiếp tục seed template theo tenant khi triển khai production.

### P2 — social/coaching/growth

- Coach/Clinic, Competition và Growth đã có domain vận hành nền tảng; event/corporate và weather interface chưa có domain hoàn chỉnh.
- Competition đã có round-robin/league/ladder, payment coaching, check-in, ladder promotion, notification và API mobile; competition entry payment riêng có thể mở rộng khi có pricing policy cho event.

## 6. Kiến trúc triển khai tuần tự

1. **Phase 0 — Audit và ổn định nền:** sửa lỗi syntax/test nền, hoàn thiện menu theo route hiện có, bổ sung tài liệu.
2. **Phase 1 — Booking Core:** availability an toàn, hold expiry, booking state transition, tenant isolation, pricing/payment snapshot, QR/check-in và tests.
3. **Phase 2 — Club Operations:** recurring, waitlist, walk-in, maintenance conflict, staff dashboard, report.
4. **Phase 3 — Pickleball Social:** open play, join game, rating/matchmaking/rotation.
5. **Phase 4 — Coaching:** coach availability, lesson, clinic.
6. **Phase 5 — Competition:** round robin, ladder, league, mở rộng tournament hiện có.
7. **Phase 6 — Growth:** voucher, referral, wallet mở rộng, review, analytics và recommendation.

## 7. Nguyên tắc thay đổi

- Reuse → extend → refactor → create new.
- Không đổi framework, không xóa migration cũ, không tạo bảng trùng nghĩa.
- Không tin `tenant_id` gửi từ client; lấy context từ session/authenticated user.
- Booking lịch sử giữ snapshot giá/chính sách; không tính lại theo bảng giá hiện tại.
- Mọi thay đổi schema phải là migration mới và có rollback.

## 8. Rủi ro hiện tại

- Worktree đang có nhiều thay đổi trước đó của project; các file không liên quan phải được giữ nguyên.
- Test database là snapshot riêng (`pickball_test`) và một số test fixture chưa đồng bộ với foreign key hiện tại.
- Một số migration cũ chèn dữ liệu mẫu tenant `1`, vì vậy seed/migration production cần được kiểm tra trước khi chạy trên database mới.

## 9. Cập nhật hardening 2026-08-09

- API và admin Facility/Court/Branch/Device/Setting đã kiểm tra tenant ở chuỗi quan hệ trước khi đọc hoặc ghi.
- Queue reserve đã atomic bằng transaction + row lock; notification và dashboard/report giữ tenant context.
- Tournament scheduler, scoring, live scores, public booking/check-in và social matching đã bổ sung tenant boundary.
- Recurring booking đã có engine template/occurrence, row-lock xử lý due, route/menu quản trị và test lịch lặp.
- Waitlist đã có migration, tenant-scoped idempotency, notify/expire/cancel, claim booking trong transaction và route/menu quản trị.
- Walk-in đã có migration phiên riêng, idempotency, booking liên kết, check-in/checkout/cancel và route/menu quản trị.
- Operational Dashboard đã có KPI tenant-scoped cho booking, doanh thu, lịch sắp diễn ra, trạng thái sân, walk-in và waitlist.
- Booking report đã có lọc ngày/chi nhánh và xuất CSV tenant-scoped.
- Conflict bảo trì đã chuẩn hóa overlap, bao gồm maintenance không có thời điểm kết thúc và boundary không chồng lấn.
- Open Play đã có migration/session player, capacity, request/waitlist, approve/leave, player-facing route/menu và test state thuần.
- Matching request đã khóa player khi confirm, re-check conflict trong transaction, tenant-safe detail/cancel và audit.
- Rotation Engine đã có round persistence, partner/opponent history và waiting-time helper.
- Social Graph đã có follow/unfollow và favorite polymorphic có tenant boundary, route/menu player và audit.
- Coach/Clinic đã có migration, tenant boundary, coach availability/blackout, session giữ sân qua BookingService, capacity/waitlist, approve/cancel, player flow và menu admin/player.
- Competition đã có migration riêng, format round-robin/league/ladder, participant team/player, fixture generator deterministic, row-lock ghi kết quả, standings và player route/menu.
- Growth đã có promotion quote/redeem idempotent tích hợp BookingService, referral qualify/reward qua wallet ledger, review moderation, tenant validation và menu admin/player.
- Player booking form đã nhận promotion code và lưu discount/net amount theo booking snapshot.
- Coaching và Competition đã có attendance/check-in persistence, admin controls, row-lock cập nhật và audit.
- Operations Dashboard đã bổ sung KPI Coach–Clinic và Competition theo ngày/tenant bên cạnh booking, walk-in và waitlist.
- POS negative stock đọc từ setting tenant; migration đã chạy ở môi trường phát triển; full test hiện đạt 120 tests / 331 assertions.
- Ladder challenge đã có migration, challenge window tối đa 2 bậc, accept/reject theo participant, result fixture, standings/promotion, audit và notification.
- Coaching approval đã tạo invoice theo entry; player wallet payment dùng `PaymentService`, khóa payer/invoice theo tenant và idempotency key.
- API mobile đã mở coaching/competition surface dưới `apiauth`; dashboard/report có invoice billed/collected/outstanding.
- API `/api/*` đã có rate-limit filter 120 request/phút theo client identity; contract mobile được ghi tại `docs/OPENAPI.md`.
- Community đã có post/comment/reaction, tenant-scoped feed, player menu, API mobile, transaction và audit.
- Full test hiện đạt 124 tests / 342 assertions; migration đã chạy đến `2026-08-09-270000`.
