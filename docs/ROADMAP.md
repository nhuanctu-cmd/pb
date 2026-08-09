# 🗺️ ROADMAP PHÁT TRIỂN — PICKLEBALL SYSTEM

> **Phiên bản:** 1.0.0 · **Cập nhật:** 09/08/2026
> **Stack:** CodeIgniter 4.7 + PHP 8.2 + MySQL 8.0 + Bootstrap 5.3
> **Nguyên tắc:** Code tuần tự từng module · Chuẩn phần mềm thương mại · UI tiếng Việt có dấu · Dễ xài

---

## 1. Bộ tiêu chuẩn áp dụng cho mọi module

### 1.1 Chuẩn code thương mại
- Kiến trúc 4 tầng: **Routes → Controller (mỏng) → Service (business logic + transaction) → Model/Entity**
- Mọi nghiệp vụ ghi nhiều bảng bọc `transStart()/transComplete()`
- Chống double-submit (idempotency), chống race condition booking (row locking)
- Validation tập trung tại Service/Model, thông báo lỗi tiếng Việt qua `lang()`
- Mọi route admin: filter `auth` + `permission:xxx.yyy`; dữ liệu lọc theo `tenant_id`
- Audit log cho mọi thao tác tạo/sửa/xóa/tiền
- Soft delete dữ liệu nghiệp vụ; **không dùng GET để xóa** (POST + CSRF)
- Mỗi module có seeder demo + feature test luồng chính

### 1.2 Chuẩn giao diện tiếng Việt
- 100% chuỗi hiển thị qua `lang()` — cấm hardcode trong view (kể cả sidebar)
- File lang `vi` là gốc, `en` dịch theo; thuật ngữ theo `docs/GLOSSARY.md`
- Format: ngày `d/m/Y` · giờ `H:i` · tiền `1.500.000 ₫` · timezone `Asia/Ho_Chi_Minh`
- Email/thông báo/lỗi hệ thống: tiếng Việt có dấu

### 1.3 Chuẩn UX dễ xài
- 4 mẫu màn hình thống nhất: **Danh sách / Form / Chi tiết / Dashboard**
- Form: label rõ + placeholder gợi ý + validate inline tiếng Việt + nút có loading
- Thao tác nguy hiểm: modal xác nhận ("Bạn có chắc muốn hủy đặt sân này?")
- Danh sách: empty state có hướng dẫn, phân trang, lọc, tìm kiếm
- Toast kết quả sau mọi thao tác; responsive mobile
- Menu theo quyền user; không badge/menu rác

### 1.4 Quy trình 8 bước mỗi module
1. Schema/migration chuẩn
2. Entity + Model (validation VN)
3. Service (transaction-safe)
4. Controller mỏng + API
5. Views tiếng Việt (4 mẫu màn hình)
6. Routes + permission + menu `lang()`
7. Seeder demo + feature test
8. Smoke test + cập nhật docs

---

## 2. Tiến độ tổng thể

| # | Module | Trạng thái |
|---|--------|-----------|
| M0 | Hạ tầng chạy (env/migrate/seed/git) | ✅ **HOÀN THÀNH** (09/08/2026) |
| M1 | Core: Tenant + Auth + RBAC + SaaS Plan | ✅ **HOÀN THÀNH** (09/08/2026) |
| M2 | Common: Settings + Media + Audit + Notification Engine | ✅ **HOÀN THÀNH** (09/08/2026) |
| M3 | Facility (cụm sân/chi nhánh/sân/giá) | ✅ **HOÀN THÀNH** (09/08/2026) |
| M4 | Booking hoàn chỉnh | ⬜ |
| M5 | Payment & Ví | ⬜ |
| M6 | Membership | ⬜ |
| M7 | Player Portal & Social | ⬜ |
| M8 | Tournament & Live Score | ⬜ |
| M9 | POS & Kho | ⬜ |
| M10 | Báo cáo & Dashboard | ⬜ |
| M11 | API & Tích hợp | ⬜ |
| M12 | Community + AI + Livestream | ⬜ |

---

## 3. Chi tiết từng module

### M0 — Hạ tầng chạy ✅
- [x] `.env` (development, baseURL, DB, timezone) + encryption key
- [x] Sửa 2 migration lỗi (POS/Payment: `addPrimaryKey`, `addUniqueKey`)
- [x] Chạy 8 migrations → 67 bảng
- [x] Sửa `SettingSeeder` (lệch cột), `PosSeeder` (`insertGetId`)
- [x] Seed: CoreSeeder, PlayerMembershipSeeder, PosSeeder, DynamicPricingSeeder, BookingSeeder, DemoDataSeeder
- [x] Server `php spark serve --port 8080`; smoke test: web login ✓, admin pages ✓, API ✓
- [x] Sửa `indexPage=''` (URL sạch), locale mặc định `vi`, sửa API login đọc JSON body
- [x] git init + commit baseline

**Tài khoản truy cập:**
| Vai trò | Email | Mật khẩu |
|---|---|---|
| Super Admin | `admin@pickleball.com` | `admin123` |
| Chủ sân (tenant 1) | `owner@pickleballpro.com` | `password` |
| Quản lý chi nhánh | `manager@pickleballpro.com` | `password` |
| Nhân viên | `staff@pickleballpro.com` | `password` |
| Demo Admin | `admin@demo-pickleball.vn` | `admin123` |

### M1 — Core: Tenant + Auth + RBAC + SaaS Plan ✅
- Quên/đặt lại mật khẩu (email), `login_attempts` (chống brute-force), `password_histories`, `user_sessions`
- **RBAC chuẩn**: 68 permissions theo 22 module (ví dụ `bookings.create`, `pos.access`, `plans.view`) + 6 vai trò system (`super-admin/owner/branch-manager/staff/referee/player`)
- Phân quyền đã đồng bộ: super-admin 68, owner 60, branch-manager 28, staff 14, referee 5, player 0
- Helper `can()` + `canAny()` + menu sidebar tự động lọc theo quyền; route admin toàn bộ có filter `permission:module.action`
- **Tài khoản demo đầy đủ**: admin, owner, manager, staff, referee, player
- **SaaS hoàn chỉnh**: `PlanFilter` chặn POS/giải đấu nếu gói không có feature; trang `admin/plans` hiển thị gói hiện tại/usage/đăng ký; giới hạn tạo sân/ngườị chơi theo gói
- Áp `TenantFilter` + `PermissionFilter` lên toàn bộ route admin; bỏ menu UI Demo

### M2 — Common: Settings + Media + Audit + Notification Engine ✅
- Bảng `notifications`, `notification_templates` (vi/en, đa kênh email/in-app), `jobs` (queue đơn giản)
- Notification Engine: `NotificationService` (in-app + email queue + render biến `{{var}}`), chuông thông báo trên topbar + API unread-count/mark-read, trang trung tâm thông báo
- Settings: trang cấu hình theo nhóm (general/booking/payment/notifications/business), tenant override → global default qua `SettingService`
- Audit logs: lọc theo module/action/user/khoảng ngày + phân trang 50 dòng
- Media library: upload ảnh tự resize ≤1600px + thumbnail 300px, lọc theo loại, copy URL, soft delete; storage `public/uploads/YYYY/MM/`
- 19 test mới (Notification/Setting/Media/Job) → tổng 76 tests, 238 assertions OK

### M3 — Facility ✅
- **Branch CRUD hoàn chỉnh**: danh sách (kèm số sân), form tạo/sửa, xóa mềm (chặn xóa khi còn sân), tự seed 7 ngày giờ mở cửa mặc định khi tạo
- **Giờ mở cửa theo tuần**: trang chỉnh 7 ngày (giờ mở/đóng/nghỉ), upsert theo day_of_week
- **Ngày nghỉ/lễ**: thêm/xóa ngày nghỉ theo chi nhánh
- **Facility**: CRUD cụm sân + dashboard tổng quan (đã có sẵn, localize tiếng Việt)
- **Courts**: lưới sân + timeline + bảo trì + ảnh (đã có sẵn)
- 10 test mới (Branch CRUD/giờ mở cửa/chặn xóa) → tổng 86 tests, 255 assertions OK

### M4 — Booking ⭐
- Chống trùng lịch (transaction + row lock), QR check-in, reschedule
- Waitlist, coupon/giảm giá, recurring booking, rating/review, blacklist
- Auto-hủy booking chưa thanh toán sau X phút (cron)

### M5 — Payment & Ví
- QR VietQR + sandbox VNPay/MoMo, webhook + idempotency
- Hóa đơn PDF, refund, sổ cái ví đầy đủ

### M6 — Membership
- Quyền lợi gói + usage tracking, tự động hết hạn, referral

### M7 — Player Portal & Social
- Hồ sơ/ví/xếp hạng ELO/huy hiệu; team/club/kèo mở; mobile-first

### M8 — Tournament & Live Score
- Bracket, auto-schedule, trọng tài nhập điểm, trang TV realtime (SSE/polling)

### M9 — POS & Kho
- Bán hàng tại quầy, ca làm việc/két tiền, in bill, nhập/xuất/kiểm kho

### M10 — Báo cáo & Dashboard
- Doanh thu, occupancy, giờ cao điểm, xuất Excel/PDF

### M11 — API & Tích hợp
- JWT chuẩn (refresh rotation), OpenAPI docs, rate limit, webhook, Zalo OA

### M12 — Community + AI + Livestream
- Bài đăng/nhóm/sự kiện; AI scheduling (Python OR-Tools); livestream sân

---

## 4. Ghi chú vận hành
- Chạy dev: `php spark serve --port 8080` → http://localhost:8080
- Chạy lại từ đầu: drop DB → `php spark migrate --all` → `php spark db:seed CoreSeeder` (+ các seeder demo)
- 6 migration cũ nằm trong thư mục con `app/Database/Migrations/2026-*/` — schema gốc đã apply qua `app/Database/SQL/create_all_tables.sql` (sẽ chuẩn hóa ở M1)

---

## 5. Roadmap theo cụm chức năng nghiệp vụ

Roadmap này dùng để chia việc theo giá trị vận hành. Một cụm chỉ được xem là hoàn thành khi có đủ migration, Model/Entity, Service, Controller/API, view, permission/menu, seeder và test luồng chính.

### Cụm A — Nền tảng và phân quyền

**Mục tiêu:** Một tài khoản đăng nhập an toàn, làm việc đúng tenant/chi nhánh và chỉ thấy đúng menu/chức năng.

**Đã có:** Tenant, Auth, RBAC, session tracking, password reset, SaaS plan, tenant switch, audit log cơ bản.

**Nên hoàn thiện tiếp:**

- Tenant membership: một user có thể thuộc nhiều tenant/branch.
- Tenant context filter cho mọi thao tác đọc/ghi nghiệp vụ.
- Phân quyền tách `view/create/update/delete/approve/refund/checkin`.
- CSRF cho toàn bộ form POST và đổi các thao tác xóa từ GET sang POST.
- Security header, rate limit API, log request ID.

**Phụ thuộc:** không có. Đây là cụm nền bắt buộc trước các nghiệp vụ tiền và booking.

### Cụm B — Cấu hình cơ sở kinh doanh

**Mục tiêu:** Chủ sân tạo được mô hình `Cụm sân → Chi nhánh → Sân` và vận hành được lịch mở cửa.

**Chức năng:**

- Facility/branch/court CRUD.
- Loại sân, indoor/outdoor, mặt sân, tiện ích, hình ảnh.
- Giờ mở cửa theo thứ, ngày lễ/ngày nghỉ.
- Trạng thái sân: hoạt động, đang chơi, giữ chỗ, bảo trì, khóa, đóng cửa.
- Lịch bảo trì, block sân, thiết bị sân và session vận hành.
- Grid/timeline/calendar theo từng sân.

**Nên hoàn thiện tiếp:** kiểm tra tenant khi sửa/xóa theo ID, form validation inline, không cho xóa sân có dữ liệu lịch sử, menu active đúng route.

**Phụ thuộc:** Cụm A.

### Cụm C — Booking và Availability Core ⭐

**Mục tiêu:** Đặt sân chính xác, không double-booking và có thể kiểm soát hold/payment.

**Chức năng:**

- Tìm sân trống theo chi nhánh/ngày/giờ/thời lượng.
- Availability tính từ lịch mở cửa, ngày nghỉ, booking, hold, bảo trì, block và event.
- Tạo booking nhiều sân, cập nhật giá snapshot.
- Hold 5 phút, tự hết hạn, giải phóng item.
- State machine: `draft → hold → pending_payment → reserved/paid → checked_in → completed`; nhánh `expired/cancelled/refunded/no_show`.
- Reschedule, cancel theo chính sách, no-show.
- QR booking và QR check-in dùng một lần.
- Booking admin, player portal và API dùng chung Service.

**Đã nâng cấp bước đầu:** transaction + row lock ở court, hold timeout và expired release.

**Cần viết test bắt buộc:** overlap, concurrent booking, hold expiry, reschedule conflict, cross-tenant access, QR replay, cancel/refund.

**Phụ thuộc:** Cụm A + B.

### Cụm D — Giá, thanh toán và tài chính

**Mục tiêu:** Giá và tiền minh bạch, truy vết được, không mất giao dịch.

**Chức năng:**

- Base price, peak/off-peak, cuối tuần, ngày lễ, giá theo loại sân.
- Dynamic pricing và price breakdown snapshot tại booking.
- Deposit/full payment/split payment.
- Invoice, payment, refund, VietQR/bank transfer.
- Payment provider abstraction cho VNPay/MoMo/Stripe về sau.
- Webhook signature, idempotency key, duplicate callback protection.
- Wallet ledger: top-up, payment, refund, credit, adjustment.

**Nguyên tắc:** Không update trực tiếp số dư ví; mọi thay đổi phải có ledger transaction.

**Phụ thuộc:** Cụm C; dùng lại `PricingService`, `PaymentService`, invoice/payment/refund models hiện có.

### Cụm E — Khách hàng, Player và Membership

**Mục tiêu:** Quản lý khách và tăng tỷ lệ quay lại sân.

**Chức năng:**

- Player profile, level/rating, lịch sử chơi, tỷ lệ hủy/no-show.
- Membership package, quyền lợi, booking window, discount, free hours.
- Gia hạn/hết hạn membership và usage tracking.
- Check-in khách, ghi chú chăm sóc, blacklist theo policy.
- Review sân sau booking hoàn tất.

**Phụ thuộc:** Cụm A + C + D.

### Cụm F — Vận hành tại quầy và doanh thu

**Mục tiêu:** Nhân viên có một màn hình để xử lý vận hành hằng ngày.

**Chức năng:**

- Walk-in booking cho user hoặc guest.
- Staff check-in, scan QR, đổi sân, block sân nhanh.
- POS bán nước, bóng, vợt, khăn, merchandise.
- Kho, nhập/xuất/kiểm kê, ca làm việc và két tiền.
- Dashboard hôm nay: booking, sân đang chơi, hold sắp hết hạn, doanh thu, no-show.

**Phụ thuộc:** Cụm B + C + D.

### Cụm G — Open Play và cộng đồng Pickleball

**Mục tiêu:** Người chơi mua slot tham gia và tìm người chơi phù hợp.

**Chức năng:**

- Open Play session, capacity, level range, price per player.
- Join game, request/approve player, public/private/club-only.
- Waitlist và tự động mời người tiếp theo khi có chỗ.
- Matchmaking deterministic theo level, lịch, địa điểm và lịch sử partner/opponent.
- Rotation engine, partner/opponent history, waiting time.
- Club, team, follow/favorite, invitation link.

**Phụ thuộc:** Cụm E + C + D.

### Cụm H — Coach và Clinic

**Mục tiêu:** Bán dịch vụ huấn luyện với availability của cả coach và court.

**Chức năng:**

- Coach profile, chứng chỉ, chuyên môn, giá giờ.
- Coach availability, blackout và lịch nghỉ.
- Private 1:1, semi-private, group lesson, clinic.
- Capacity, waitlist, payment, attendance, review.

**Phụ thuộc:** Cụm B + C + D + E.

### Cụm I — Giải đấu và thi đấu

**Mục tiêu:** Tổ chức giải từ đăng ký đến kết quả và bảng xếp hạng.

**Đã có:** Tournament, registration, bracket, group, scheduler, referee scoring, live score.

**Nên mở rộng:**

- Round robin, ladder, league và standings.
- Check-in đội/người chơi, seed, đổi lịch có kiểm soát.
- Tách kết quả thi đấu khỏi trạng thái booking nhưng liên kết được court/time.
- Audit thay đổi score/result và quyền referee.

**Phụ thuộc:** Cụm B + E; thanh toán giải dùng Cụm D.

### Cụm J — Thông báo, tài liệu và tích hợp

**Mục tiêu:** Người dùng luôn biết trạng thái booking/payment và admin có dữ liệu kết nối.

**Đã có:** Notification template vi/en, in-app bell, job queue đơn giản, media library.

**Nên hoàn thiện:**

- Booking confirmation/reminder/cancel/payment/waitlist notification.
- Queue worker và retry/dead-letter rõ ràng.
- Email/SMS/Push adapter.
- Media permission theo tenant/branch, file type/size policy.
- Webhook outbound cho đối tác.

**Phụ thuộc:** Cụm A; tích hợp sâu theo từng cụm nghiệp vụ.

### Cụm K — Báo cáo và dữ liệu quản trị

**Mục tiêu:** Chủ sân ra quyết định dựa trên occupancy, doanh thu và hành vi khách.

**Chức năng:**

- Occupancy theo sân/giờ/ngày/chi nhánh.
- Doanh thu booking/payment/POS/membership.
- Peak hour, cancellation, no-show, repeat customer.
- Court utilization ranking và forecast nhu cầu cơ bản.
- Export CSV/Excel/PDF theo quyền.

**Phụ thuộc:** Cụm B + C + D + F + E.

### Cụm L — API, Mobile và mở rộng

**Mục tiêu:** Dùng chung nghiệp vụ cho web, PWA, mobile và đối tác.

**Chức năng:**

- API versioning `/api/v1`.
- Auth token/refresh rotation, scope permission, rate limit.
- API availability, booking, payment status, player profile, notification.
- OpenAPI document và contract test.
- Polling trước; chỉ thêm realtime/WebSocket khi có nhu cầu vận hành rõ ràng.

**Phụ thuộc:** Các cụm lõi đã ổn định và có feature test.

## 6. Thứ tự triển khai đề xuất

```text
A Nền tảng/RBAC
        ↓
B Facility/Court/Schedule
        ↓
C Booking/Availability/Hold/QR
        ↓
D Pricing/Payment/Wallet
        ↓
E Player/Membership/Review
        ↓
F Walk-in/POS/Kho/Owner Dashboard
        ↓
G Open Play/Waitlist/Matchmaking
        ↓
H Coach/Clinic
        ↓
I Tournament/League/Ladder
        ↓
J Notification/Integration nâng cao
        ↓
K Reporting/Analytics
        ↓
L API/Mobile/Realtime mở rộng
```

## 7. Mốc nghiệm thu thương mại

### MVP vận hành một cụm sân

Hoàn tất A → F: chủ sân tạo sân, cấu hình lịch/giá, nhận booking, thu tiền, check-in, bán hàng và xem doanh thu.

### Nền tảng Pickleball có cộng đồng

Hoàn tất A → G: người chơi có profile, membership, open play, join game, waitlist và matchmaking.

### Nền tảng SaaS mở rộng

Hoàn tất A → L: nhiều tenant/chi nhánh, API mobile, tích hợp thanh toán/thông báo, báo cáo và realtime có kiểm soát.

## 8. Quy tắc chọn việc tiếp theo

1. Ưu tiên lỗi có thể làm sai tiền hoặc double-booking.
2. Sau đó hoàn thiện màn hình vận hành hằng ngày.
3. Mỗi cụm phải có test và menu trước khi chuyển cụm.
4. Không mở Open Play/AI khi Booking Core chưa ổn định.
5. Không thêm realtime nếu polling đáp ứng được vận hành hiện tại.

## 9. Trạng thái hardening đã hoàn tất — Booking Core

Đợt nâng cấp thương mại đầu tiên đã hoàn tất ở Cụm C:

- Tenant isolation cho booking list/detail/cancel/reschedule/check-in và availability.
- Booking state machine dùng chung cho web, player portal và API.
- Transaction + row-lock cho create, payment, reschedule, completion, expiry và QR claim.
- Chống double-book trong cùng request; snapshot giá và audit log tiếp tục nằm trong transaction.
- API bearer token ký HMAC bằng encryption key; token bị sửa sẽ bị từ chối.
- 86 test / 255 assertion đạt; test state machine, tamper token và branch regression đã được bổ sung.

Việc tiếp theo theo thứ tự rủi ro: Payment Ledger + idempotency key, refund policy, rồi mới mở rộng waitlist/recurring booking.
