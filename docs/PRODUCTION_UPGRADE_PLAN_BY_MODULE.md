# KẾ HOẠCH NÂNG CẤP THƯƠNG MẠI (15 MODULE)  
**Phiên bản:** 0.1 · **Ngày cập nhật:** 13/08/2026  
**Vai trò:** Kiến trúc sư phần mềm + Chuyên gia UX/UI cho vận hành thực tế  

Mục tiêu: chuyển hệ thống hiện tại thành nền tảng “dùng hằng ngày như phần mềm thật”, ưu tiên **ổn định, dễ dùng, dễ kiểm soát tài chính – vận hành – marketing**.

## 1) Tiêu chí chất lượng (áp dụng cho toàn bộ 15 module)

- **Thương mại đúng nghĩa**
  - Mọi thao tác nghiệp vụ có `tenant_id` + `branch_id` scope.
  - Phân quyền theo quyền tác vụ (view/create/update/delete/approve), không dựa vào giao diện.
  - Audit log đầy đủ: ai làm gì, khi nào, trước/sau khi thay đổi.
  - Giao dịch DB (transaction) cho luồng ảnh hưởng tiền, trạng thái, tồn kho.
- **Dễ dùng**
  - UI thống nhất 4 mẫu chính: Dashboard / Danh sách + lọc / Form / Chi tiết + lịch sử.
  - Mọi danh sách phải có:
    - tìm kiếm nhanh,
    - bộ lọc nhanh theo trạng thái/ngày/chi nhánh,
    - phân trang + thứ tự mặc định có nghĩa,
    - empty state + hành động gợi ý.
- **UX/UI hiện đại**
  - Cái nhìn “ops-ready”: card KPI, timeline trạng thái, badge cảnh báo, micro-interaction có feedback tức thời.
  - Hỗ trợ `loading`, `skeleton`, `empty`, `error state`, `toast` theo chuẩn.
  - Tối thiểu hóa cú nhấp: 1-3 click cho nghiệp vụ nóng (check-in, mở hold, khóa ca).
- **Độ tin cậy**
  - Idempotency cho action nhạy (gửi sms, khóa ca, tạo hóa đơn, phát hàng loạt).
  - Retry cho công việc async (webhook, gửi campaign).
  - Tránh lỗi “hủy/trùng dữ liệu” qua khóa hàng và ràng buộc trạng thái.
- **Hiệu năng**
  - API danh sách có pagination + index đúng (tenant, ngày, branch, status).
  - Danh sách lớn ưu tiên query nhẹ, tách N+1, cache read-only.

## 2) Chuẩn triển khai cho mỗi module

Với mỗi module khi code:

1. Phân tích nghiệp vụ + thiết kế luồng state.
2. Xác nhận model/schema/API cần bổ sung (migrations khi cần).
3. Service layer và transaction.
4. Controller mỏng, route rõ quyền.
5. View theo 4 mẫu + link deep giữa dữ liệu liên quan.
6. Seed dữ liệu production-like.
7. Smoke test + test kịch bản nghiệp vụ chính + test phân quyền.
8. Chạy bằng checklist “go-live” riêng (trước khi merge).

## 3) Kế hoạch nâng cấp chi tiết từng module (15 module)

> Ưu tiên sắp xếp theo giá trị vận hành, khả năng tạo doanh thu hằng ngày.

### M1 — Venue Control Room (`/admin/venue-operations`)
- **Mục tiêu thương mại:** Bảng điều phối toàn sân theo ngày, vận hành nhanh, không phải tra thủ công.
- **Cần nâng cấp**
  - Backend:
    - Service tổng hợp: sân đang đánh, trống, sắp tới, trễ giờ, check-in chưa xong.
    - Tính toán trạng thái theo thời gian thực dựa trên booking timeline + hold + no-show.
    - API cho TV/dashboard/mobile pull.
  - Frontend:
    - Real-time polling 10–15 giây, badge color theo ưu tiên (màu đỏ: trễ, vàng: cần xử lý).
    - Drilldown từ sân → booking → player → lịch sử của vận động viên.
    - Layout split: cột trái trạng thái sân, cột phải sự kiện.
  - UX:
    - Chỉ 2 action chính: `CALL PLAYER`, `MARK IN`.
    - Mẹo cảnh báo âm thanh nhẹ khi có sự kiện khẩn.
- **Kiểm thử**
  - Trường hợp sân trùng giờ, hold hết hạn, booking no-show.
  - Khả năng filter theo branch và tìm nhanh theo mã booking.

### M2 — TV / LED Mode (`/admin/tournaments/control-room`, `/live-scores/tv`)
- **Mục tiêu thương mại:** Một URL hiển thị tự động chu kỳ cho quầy/chuyển động địa điểm.
- **Cần nâng cấp**
  - Backend:
    - Board payload API chuẩn hóa: `LIVE`, `NEXT`, `CALL PLAYER`, `RESULT`.
    - Cơ chế đổi slide theo thời gian + trigger theo trạng thái booking.
    - Hỗ trợ nguồn URL cấu hình theo venue/branch.
  - Frontend:
    - Bố cục 16:9 full screen, chữ lớn, contrast cao.
    - Auto-play mode khóa thao tác, admin lock/unlock.
    - Placeholder khi không có trận.
  - Test:
    - Chạy liên tục 30 phút không refresh.
    - Không crash khi source tạm rỗng.

### M3 — Print Center (`/admin/print-center`)
- **Mục tiêu thương mại:** In nhanh, in đúng mẫu, giảm thời gian thao tác của nhân viên.
- **Cần nâng cấp**
  - Backend:
    - Tập trung queue in: badge, bảng tên, lịch, phiếu trận, bracket, chứng nhận.
    - Chuẩn hóa template và metadata cho mỗi loại in.
    - Log kết quả in (ai in lúc nào, bản ghi nào).
  - Frontend:
    - Màn hình “chọn bulk + preview 1 lần”.
    - Lọc nhanh theo đợt giải, ngày, branch, trạng thái.
  - UX:
    - Tối giản 3 bước: chọn -> preview -> in.
  - Test:
    - In hàng loạt tối thiểu 50 item.
    - Xử lý lỗi máy in/no response.

### M4 — Tournament Template (`/admin/tournament-templates`)
- **Mục tiêu thương mại:** Tái dùng cấu hình giải cũ, mở đăng ký mới nhanh trong 1-2 phút.
- **Cần nâng cấp**
  - Template versioning:
    - Lưu snapshot: quy định, khung giờ, format bảng, quy tắc scoring.
    - Clone + overwrite ngày diễn ra + branch.
  - Frontend:
    - Wizard gồm 5 bước có validate.
    - Gợi ý conflict khi ngày/sân/tournament clash.
  - UX:
    - Nút “Tạo nhanh từ mẫu” luôn nổi.
- **Test:**
  - Tạo 5 event từ 1 template.
  - Mức lệch lịch giữa template và bản clone phải có cảnh báo.

### M5 — Front Desk (`/admin/front-desk`)
- **Mục tiêu thương mại:** Quầy làm việc nhanh, rõ hold queue, tự cảnh báo trễ slot.
- **Cần nâng cấp**
  - Backend:
    - Tách `queue hold`, `queue walk-in`, `queue check-in`.
    - Log thao tác quầy (mã hóa event + actor + session id).
    - Cơ chế hold timeout + cảnh báo theo ngưỡng phút.
  - Frontend:
    - 3 panel: `Incoming`, `Current`, `Late`.
    - Nút thao tác lớn cho touch + shortcut bàn phím.
  - UX:
    - Cảnh báo âm thanh nhẹ nếu booking đến giờ mà chưa check-in.
- **Test:**
  - Hold timeout auto-release + không phát sinh duplicate.
  - Check-in nhanh từ queue không cần chuyển trang.

### M6 — Owner Dashboard (`/admin/owner-dashboard`)
- **Mục tiêu thương mại:** 1 màn hình ra quyết định cho chủ sân.
- **Cần nâng cấp**
  - Backend:
    - KPI MTD, YTD, Doanh thu ròng, giờ cao điểm, nợ quá hạn.
    - Cảnh báo chênh lệch công nợ theo branch.
    - Top khách & top cơ hội tái kích hoạt.
  - Frontend:
    - Dashboard card + sparkline + cột cảnh báo.
  - UX:
    - Tất cả metric có drill-down đến report chi tiết.
- **Test:**
  - Đúng phép cộng theo branch + ngày + nguồn doanh thu.

### M7 — Daily Closing (`/admin/daily-closing`)
- **Mục tiêu thương mại:** Chốt ca ngày/ca làm việc chuẩn kiểm soát kế toán.
- **Cần nâng cấp**
  - Backend:
    - Khóa số liệu theo `tenant + branch + date`.
    - Chấm công quầy, chữ ký điện tử người chốt ca.
    - Tự sinh biên bản PDF có hash để không bị sửa đổi.
  - Frontend:
    - Wizard chốt ca 4 bước có xác nhận.
    - Trạng thái đã khóa/đang khóa.
  - UX:
    - Cảnh báo nếu còn payment/pending booking chưa hợp lệ trước khi khóa.
- **Test:**
  - Không cho chỉnh sửa sau khi khóa.
  - Compare tổng tiền chốt với report.

### M8 — Membership Renewal (`/admin/memberships/renewals`)
- **Mục tiêu thương mại:** Gia hạn theo chủ động + automation chăm sóc.
- **Cần nâng cấp**
  - Backend:
    - Lịch sử gia hạn theo event (manual/auto/manual reminder).
    - Cân bằng hạn dùng: gia hạn theo ngày + gia hạn theo slot gia hạn.
    - Queue nhắc `SMS/Zalo/Email` theo template, trạng thái gửi và retry.
  - Frontend:
    - Danh sách ưu tiên: hết hạn gần, đang gia hạn, quá hạn.
    - Nút “Gửi thử” (test mode) cho campaign nhắc.
  - UX:
    - Màu cảnh báo theo mốc: 30/14/7/1 ngày.
- **Test:**
  - Test mode không sinh tiền, không đổi dữ liệu thật.
  - Nhắc không trùng nhau quá 30 phút.

### M9 — CRM Campaign (`/admin/crm-campaigns`)
- **Mục tiêu thương mại:** Triển khai chiến dịch marketing ổn định và kiểm soát.
- **Cần nâng cấp**
  - Backend:
    - `throttle rate` theo channel + tenant.
    - Template variable engine: `{{name}}`, `{{expiry_date}}`, `{{court_name}}`.
    - Retry policy: exponential backoff + dead-letter summary.
    - A/B mẫu thử A/B.
  - Frontend:
    - Composer cho nội dung + preview trước gửi theo kênh.
    - Dashboard campaign: đã gửi / thất bại / mở / click.
  - UX:
    - Kiểm duyệt trước khi gửi hàng loạt.
- **Test:**
  - Test gửi qua tất cả channel bằng mock.
  - Replay không làm trùng khi callback retry.

### M10 — Club Membership (`/admin/memberships` + `/admin/clubs`)
- **Mục tiêu thương mại:** Quản lý membership theo từng CLB và liên kết dữ liệu vận động viên rõ ràng.
- **Cần nâng cấp**
  - Backend:
    - Quan hệ member-club-tenant rõ ràng, lịch sử trạng thái.
    - Membership quyền lợi theo CLB (nếu có) và theo sân.
    - Kiểm soát xóa (soft-delete), chuyển CLB.
  - Frontend:
    - Thẻ thành viên có avatar + status chip + hạn + lịch sử.
  - UX:
    - Nút chuyển CLB + nhắc khi hết hạn.
- **Test:**
  - Không xảy ra duplicate membership cho cùng player+club+kỳ hạn.

### M11 — Facility Management (`/admin/facilities`)
- **Mục tiêu thương mại:** Tạo/sửa/cập nhật cơ sở nhanh, ít sai cấu hình.
- **Cần nâng cấp**
  - Backend:
    - Bổ sung schema validate (slug/code unique theo tenant).
    - Kiểm tra dependency trước khi archive/đóng cơ sở.
  - Frontend:
    - 2 cấp view: overview và ops health.
  - UX:
    - Cấu trúc “clone/sao chép” cấu hình từ cơ sở mẫu.
- **Test:**
  - Không xóa facility đang dùng bởi booking lịch sử.

### M12 — Branch & Court Operations (`/admin/branches`, `/admin/courts`)
- **Mục tiêu thương mại:** Lịch chi nhánh + sân chạy đúng lịch mở cửa, không bị booking sai giờ.
- **Cần nâng cấp**
  - Backend:
    - Validation lịch mở cửa + ngày nghỉ + bảo trì.
    - Cạnh tranh booking theo court lock.
  - Frontend:
    - Calendar/heatmap trạng thái court.
  - UX:
    - Tập trung lỗi trực quan ngay tại trường nhập.
- **Test:**
  - Conflict check khi thay đổi giờ hoạt động / maintenance.

### M13 — Club Management (`/admin/clubs`, `/clubs`)
- **Mục tiêu thương mại:** CLB có profile mạnh, đồng bộ public portal.
- **Cần nâng cấp**
  - Backend:
    - Quyền hiển thị công khai theo trạng thái phê duyệt.
    - SEO metadata cơ bản cho public club/venue pages.
  - Frontend:
    - Template trang công khai responsive (đặt sân theo CLB, lịch thi đấu, thành viên tiêu biểu).
  - UX:
  - Giao diện theo nhánh/địa điểm, không “nén” nhiều thông tin.
- **Test:**
  - Public club page deep-link không lộ dữ liệu cross-tenant.

### M14 — Facility-Club Partnership
- **Mục tiêu thương mại:** Tách/ghép quan hệ sâu giữa sân và CLB để chia tỉ lệ, ưu tiên booking.
- **Cần nâng cấp**
  - Backend:
    - Quan hệ nhiều-nhiều có lịch sử thay đổi (% chia doanh thu, ưu tiên giờ cao điểm).
    - Audit khi thay đổi tỷ lệ/điều khoản.
  - Frontend:
    - Bảng mapping facility ↔ club có filter + export.
  - UX:
    - Mẫu nhập nhanh + gợi ý CLB top theo lịch sử liên kết.
- **Test:**
  - Kiểm soát báo cáo doanh thu theo phân bổ chính xác.

### M15 — Public Venue/Club + API chuẩn + Security Hardening
- **Mục tiêu thương mại:** Public portal và API vận hành được cho khách hàng ngoài, có bảo mật sản xuất.
- **Cần nâng cấp**
  - Public venue & club:
    - Hiển thị danh sách, chi tiết sân/giải đấu/nhà tổ chức rõ SEO.
    - Đường dẫn vận động viên: liên kết tới lịch sử/chi tiết profile.
  - API chuẩn:
    - Chuẩn `page`, `limit`, `sort`, `filter`, `search`, `fields`, `format=csv/xlsx`.
    - Versioning, error contract, rate-limit, idempotency key cho write.
  - Security:
    - Input hardening, HMAC webhook, policy cho file upload, request logging + sensitive masking.
  - UX:
    - Public page phải có path rõ, trạng thái loading và empty state sạch.
- **Test:**
  - API contract test từng endpoint public.
  - Security test tối thiểu: unauthorized, invalid tenant, SQLi payload, rate-limit.

## 4) Lộ trình triển khai gợi ý (12 tuần)

### Giai đoạn 1 — Cốt vận hành thương mại (Tuần 1-4)
- M5 Front Desk
- M7 Daily Closing
- M6 Owner Dashboard
- M8 Membership Renewal
- M1 Venue Control Room

### Giai đoạn 2 — Tăng tốc dịch vụ và truyền thông (Tuần 5-8)
- M2 TV/LED Mode
- M3 Print Center
- M4 Tournament Template
- M9 CRM Campaign
- M15 Security/API/Public nâng nền

### Giai đoạn 3 — Chuẩn hóa hệ sinh thái (Tuần 9-12)
- M10 Club Membership
- M11 Facility Management
- M12 Branch & Court Ops
- M13 Club Management
- M14 Facility-Club Partnership

## 5) KPI nghiệm thu theo module (điều kiện hoàn thành)

- **Ổn định:** lỗi nghiêm trọng 0 trong 48h smoke test trên môi trường staging.
- **Hiệu năng:** danh sách >2.000 bản ghi render <2s (kèm pagination).
- **Trải nghiệm:** tỉ lệ thao tác sai thao tác < 2% trong 3 ngày chạy ổn định.
- **Báo cáo:** xuất dữ liệu đúng định dạng, không thiếu dòng do filter.
- **Bảo mật:** route admin + API có quyền đúng theo role, không truy cập chéo tenant.

## 6) Checklist bắt buộc trước khi đóng từng module

1. Migrate + seed liên quan đã chạy thành công.
2. Có ít nhất 1 test nghiệp vụ chính + 1 test phân quyền + 1 test lỗi.
3. Có migration fallback khi cột/chức năng chưa có.
4. Có nhật ký thao tác quan trọng.
5. Có link deep giữa dữ liệu liên quan trên UI (ví dụ booking → player → lịch sử).
6. Có hướng dẫn kiểm thử 1-nút (runbook section) cho owner/chủ sân.

## 7) Gợi ý code tiếp theo

- Ưu tiên thực hiện theo đúng thứ tự trong Giai đoạn 1.
- Mỗi sprint giao 1-2 module, tránh quá dồn để giữ chất lượng.
- Mỗi module xong cập nhật file này cùng `docs/IMPLEMENTATION_LOG.md` và tạo checklist “Đã bật cho demo/local”.
