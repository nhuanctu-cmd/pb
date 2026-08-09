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
| Database | 16 migration/module, đã migrate đến `2026-08-09-140000` |
| Tests | 76 tests hiện có cho auth, RBAC, plan, setting, media, notification, job |

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

- `BookingService` đã có transaction nhưng availability check diễn ra trước transaction/row lock; cần bảo vệ lần kiểm tra cuối trong transaction.
- Hold hiện đang biểu diễn qua booking pending và các cột `hold_until/is_hold`, nhưng chưa có flow state machine đầy đủ, idempotency và worker giải phóng hold rõ ràng.
- Một số thao tác service tìm booking theo ID nhưng chưa luôn ràng buộc tenant context ở tầng service.
- Chưa có test đầy đủ cho concurrent booking, cross-tenant access, hold expiry, payment idempotency và QR replay.
- Cần chuẩn hóa date/time theo timezone tenant/branch thay vì dùng trực tiếp timezone process ở toàn bộ flow.

### P1 — club operations

- Walk-in, waitlist, recurring occurrence thực tế, staff operation dashboard và report vận hành còn thiếu hoặc chưa hoàn chỉnh.
- Menu đã có nhiều module, nhưng notification center chưa được đưa vào sidebar chính; một số menu đang trỏ tới route tổng quát chưa có trạng thái active chính xác.

### P2 — social/coaching/growth

- Open Play rotation, coach/clinic, event/corporate, voucher/promotion, referral, favorite/follow, review và weather interface chưa có domain hoàn chỉnh.
- Competition đã có tournament/scheduler; league/ladder/round-robin độc lập chưa hoàn thiện.

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
