# National Pickleball Ranking Portal — Audit & Delivery Notes

Ngày: 09/08/2026

## 1. Existing frontend audit

- Project dùng CodeIgniter 4.7 + PHP 8.2, kiến trúc MVC với service/model layer.
- `app/Views/welcome_message.php` vẫn là trang mặc định CodeIgniter.
- `app/Controllers/Home.php` hiện redirect người dùng sang admin hoặc login; chưa có public homepage.
- Public surface hiện có tournament listing/detail/register và live scores.
- CSS admin hiện có là ERP/admin-oriented, vì vậy portal dùng stylesheet riêng để không kéo sidebar/topbar quản trị vào public page.

## 2. Existing public routes and APIs

- Đã có: `/`, `/tournaments`, `/tournaments/{slug}`, `/live-scores`, `/live-scores/tv`.
- Đã có API: `/api/v1/live-scores`, `/api/v1/rankings/leaderboard`, tournament/club APIs nội bộ.
- Chưa có aggregator `/api/public/v1/home` và public search API.

## 3. Reuse / missing components

Tái sử dụng: `RankingNetworkService`, `RatingNetworkService`, `LiveScoreService`, `PlayerModel`, `TournamentModel`, `ClubModel`, `PlatformClubModel` và các bảng ledger/snapshot hiện có.

Bổ sung trong slice này: `PublicPortalService`, public home API/search API, portal homepage view, responsive public stylesheet, semantic ranking/search/live/tournament/club/result sections.

Các trang profile/ranking detail/verify đầy đủ được giữ route-ready và sẽ là phase tiếp theo; homepage chỉ link tới public destinations đã có hoặc anchor rõ ràng.

## 4. Homepage information architecture

Header → hero search → quick stats → national ranking → movers/live → upcoming tournaments → rating vs ranking → province/club discovery → official results → verification/methodology → footer.

## 5. Wireframe

```text
┌────────────────────────────────────────────────────┐
│ NP  BXH  VĐV  GIẢI ĐẤU  LỊCH  CLB  LIVE  TÌM KIẾM │
├────────────────────────────────────────────────────┤
│ BẢNG XẾP HẠNG PICKLEBALL TOÀN QUỐC                 │
│ Theo dõi thứ hạng, Rating, kết quả và giải đấu     │
│ [ Tên VĐV hoặc National Player ID ] [ TRA CỨU ]    │
├────────────────────────────────────────────────────┤
│ VĐV | CLB | GIẢI ĐẤU | TRẬN ĐẤU | TỈNH/THÀNH       │
├────────────────────────────────────────────────────┤
│ BẢNG XẾP HẠNG TOÀN QUỐC     │ TĂNG HẠNG NỔI BẬT   │
│ #1 … #10                       │ LIVE NOW           │
├────────────────────────────────────────────────────┤
│ GIẢI ĐẤU SẮP DIỄN RA                               │
│ BXH TỈNH/THÀNH                │ TOP CLB            │
├────────────────────────────────────────────────────┤
│ KẾT QUẢ CHÍNH THỨC · XÁC MINH · PHƯƠNG PHÁP        │
└────────────────────────────────────────────────────┘
```

## 6. API / data / cache requirements

- `GET /api/public/v1/home`: stats, top rankings, movers, live events, upcoming events, featured players, top clubs, latest official results.
- `GET /api/public/v1/search?q=`: grouped public Players/Clubs/Tournaments; không trả phone, email, birthday.
- Service đọc snapshot/ledger đã có; không tính ranking trong Blade/controller.
- Cache production nên tách theo module: stats 10 phút, leaderboard 1–5 phút, upcoming 5 phút, live TTL ngắn/polling fallback.
- Khi thiếu bảng/dữ liệu hoặc API lỗi, từng module trả empty/error state độc lập.

## 7. File change list

- `app/Controllers/Home.php`, `app/Controllers/Public/PublicPortal.php`, `app/Controllers/Api/PublicPortalApi.php`
- `app/Services/PublicPortalService.php`, `app/Config/Services.php`, `app/Config/Routes.php`
- `app/Views/public/home.php`, `public/assets/css/public-portal.css`, `public/assets/js/public-portal.js`
