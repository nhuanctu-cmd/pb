# Chạy local bằng Laragon

Mở Laragon và bật Apache + MySQL. Không cần chạy `php spark`, `migrate`, `seed`, npm hoặc lệnh build frontend.

Laragon có thể mở project bằng virtual host `pickball-system.test` hoặc đường dẫn localhost tương ứng. Lần truy cập đầu sẽ tự kiểm tra schema; nếu database chưa sẵn sàng, ứng dụng tự chạy migration và bộ dữ liệu demo thương mại một lần, có khóa chống chạy trùng.

Thiết lập này dùng cho local development qua `app.autoBootstrap = true` trong `.env`. Khi triển khai production thật, đặt giá trị này thành `false` và chạy migration/seed qua quy trình release riêng.
