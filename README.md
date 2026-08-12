# PICKLEBALL NATIONAL OS

## National Pickleball Operating System (Pickball Platform)

Một hệ sinh thái vận hành pickleball toàn diện cho:

- **Sân/CLB/Facility** (đặt sân, quản lý hoạt động nội bộ)
- **Giải đấu & Thi đấu chính quy** (đăng ký, lịch, bảng đấu, điểm)
- **Rating & Ranking quốc gia** (xếp hạng kỹ năng + xếp hạng thành tích)
- **CRM & Marketing** (khách hàng, chiến dịch, chất lượng dữ liệu)
- **POS & Tài chính** (thanh toán, hoá đơn, hàng tồn kho)
- **Cổng mở** (API công khai, API đối tác, API nội bộ)

Nền tảng sử dụng **CodeIgniter 4.7 + PHP 8.2 + MySQL 8.0**.

---

## 1) Kiến trúc tổng quan

- Kiểu triển khai: **SaaS đa tenant** (Tenant/Branch/Facility/Club/Role/Permission).
- Thiết kế theo hướng **module**: Public portal, Admin portal, Player portal, Referee portal, API.
- Đa ngôn ngữ: **vi/en** (mặc định `vi`).
- Giao tiếp vận hành:
  - Web route: `http://localhost:8080/...`
  - Public API: `/api/public/v1`
  - Partner API: `/api/partner/v1`
  - Admin/Internal API: `/api/v1`

---

## 2) Danh sách module đầy đủ

## 2.1 Quản trị nền tảng (Core Platform)

### 2.1.1 Nhân tố nền tảng
- **Multi-tenant**:
  - Tenant (trang) đăng nhập, chuyển context tenant.
  - Branch, Club, Facilities gắn theo tenant.
- **Bảo mật & phiên làm việc**:
  - Đăng nhập/Đăng xuất/Đặt lại mật khẩu.
  - Session filter, API auth filter, tenant filter.
  - Audit log cho hành động nhạy cảm.
- **Quyền hạn – RBAC**:
  - Role, Permission, Role-Permission mapping.
  - Policy-based filter trên route.
- **Cài đặt cấu hình hệ thống**:
  - Thiết lập thông số vận hành, thông số môi trường, menu, portal, quyền.
- **Dữ liệu toàn cục**:
  - Plan/SaaS entitlement theo tenant.
  - Trạng thái gói, quyền tính năng theo module.

### 2.1.2 Dịch vụ phụ trợ nền tảng
- **Language / i18n**: chuyển đổi `locale`.
- **Notification**: thông báo hệ thống, nhắc nhở, workflow.
- **Media**: quản lý hình ảnh/video.
- **Webhook**: tạo webhook, bật/tắt, theo dõi delivery.
- **Queue & Jobs**:
  - Theo dõi retry/dead-letter.
  - Deliver webhook, tái xử lý công việc nền.

## 2.2 Quản lý cơ sở vận hành sân (Venue Management)

### 2.2.1 Facility
- CRUD Facility.
- Dashboard Facility theo KPI vận hành.
- Liên kết Facility với các CLB.

### 2.2.2 Branch
- CRUD branch.
- Thời gian hoạt động mở cửa (opening hours).
- Tạo/xoá ngày lễ.
- Trang chi tiết theo branch.

### 2.2.3 Court
- Quản lý loại sân, trạng thái sân.
- Timeline sân theo ngày/khung giờ.
- Realtime trạng thái sân.
- Danh sách thiết bị sân + nhật ký thiết bị.
- Báo cáo sử dụng sân.

### 2.2.4 Quản lý dịch vụ sân liên quan
- Pricing rule động theo khung giờ/nhóm sân.
- Membership & gói dùng cho booking.
- Hỗ trợ điều phối open play / chờ / walk-in.

## 2.3 Quản lý đặt sân (Booking Engine)

### 2.3.1 Luồng đặt sân
- Tạo, sửa, hủy booking.
- Danh sách booking theo slot và trạng thái.
- Kiểm tra khả dụng theo slot/branch/court.
- Đặt lặp (recurring bookings).
- Danh sách tuần / availability theo ngày.

### 2.3.2 Trải nghiệm check-in
- Mã QR check-in.
- Gắn/mở/tắt trạng thái booking qua API/quy trình vận hành.

### 2.3.3 Waitlist & điều phối
- Hàng chờ booking.
- Drawer/preview reschedule.
- Kênh phân bổ tốc độ cao cho nhân viên tiền sảnh.

## 2.4 Tài chính, kho & POS

### 2.4.1 Đơn hàng & thanh toán
- Tạo hoá đơn đặt sân.
- Thanh toán tiền mặt, QR ngân hàng.
- Xác nhận thanh toán, hủy, hoàn tiền.
- Cài đặt cấu hình QR thanh toán.

### 2.4.2 POS
- Quầy bán hàng theo tenant.
- Tạo/sửa/xoá sản phẩm mua thêm.
- Tìm kiếm booking/khách hàng để gắn mua hàng.
- Điều phối kho:
  - Import hàng tồn
  - Kiểm kho
  - Lịch sử kho

### 2.4.3 Ví người dùng
- Nạp tiền.
- Lịch sử giao dịch ví.
- Điều chỉnh theo nghiệp vụ.

## 2.5 Tournament & Competition (Lõi hoạt động thi đấu)

### 2.5.1 Quản lý Giải đấu
- Danh sách giải.
- Tạo/sửa/xoá tournament.
- Template tournament và nhân bản từ template.
- Nhóm/phân loại giải theo lớp/đội/mục lục.

### 2.5.2 Đăng ký & điều kiện
- Registration theo nhóm cá nhân/đôi.
- Kiểm tra điều kiện tự động (eligibility).
- Duyệt hồ sơ/chờ, xác nhận, hủy, chuyển trạng thái.
- Quản lý đội/đăng ký đội kép và partner.

### 2.5.3 Lịch & bảng đấu
- Tạo/mở khóa phiên lịch.
- Xếp lịch tự động.
- Chỉnh tay lịch/sắp xếp lại trận theo nhóm.
- Khóa lịch cho từng vòng.
- Giao diện control room điều phối match live.

### 2.5.4 Bảng thắng lợi / Draw & Bracket
- Tạo phiên bản draw.
- Export bảng đấu.
- Chạy lại draw theo yêu cầu (tạo phiên bản mới).

### 2.5.5 Kết quả trận
- Nhập điểm trận tại referee workflow.
- Kết quả chính thức / pending.
- Import/correction/dispute.
- Hỗ trợ kiểm định lịch sử kết quả.

### 2.5.6 Trận đấu công khai
- Bracket public.
- Trận đang diễn ra / live score.
- Đăng ký tham gia open play, ladder challenge.

## 2.6 Rating Engine (Xếp hạng năng lực / Skill Engine)

### 2.6.1 Hồ sơ rating
- `player_rating_profiles` và bản tóm tắt theo discipline:
  - đơn, đôi, mix, tổng quan.
- `rating_transaction`, phiên bản chính sách rating.
- Lịch sử rating theo phiên bản chính sách.

### 2.6.2 Nhập liệu & đồng bộ
- Nhập kết quả rating.
- Claim rating profile từ cầu thủ.
- Đề xuất/kiểm định identity cho claim.
- Chuyển đổi/legacy backfill (giữ lại lịch sử cũ khi nạp seed).

### 2.6.3 Điều chỉnh & minh bạch
- Manual adjustment có quyền.
- Lưu lý do chỉnh sửa, audit log.
- Chỉ rõ người phê duyệt/phản hồi.

### 2.6.4 Chống thao túng
- Integrity flags.
- Bài toán độ tin cậy/độ đa dạng đối thủ (opponent/partner diversity).
- Dispute & repair workflow.

## 2.7 Ranking Network / National Ranking

### 2.7.1 Cấu trúc ranking
- Ranking authority (federation/authority).
- Policy version & điểm.
- Snapshot ranking định kỳ.

### 2.7.2 Danh sách xếp hạng
- Leaderboard national/public.
- Lọc theo category, discipline, theo mùa.
- API leaderboard và portal ranking.
- Dữ liệu ranking history/biểu đồ (sẵn sàng mở rộng theo phiên bản chính sách).

### 2.7.3 Tính điểm
- Tính điểm theo bảng chính sách.
- Gắn sự kiện giải đấu vào điểm theo mức công nhận.

## 2.8 Player Identity & Passport

### 2.8.1 Hồ sơ công khai VĐV
- Passport profile:
  - thông tin công khai
  - liên kết CLB/tỉnh thành viên
  - lịch sử đối thủ
- Bảo vệ quyền riêng tư theo mức.

### 2.8.2 Danh tính & xác thực
- Nhận diện bằng:
  - national player ID
  - identity claims
  - external claim id
- Fuzzy match hỗ trợ đề xuất trùng lặp (khuyến nghị duyệt thủ công).
- Merge audit cho trường hợp hợp nhất hồ sơ.

### 2.8.3 Hồ sơ cầu thủ đầy đủ
- Theo dõi wallet, membership, lịch sử trận, lịch sử booking, điểm thưởng.

## 2.9 CRM & Marketing

### 2.9.1 Khách hàng
- Danh mục khách hàng riêng (khác player registry).
- Gán tag, ghi chú, phân nhóm chất lượng.

### 2.9.2 Chiến dịch
- Tạo chiến dịch truyền thông.
- Theo dõi launch/hiệu quả.

### 2.9.3 Quy trình vận hành front desk
- Quản lý đặt nhanh theo khách.
- Review, feedback, phản hồi cộng đồng.

## 2.10 Mạng xã hội, cộng đồng & tăng trưởng

### 2.10.1 Social & Community
- Follow/unfollow.
- Post cộng đồng, comment, reaction.
- Bảng đánh giá, review.

### 2.10.2 Team & Coaching
- Nhóm đội:
  - tạo đội, mời/tham gia/tháo gỡ thành viên.
- Coaching session:
  - danh sách lớp/chương trình
  - đăng ký tham gia
  - thanh toán đơn vị coaching.

### 2.10.3 Growth
- Referral, review, promotion data.
- KPI tương tác theo cầu thủ.

## 2.11 Livestream & truyền thông sự kiện

### 2.11.1 Livestream quản lý
- Danh sách kênh.
- Mở/tắt stream theo giải/trận.
- Liên kết tournament match.

## 2.12 Kênh người làm trọng tài (Referee)
- Dashboard chấm điểm:
  - bắt đầu trận
  - cập nhật lượt
  - kết thúc & đẩy kết quả.

## 2.13 API & tích hợp đối tác

### 2.13.1 Public API
- Portal dữ liệu công khai:
  - home, search, players, ranking, countries
  - verify player card, lịch sử rating công khai.

### 2.13.2 Partner API
- API key + scope:
  - players
  - rankings
  - clubs
  - tournaments.

### 2.13.3 Internal API
- Auth/token + apiratelimit.
- Booking, court, users, settings.
- Tournament schedule.
- Player service, competition, community, coaching.
- Livestream, governance, corrections, identity, ranking & rating endpoints.

## 2.14 Quản lý vận hành chất lượng dữ liệu
- Data quality dashboard.
- Governance module: dispute, correction, correction request, sanction, appeals.
- Audit log trình tự phê duyệt.
- Integrity flags cho match và rating.

## 2.15 Bảo trì & giám sát
- Daily closing.
- Operations report.
- Audit log và lịch sử thao tác.
- Queue monitor.
- Notification center, hệ thống nhắc việc.

---

## 3) Cấu trúc thư mục chính

```text
app/
  Controllers/               # Public, Admin, Player, Referee, Api
  Models/                    # Domain models theo bảng nghiệp vụ
  Services/                  # Dịch vụ nghiệp vụ (booking, rating, ranking, tournament...)
  Filters/                   # Auth, tenant, permission, rate-limit, partner auth
  Commands/                  # rating:rebuild, ranking:rebuild, webhook deliver
  Helpers/                   # Utils
  Entities/                  # Entities domain-specific
  Libraries/                 # Tích hợp đặc thù
  Database/
    Migrations/              # Migration theo module
    Seeds/                   # Seeder module-level
    SQL/                     # File SQL nội bộ
  Views/                     # Template web cho từng portal
  Language/                  # vi, en
  Config/                    # AppConfig, routes, filters...
docs/                        # Tài liệu kiến trúc/kiểm toán/đặc tả API
public/                      # Asset, js, css, ảnh
tests/                       # Unit + Feature
```

---

## 4) Cài đặt nhanh (Local)

```bash
git clone git@github.com:nhuanctu-cmd/pb.git
cd pb
composer install
cp env .env    # hoặc dùng file env tùy môi trường
```

Trong `.env`, cấu hình tối thiểu:

- `app.baseURL`
- `database.default.hostname`
- `database.default.database` (mặc định: `pickball_db`)
- `database.default.username`
- `database.default.password`
- `CI_ENVIRONMENT` (Production/Staging/Development)

Chạy DB và seed:

```bash
php spark migrate --all
php spark db:seed CoreSeeder
php spark db:seed RbacSeeder
php spark db:seed RolePermissionSeeder
php spark db:seed TenantSeeder
php spark db:seed TenantPlanSeeder
php spark db:seed SettingSeeder
php spark db:seed CanonicalRatingDemoSeeder
php spark db:seed TournamentDemoIntegritySeeder
php spark db:seed TournamentBracketSampleSeeder
php spark db:seed CommercialDemoSeeder
php spark db:seed DemoDataSeeder
```

Khởi chạy:

```bash
php spark serve --port 8080
```

Mở các URL:

- `http://localhost:8080` (Home)
- `http://localhost:8080/admin` (Admin dashboard)
- `http://localhost:8080/player` (Player portal)
- `http://localhost:8080/tournaments` (Public tournament)
- `http://localhost:8080/locale/switch/en` để chuyển giao diện.

---

## 5) Dump dữ liệu và khôi phục

Repo có mẫu dump tại `backups/`.

Khôi phục:

```bash
mysql -u root -h 127.0.0.1 -P 3306 pickball_db < backups/<file>.sql
```

---

## 6) Kiểm thử

Các nhóm kiểm thử đang có:

- **Feature tests**: luồng admin, player, tournament, booking, api.
- **Unit tests**: service/engine/rating/reputation/gov.
- **Workflow tests**: integration cho governance/dispute/import/legacy.
- **Command tests**: rebuild ranking/rating.

Chạy:

```bash
php spark test
composer test
```

---

## 7) Tiêu chuẩn vận hành đề xuất

- Ưu tiên dữ liệu công khai trước khi render UI: ưu tiên dùng nguồn `player_rating_profiles`, `ranking_snapshot`, `tournament_*` thay vì dữ liệu legacy.
- Mọi thay đổi điều chỉnh rating/ranking phải có:
  - actor
  - reason (bắt buộc)
  - audit log liên quan.
- Mọi tranh chấp (dispute), correction, integrity flag phải đi qua workflow phê duyệt.
- Mọi import/seed cần đi kèm preview + log rollback.

---

## 8) Gợi ý lộ trình tiếp theo (nâng cấp)

1. Hoàn thiện CRM menu cấp 2 thật sự cho:
   - Facilities/Branches/Tenants/Users/Teams/POS/Payments với đầy đủ CRUD + actions.
2. Đóng gói đầy đủ **module con** cho:
   - Rating governance (approve/reject, integrity, audit).
   - Tournament registration + eligibility gate.
3. Chuẩn hoá API hợp đồng + OpenAPI kiểm tra CI.
4. Nâng bộ dữ liệu mô phỏng theo từng module (facility, tournament, rating, ranking, CRM).
5. Bật dashboard biểu đồ lịch sử rating/ranking theo phiên bản.
6. Bổ sung bộ test nghiệp vụ còn thiếu:
   - doubles, correction, disputed match, duplicate import, legacy backfill.

---

## 9) Đóng góp

1. Tạo branch feature.
2. Phân tích module bị tác động.
3. Chạy test liên quan trước khi merge.
4. Ghi chú thay đổi trong docs tương ứng (docs/*.md).

---

Repo liên kết: https://github.com/nhuanctu-cmd/pb
