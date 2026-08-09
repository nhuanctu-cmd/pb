<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?= view('layouts/partials/stat_cards', ['stats' => [
    ['label' => 'Doanh thu hôm nay', 'value' => '42,8tr', 'trend' => '+18% so với hôm qua', 'icon' => 'bi-cash-stack'],
    ['label' => 'Booking hôm nay', 'value' => '126', 'trend' => '34 booking cao điểm', 'icon' => 'bi-calendar2-check'],
    ['label' => 'Sân đang hoạt động', 'value' => '28/32', 'trend' => '4 sân bảo trì', 'trendType' => 'danger', 'icon' => 'bi-grid-3x3-gap'],
    ['label' => 'Sân trống', 'value' => '9', 'trend' => 'Khung 15:00 - 17:00', 'icon' => 'bi-check2-circle'],
    ['label' => 'Người chơi check-in', 'value' => '84', 'trend' => '21 hội viên', 'icon' => 'bi-person-check'],
    ['label' => 'Giải đấu đang chạy', 'value' => '3', 'trend' => '2 giải sắp khai mạc', 'icon' => 'bi-trophy'],
]]) ?>

<div class="erp-dashboard-grid">
    <div class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header">
                <strong>Doanh thu 7 ngày gần nhất</strong>
                <div class="d-flex gap-2">
                    <?= renderStatusBadge('success') ?>
                    <button class="erp-btn erp-btn-icon"><i class="bi bi-three-dots"></i></button>
                </div>
            </div>
            <div class="erp-card-body">
                <div class="erp-revenue-bars">
                    <?php foreach ([44, 58, 36, 72, 64, 88, 79] as $i => $height): ?>
                        <div class="erp-revenue-day">
                            <div class="erp-revenue-bar" style="height: <?= $height * 2 ?>px"></div>
                            <strong><?= ['T2','T3','T4','T5','T6','T7','CN'][$i] ?></strong>
                            <span class="erp-muted"><?= [18,24,15,31,28,39,34][$i] ?>tr</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header">
                <strong>Heatmap giờ cao điểm</strong>
                <span class="erp-muted">Tỷ lệ lấp đầy theo cụm sân</span>
            </div>
            <div class="erp-card-body">
                <div class="erp-heatmap">
                    <strong>Khung giờ</strong><strong>Q.7</strong><strong>Thủ Đức</strong><strong>Bình Thạnh</strong><strong>Q.1</strong><strong>Gò Vấp</strong><strong>Tân Bình</strong>
                    <?php foreach ([
                        ['06-09',1,2,1,2,1,2],
                        ['09-12',2,2,1,1,2,1],
                        ['12-15',1,1,2,1,1,1],
                        ['15-18',3,3,2,3,2,3],
                        ['18-21',5,5,4,5,4,5],
                        ['21-23',3,4,3,4,3,3],
                    ] as $row): ?>
                        <strong><?= esc($row[0]) ?></strong>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div class="erp-heat-cell erp-heat-<?= $row[$i] ?>"><?= $row[$i] * 20 ?>%</div>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="row g-3">
            <div class="col-xl-6">
                <section class="erp-card h-100">
                    <div class="erp-card-header"><strong>Lịch sân hôm nay</strong><a href="/admin/bookings/calendar">Mở lịch</a></div>
                    <div class="erp-card-body erp-calendar-strip">
                        <?php foreach ([['18:00', 'Nguyễn Minh Anh', 'Sân A01', 'paid'], ['18:30', 'Trần Hoàng Yến', 'Sân B02', 'reserved'], ['19:00', 'Lê Quốc Bảo', 'Sân C04', 'checked_in']] as $item): ?>
                            <div class="erp-calendar-item">
                                <strong><?= esc($item[0]) ?></strong>
                                <div><strong><?= esc($item[1]) ?></strong><div class="erp-muted"><?= esc($item[2]) ?> · 60 phút</div></div>
                                <?= renderStatusBadge($item[3], 'booking') ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
            <div class="col-xl-6">
                <section class="erp-card h-100">
                    <div class="erp-card-header"><strong>Booking mới nhất</strong></div>
                    <div class="erp-card-body erp-calendar-strip">
                        <?php foreach ([['BK-240706-0188','Phạm Gia Hân','Zalo','pending'], ['BK-240706-0187','Đỗ Minh Khang','Admin','paid'], ['BK-240706-0186','Công ty An Phát','Website','reserved']] as $item): ?>
                            <div class="erp-calendar-item">
                                <strong><?= esc($item[0]) ?></strong>
                                <div><strong><?= esc($item[1]) ?></strong><div class="erp-muted">Nguồn: <?= esc($item[2]) ?></div></div>
                                <?= renderStatusBadge($item[3], 'booking') ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header"><strong>Cảnh báo vận hành</strong><?= renderStatusBadge('warning') ?></div>
            <div class="erp-card-body erp-alert-list">
                <div class="erp-alert-item"><i class="bi bi-exclamation-triangle text-warning"></i><div><strong>Sân B03 quá giờ 12 phút</strong><div class="erp-muted">Cần nhắc khách checkout hoặc gia hạn.</div></div></div>
                <div class="erp-alert-item"><i class="bi bi-credit-card text-danger"></i><div><strong>8 booking chưa đặt cọc</strong><div class="erp-muted">3 booking sẽ tự hủy trong 5 phút.</div></div></div>
                <div class="erp-alert-item"><i class="bi bi-tools text-info"></i><div><strong>Bảo trì đèn sân A04</strong><div class="erp-muted">Đã khóa lịch sau 21:00.</div></div></div>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header"><strong>Top sân doanh thu</strong></div>
            <div class="erp-card-body erp-mini-rank">
                <?php foreach ([['1','Sân A01','5,8tr'], ['2','Sân B02','5,1tr'], ['3','Sân C04','4,7tr'], ['4','Sân A03','4,2tr']] as $rank): ?>
                    <div class="erp-mini-rank-item"><strong>#<?= esc($rank[0]) ?></strong><div><?= esc($rank[1]) ?><div class="erp-muted">Tỷ lệ lấp đầy 86%</div></div><strong><?= esc($rank[2]) ?></strong></div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header"><strong>Giải đấu sắp diễn ra</strong></div>
            <div class="erp-card-body erp-calendar-strip">
                <div class="erp-calendar-item"><strong>12/07</strong><div><strong>Summer Open 2026</strong><div class="erp-muted">64 đội · Q.7</div></div><?= renderStatusBadge('open', 'tournament') ?></div>
                <div class="erp-calendar-item"><strong>20/07</strong><div><strong>Corporate Cup</strong><div class="erp-muted">32 đội · Thủ Đức</div></div><?= renderStatusBadge('draft', 'tournament') ?></div>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
