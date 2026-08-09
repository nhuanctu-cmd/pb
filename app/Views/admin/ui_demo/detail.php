<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<section class="erp-card mb-3">
    <div class="erp-card-body erp-entity-header">
        <div class="erp-entity-main">
            <div class="erp-avatar" style="width:64px;height:64px;font-size:24px">A</div>
            <div>
                <h2 class="erp-entity-title">Nguyễn Minh Anh</h2>
                <div class="erp-muted">PL-0001 · Nữ · TP. Hồ Chí Minh · Tham gia từ 12/03/2025</div>
                <div class="d-flex gap-2 mt-2">
                    <span class="erp-status erp-status-info">Advanced</span>
                    <span class="erp-status erp-status-dark">Rating 1,685</span>
                    <?= renderStatusBadge('active', 'membership') ?>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <button class="erp-btn"><i class="bi bi-pencil"></i> Sửa hồ sơ</button>
            <a class="erp-btn erp-btn-primary" href="/admin/bookings/create"><i class="bi bi-calendar-plus"></i> Tạo booking</a>
            <button class="erp-btn"><i class="bi bi-wallet2"></i> Nạp ví</button>
        </div>
    </div>
    <div class="erp-tabs px-3" data-erp-tabs>
        <a class="erp-tab active" href="#tabOverview" data-erp-tab>Tổng quan</a>
        <a class="erp-tab" href="#tabBooking" data-erp-tab>Booking</a>
        <a class="erp-tab" href="#tabMatches" data-erp-tab>Trận đấu</a>
        <a class="erp-tab" href="#tabWallet" data-erp-tab>Ví / giao dịch</a>
    </div>
</section>

<div id="tabOverview" data-erp-panel>
    <?= view('layouts/partials/stat_cards', ['stats' => [
        ['label' => 'Tổng booking', 'value' => '86', 'trend' => '12 booking tháng này', 'icon' => 'bi-calendar-check'],
        ['label' => 'Tỷ lệ thắng', 'value' => '64%', 'trend' => '32 thắng / 18 thua', 'icon' => 'bi-trophy'],
        ['label' => 'Check-in streak', 'value' => '9 ngày', 'trend' => 'Best streak 14 ngày', 'icon' => 'bi-fire'],
        ['label' => 'Số dư ví', 'value' => '2,450,000đ', 'trend' => 'Khả dụng', 'icon' => 'bi-wallet2'],
    ]]) ?>

    <div class="erp-grid-dashboard">
        <div class="erp-dashboard-stack">
            <section class="erp-card">
                <div class="erp-card-header"><strong>Thông tin cá nhân</strong></div>
                <div class="erp-card-body erp-info-list">
                    <div class="erp-info-row"><span>Họ tên</span><strong>Nguyễn Minh Anh</strong></div>
                    <div class="erp-info-row"><span>Số điện thoại</span><strong>0901 000 001</strong></div>
                    <div class="erp-info-row"><span>Email</span><strong>minhanh@example.com</strong></div>
                    <div class="erp-info-row"><span>Khu vực</span><strong>TP. Hồ Chí Minh</strong></div>
                    <div class="erp-info-row"><span>Cụm sân yêu thích</span><strong>Pickleball Riverside Q.7</strong></div>
                </div>
            </section>

            <section class="erp-card">
                <div class="erp-card-header"><strong>Hội viên hiện tại</strong><?= renderStatusBadge('active', 'membership') ?></div>
                <div class="erp-card-body erp-info-list">
                    <div class="erp-info-row"><span>Gói</span><strong>Gold 90 ngày</strong></div>
                    <div class="erp-info-row"><span>Hiệu lực</span><strong>01/07/2026 - 29/09/2026</strong></div>
                    <div class="erp-info-row"><span>Ưu đãi</span><strong>Giảm 15% giờ thường, ưu tiên giữ sân</strong></div>
                    <div class="erp-info-row"><span>Ngày còn lại</span><strong>85 ngày</strong></div>
                </div>
            </section>
        </div>

        <aside class="erp-card">
            <div class="erp-card-header"><strong>Lịch sử hoạt động gần đây</strong></div>
            <div class="erp-card-body erp-timeline">
                <?php foreach (['Check-in sân A01 tại Quận 7', 'Thắng trận ranking đôi nam nữ, +18 ELO', 'Nạp ví 1,000,000đ qua chuyển khoản', 'Nhận badge MVP tuần', 'Gia hạn hội viên Gold 90 ngày'] as $item): ?>
                    <div class="erp-timeline-item">
                        <div class="erp-timeline-dot"></div>
                        <div><strong><?= esc($item) ?></strong><div class="erp-muted">06/07/2026 · bởi hệ thống</div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>
</div>

<div id="tabBooking" data-erp-panel class="d-none">
    <div class="erp-table-wrap">
        <table class="erp-table">
            <thead><tr><th>Mã booking</th><th>Sân</th><th>Ngày giờ</th><th>Số tiền</th><th>Trạng thái</th><th>Thanh toán</th><th class="col-actions">Action</th></tr></thead>
            <tbody>
            <?php foreach ([['BK-240706-0187','Sân A01','06/07/2026 18:00-19:30','330,000đ','paid','paid'], ['BK-240629-0102','Sân B02','29/06/2026 07:00-08:00','130,000đ','completed','paid'], ['BK-240622-0098','Sân C04','22/06/2026 20:00-21:00','190,000đ','completed','paid']] as $row): ?>
                <tr>
                    <td><strong><?= esc($row[0]) ?></strong></td>
                    <td><?= esc($row[1]) ?></td>
                    <td><?= esc($row[2]) ?></td>
                    <td><strong><?= esc($row[3]) ?></strong></td>
                    <td><?= renderStatusBadge($row[4], 'booking') ?></td>
                    <td><?= renderStatusBadge($row[5], 'payment') ?></td>
                    <td class="col-actions"><button class="erp-btn erp-btn-icon"><i class="bi bi-eye"></i></button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="tabMatches" data-erp-panel class="d-none">
    <div class="erp-table-wrap">
        <table class="erp-table">
            <thead><tr><th>Ngày</th><th>Đối thủ</th><th>Giải / cụm sân</th><th>Kết quả</th><th>ELO</th><th>Badge</th></tr></thead>
            <tbody>
            <?php foreach ([['05/07/2026','Trần Hoàng Yến','Ranking Q.7','Thắng 2-1','+18','MVP'], ['28/06/2026','Lê Quốc Bảo','Open Ladder','Thua 1-2','-9',''], ['21/06/2026','Phạm Gia Hân','Club Match','Thắng 2-0','+12','Streak']] as $row): ?>
                <tr><td><?= esc($row[0]) ?></td><td><?= esc($row[1]) ?></td><td><?= esc($row[2]) ?></td><td><strong><?= esc($row[3]) ?></strong></td><td><?= esc($row[4]) ?></td><td><?= $row[5] ? '<span class="erp-chip erp-status-info">'.esc($row[5]).'</span>' : '<span class="erp-muted">-</span>' ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="tabWallet" data-erp-panel class="d-none">
    <section class="erp-card mb-3">
        <div class="erp-card-body d-flex justify-content-between align-items-center">
            <div><div class="erp-muted">Số dư ví</div><div class="erp-stat-value">2,450,000đ</div></div>
            <button class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Nạp ví</button>
        </div>
    </section>
    <div class="erp-table-wrap">
        <table class="erp-table">
            <thead><tr><th>Mã GD</th><th>Thời gian</th><th>Loại</th><th>Nội dung</th><th>Số tiền</th><th>Số dư</th><th>Trạng thái</th></tr></thead>
            <tbody>
            <?php foreach ([['TX-10021','06/07/2026 09:12','Nạp ví','Chuyển khoản VietQR','+1,000,000đ','2,450,000đ','success'], ['TX-10012','05/07/2026 18:04','Thanh toán','Booking BK-240705-0160','-220,000đ','1,450,000đ','success'], ['TX-10001','01/07/2026 08:30','Hoàn tiền','Hủy booking trước giờ','+150,000đ','1,670,000đ','success']] as $row): ?>
                <tr><td><strong><?= esc($row[0]) ?></strong></td><td><?= esc($row[1]) ?></td><td><?= esc($row[2]) ?></td><td><?= esc($row[3]) ?></td><td><strong><?= esc($row[4]) ?></strong></td><td><?= esc($row[5]) ?></td><td><?= renderStatusBadge($row[6]) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
