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
| M2 | Common: Settings + Media + Audit + Notification Engine | ⬜ |
| M3 | Facility (cụm sân/chi nhánh/sân/giá) | ⬜ |
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

### M2 — Common: Settings + Media + Audit + Notification Engine
- Bảng `notifications`, `notification_templates` (vi/en, đa kênh email/in-app)
- Chuông thông báo in-app + gửi email (queue đơn giản qua bảng jobs)
- Media: upload + resize ảnh, chuẩn hóa storage

### M3 — Facility
- Hoàn thiện CRUD cụm sân/chi nhánh/sân/loại sân/giờ mở cửa/ngày nghỉ/bảo trì/thiết bị
- UI VN chuẩn 4 mẫu; lưới sân realtime (grid + timeline)

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
