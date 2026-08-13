# Nhật ký triển khai vận hành thương mại

Ngày cập nhật: 13/08/2026

Tất cả module dưới đây chạy bằng PHP/CodeIgniter và CSS/JS tĩnh hiện có; không cần build front-end khi chạy Laragon.

| Mã | Module | Điểm vào để kiểm thử | Luồng đã bật |
| --- | --- | --- | --- |
| M1 | Venue Control Room | `/admin/venue-operations` | trạng thái sân, trận/booking kế tiếp, trễ giờ, check-in và deep-link vận hành |
| M2 | TV / LED Mode | `/live-scores/tv`, `/admin/tournaments/control-room` | LIVE, NEXT, CALL PLAYER, kết quả và polling dữ liệu |
| M3 | Print Center | `/admin/print-center` | chọn giải, lọc/phân trang, preview và in tài liệu giải |
| M4 | Tournament Template | `/admin/tournament-templates` | snapshot, clone giải, đổi ngày mở đăng ký, wizard mở nhanh + preset ngày + 1-click cho 5 module core |
| M5 | Front Desk | `/admin/front-desk` | incoming/current/late, hold, release, check-in, no-show, complete |
| M6 | Owner Dashboard | `/admin/owner-dashboard` | KPI ngày/MTD/YTD, công nợ, chênh lệch chốt ca, khách top |
| M7 | Daily Closing | `/admin/daily-closing` | kiểm tra, khóa ca, mở lại có quyền, CSV, bản in/PDF |
| M8 | Membership Renewal | `/admin/memberships/renewals` | ưu tiên hết hạn, gia hạn, lịch sử, reminder test/bulk |
| M9 | CRM Campaign | `/admin/crm-campaigns` | biến mẫu, test send, throttle, retry, dispatch và dashboard |
| M10 | Club Membership | `/admin/memberships`, `/admin/clubs` | hồ sơ hội viên, trạng thái, lịch sử và API membership |
| M11 | Facility Management | `/admin/facilities` | CRUD, archive an toàn, dashboard, nhánh và CLB liên kết |
| M12 | Branch & Court Operations | `/admin/branches`, `/admin/courts` | giờ mở cửa, ngày nghỉ, bảo trì, lịch/grid/timeline sân |
| M13 | Club Management | `/admin/clubs`, `/clubs` | quản trị CLB, trang public, thành viên, bài viết, lịch sử |
| M14 | Facility–Club Partnership | `/admin/facilities/clubs/{facilityId}` | mapping nhiều-nhiều, tỷ lệ/chính sách và audit |
| M15 | Public / API / Security | `/venues`, `/clubs`, `/api/v1/facilities`, `/api/v1/clubs` | public deep-link, API phân trang/scope, API auth và rate-limit |

## Kiểm tra trước khi đưa lên môi trường thật

1. Chạy `php spark migrate` trên database đích.
2. Mở `/admin/runbook` và đi lần lượt Front Desk → Renewal → Daily Closing → CRM → Owner Dashboard.
3. Kiểm tra role nhân viên, quản lý, chủ sân và super admin không truy cập chéo tenant/branch.
4. Kiểm tra URL TV trên màn hình độc lập và in thử từ Print Center.

## Tương thích database cũ

Booking/Front Desk tự nhận diện các cột hold tùy chọn (`auto_release_at`, `hold_until`, `is_hold`). Vì vậy database legacy không có các cột này vẫn mở được màn hình, tự giải phóng hold theo dữ liệu còn hỗ trợ, và không phát sinh lỗi SQL cột không tồn tại.
