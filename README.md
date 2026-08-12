# PICKLEBALL NATIONAL OS  
**National Pickleball Operating System (Pickball Platform v2)**  
Platform booking + tournament + rating + ranking + CRM + marketplace + partner API, built on **CodeIgniter 4.7**.

## Bản chất dự án

Repository này là hệ sinh thái vận hành pickleball toàn diện cho:
- Hệ thống sân/CLB (booking, lịch sân, thiết bị, POS, membership)
- Điều phối giải đấu (lập lịch, bảng đấu, kiểm soát trận, live score)
- Động cơ xếp hạng/đánh giá tay nghề (rating engine + integrity + governance)
- Ranking quốc gia theo cầu thủ/từng mùa giải/chuỗi thi đấu
- CRM cho quản lý khách hàng/chương trình marketing
- Hệ thống API/đối tác mở (public, partner, internal)

## Tính năng hiện có (đã triển khai)

### 1) Hệ điều hành nền tảng
- Multi-tenant: Tenant/Facility/Branch/Club/Role/Permission/RBAC
- Authentication, Session, Token-based API authentication
- Hệ thống settings, menu, dashboard, report, audit trail, notifications
- Mở rộng theo module, có command, service, filter riêng

### 2) Quản lý cơ sở & đặt sân
- Quản lý cơ sở (Facilities): thông tin, dashboard, chi nhánh, câu lạc bộ liên kết
- Quản lý sân (Courts): trạng thái sân theo thời gian thực, thiết bị, timeline
- Đặt sân (Bookings): tạo/cập nhật/hủy, check-in QR, booking drawer/recurring/điều phối
- Open play / chờ / walk-in / đội ngũ tiền sảnh
- Pricing rules, membership packages, coupon/plan tích hợp theo nhu cầu vận hành

### 3) Thanh toán & vận hành nội bộ
- POS, inventory/invoice, ví người dùng, lịch sử giao dịch
- Daily closing & reconciliation
- Job/queue monitor, dead-letter, retry job
- Integrations webhook + vận hành hệ thống qua admin ops

### 4) Tournament & thi đấu
- Tournament engine: tạo/lập lịch/quản lý danh sách/điều phối lịch
- Tournament registration, eligibility gate, tournament templates, bracket/sân đấu
- Điều phối match, điều khiển live score, control room
- Kết nối result network cho luồng trận tử trận chuẩn hóa (unified match)

### 5) Rating, Ranking & Integrity
- Profile rating chuẩn hóa + player rating profile
- Rating history, transaction ledger, policy, adjustment, reliability
- Import/repair/correction workflow
- Governance review: tranh chấp, integrity flag, dispute/appeal handling
- Ranking snapshot/leaderboard (public + admin) và nền tảng mở rộng cho cấp tổ chức

### 6) Quản trị vận hành & CRM
- Customer management, campaign, data quality, front desk, media
- Operations report, growth dashboard, governance/sanction, competitor/audit logic
- Livestream channel + control pages
- Notification & message flow theo workflow nghiệp vụ

### 7) APIs
- API Public v1 (`/api/public/v1`)
- API Partner v1 (`/api/partner/v1`) với OAuth/key + quyền theo scope
- Internal API (`/api/v1`) cho nền tảng nội bộ
- OpenAPI spec: `docs/api/openapi.yaml`

## Cấu trúc thư mục quan trọng

```text
app/
  Controllers/      # Web + Player + Public + Referee + Admin + Api
  Models/           # Domain models
  Services/         # Domain services
  Filters/          # Auth, RBAC, rate limit, partner auth...
  Commands/         # CLI commands (rating rebuild, webhook deliver...)
  Database/
    Migrations/
    Seeds/
  Views/
docs/               # Tài liệu thiết kế & vận hành
public/             # CSS/JS assets, images, static
tests/              # PHPUnit feature + unit tests
```

## Yêu cầu môi trường

- PHP >= 8.2
- MySQL >= 8.0
- Composer
- Apache/Nginx hoặc Laragon
- Extension chuẩn PHP: intl, mbstring, json, curl, mysqlnd

## Khởi chạy nhanh (Local)

```bash
git clone git@github.com:nhuanctu-cmd/pb.git
cd pb
composer install
cp env .env    # hoặc dùng file env riêng của bạn
```

Trong `.env`, cấu hình:
- `app.baseURL`
- `database.default.hostname`
- `database.default.database` (mặc định: `pickball_db`)
- `database.default.username`
- `database.default.password`

Chạy migrate + seed:

```bash
php spark migrate --all
php spark db:seed RbacSeeder
php spark db:seed RolePermissionSeeder
php spark db:seed TenantSeeder
php spark db:seed SettingSeeder
php spark db:seed CanonicalRatingDemoSeeder
php spark db:seed CommercialDemoSeeder
```

Khởi chạy app:

```bash
php spark serve --port 8080
```

Mở: `http://localhost:8080`

## Chuẩn bị môi trường Production (gợi ý)
- Tắt bootstrap tự động trong `App\Config\App` nếu đang bật cho local auto-bootstrap
- Dùng `.env` riêng cho prod
- Chạy migrate/seed theo quy trình deploy
- Tắt debug và log thông tin nhạy cảm
- Cấu hình quyền thư mục `writable/`, cache, session, queue worker

## Gọi nhanh API
- Admin portal: `/admin`
- Public portal: `/` , `/tournaments`, `/players`, `/rankings`
- Public API docs endpoint: xem `docs/api/openapi.yaml`

## Scripts / Commands hữu ích

```bash
php spark migrate --all                       # Chạy migration
php spark migrate:status                      # Kiểm tra migration state
php spark db:seed DemoDataSeeder              # Seed dữ liệu demo
php spark test                               # Chạy toàn bộ test
composer test                                # Chạy PHPUnit qua script composer
```

## Kiểm thử

Repo có đầy đủ:
- Feature tests cho luồng admin/public
- Unit tests cho service, rating engine, webhook, operations
- Test integrity và workflow edge cases

## Database dump & backup

Trong dự án đã có script dump mẫu tại thư mục `backups/` (được sinh bởi `mysqldump` theo `pickball_db`).

Import lại:

```bash
mysql -u root -h 127.0.0.1 -P 3306 pickball_db < backups/<file>.sql
```

## Roadmap

- Hoàn thiện public portal chi tiết theo sân/tournament/đội
- Nâng cấp dashboard biểu đồ rating history + governance timeline
- Mở rộng webhook provider + monitoring hiệu năng theo tenant
- Tiếp tục harden API contract theo OpenAPI

## Pháp lý & giấy phép

Dự án kế thừa khung CodeIgniter, giữ đầy đủ thông tin bản quyền của framework.  
Ứng dụng nghiệp vụ của bạn tiếp tục được quản lý tại repo này theo quy ước team.

## Đóng góp

1. Tạo nhánh feature
2. Thực hiện thay đổi
3. Chạy test
4. Tạo PR

---

Repo link: https://github.com/nhuanctu-cmd/pb
