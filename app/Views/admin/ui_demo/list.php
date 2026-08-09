<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$bookings = [
    ['BK-240706-0188','Phạm Gia Hân','0908 123 456','Sân A01','06/07/2026 18:00-19:00','220,000đ','pending','unpaid','Zalo'],
    ['BK-240706-0187','Đỗ Minh Khang','0912 778 899','Sân B02','06/07/2026 18:30-20:00','330,000đ','paid','paid','Admin'],
    ['BK-240706-0186','Nguyễn Minh Anh','0901 000 001','Sân C04','06/07/2026 19:00-20:00','190,000đ','checked_in','paid','Player Portal'],
    ['BK-240706-0185','Công ty An Phát','028 7777 8888','Sân A03, A04','06/07/2026 20:00-22:00','880,000đ','reserved','partial','Website'],
    ['BK-240706-0184','Trần Hoàng Yến','0933 555 666','Sân D01','06/07/2026 16:00-17:00','150,000đ','cancelled','refunded','Phone'],
];
?>

<div class="erp-action-bar">
    <div class="d-flex gap-2">
        <a class="erp-btn erp-btn-primary" href="/admin/bookings/create"><i class="bi bi-plus-lg"></i> Tạo booking</a>
        <button class="erp-btn"><i class="bi bi-file-earmark-excel"></i> Xuất Excel</button>
        <button class="erp-btn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
    <div class="erp-muted">Cập nhật lúc 15:42 · 06/07/2026</div>
</div>

<?= view('layouts/partials/filter_bar', ['left' => '
    <select class="erp-select" style="width:180px"><option>Tất cả chi nhánh</option><option>Q.7 Riverside</option><option>Thủ Đức Prime</option></select>
    <input type="date" class="erp-control" style="width:160px" value="2026-07-06">
    <select class="erp-select" style="width:170px"><option>Tất cả trạng thái</option><option>Chờ xác nhận</option><option>Đã thanh toán</option><option>Đang chơi</option></select>
    <select class="erp-select" style="width:190px"><option>Thanh toán bất kỳ</option><option>Chưa thanh toán</option><option>Đã thanh toán</option><option>Hoàn tiền</option></select>
    <input class="erp-control" style="width:280px" placeholder="Tên / SĐT / mã booking">
']) ?>

<div class="erp-summary-chips">
    <div class="erp-summary-chip"><?= renderStatusBadge('pending', 'booking') ?> <strong>18</strong></div>
    <div class="erp-summary-chip"><?= renderStatusBadge('paid', 'booking') ?> <strong>42</strong></div>
    <div class="erp-summary-chip"><?= renderStatusBadge('checked_in', 'booking') ?> <strong>11</strong></div>
    <div class="erp-summary-chip"><?= renderStatusBadge('cancelled', 'booking') ?> <strong>5</strong></div>
</div>

<?= view('layouts/partials/table_toolbar', ['left' => '<button class="erp-btn"><i class="bi bi-check2-square"></i> Bulk action</button>', 'right' => '<button class="erp-btn"><i class="bi bi-layout-three-columns"></i> Tùy chỉnh cột</button>']) ?>

<?php if (! empty($bookings)): ?>
<div class="erp-table-wrap">
    <table class="erp-table">
        <thead>
            <tr>
                <th style="width:42px"><input type="checkbox" data-bulk-master></th>
                <th>Mã booking <i class="bi bi-arrow-down-up erp-sort"></i></th>
                <th>Khách hàng</th>
                <th>Sân</th>
                <th>Ngày giờ</th>
                <th>Số tiền</th>
                <th>Booking</th>
                <th>Thanh toán</th>
                <th>Nguồn</th>
                <th class="col-actions">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bookings as $i => $row): ?>
            <tr>
                <td><input type="checkbox" data-bulk-item></td>
                <td><strong><?= esc($row[0]) ?></strong><div class="erp-muted">Chi nhánh Q.7</div></td>
                <td><?= esc($row[1]) ?><div class="erp-muted"><?= esc($row[2]) ?></div></td>
                <td><strong><?= esc($row[3]) ?></strong></td>
                <td><?= esc($row[4]) ?></td>
                <td><strong><?= esc($row[5]) ?></strong></td>
                <td><?= renderStatusBadge($row[6], 'booking') ?></td>
                <td><?= renderStatusBadge($row[7], 'payment') ?></td>
                <td><span class="erp-chip erp-status-neutral"><?= esc($row[8]) ?></span></td>
                <td class="col-actions">
                    <button class="erp-btn erp-btn-icon" data-drawer-open="#erpQuickDrawer" data-drawer-template="#bookingDrawer<?= $i ?>" title="Xem nhanh"><i class="bi bi-eye"></i></button>
                    <button class="erp-btn erp-btn-icon" title="Sửa"><i class="bi bi-pencil"></i></button>
                    <button class="erp-btn erp-btn-icon" title="Check-in"><i class="bi bi-qr-code-scan"></i></button>
                    <button class="erp-btn erp-btn-icon" data-confirm="Hủy booking <?= esc($row[0]) ?>?" title="Hủy"><i class="bi bi-x-circle"></i></button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="erp-pagination">
        <span class="erp-muted">Hiển thị 1-5 trong 126 booking</span>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-secondary">Trước</button>
            <button class="btn btn-outline-secondary active">1</button>
            <button class="btn btn-outline-secondary">2</button>
            <button class="btn btn-outline-secondary">3</button>
            <button class="btn btn-outline-secondary">Sau</button>
        </div>
    </div>
</div>
<?php else: ?>
<div class="erp-card erp-empty">
    <div class="erp-empty-icon"><i class="bi bi-calendar-x"></i></div>
    <h3>Không có booking phù hợp</h3>
    <p>Thử đổi bộ lọc hoặc tạo booking mới cho khách hàng.</p>
    <a class="erp-btn erp-btn-primary" href="/admin/bookings/create">Tạo booking</a>
</div>
<?php endif; ?>

<div class="erp-mobile-list">
    <?php foreach ($bookings as $row): ?>
        <div class="erp-mobile-card">
            <div class="d-flex justify-content-between"><strong><?= esc($row[0]) ?></strong><?= renderStatusBadge($row[6], 'booking') ?></div>
            <div><?= esc($row[1]) ?> · <?= esc($row[3]) ?></div>
            <div class="erp-muted"><?= esc($row[4]) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<?php foreach ($bookings as $i => $row): ?>
<template id="bookingDrawer<?= $i ?>">
    <div class="erp-info-list">
        <div class="erp-info-row"><span>Mã booking</span><strong><?= esc($row[0]) ?></strong></div>
        <div class="erp-info-row"><span>Khách hàng</span><strong><?= esc($row[1]) ?></strong></div>
        <div class="erp-info-row"><span>SĐT</span><strong><?= esc($row[2]) ?></strong></div>
        <div class="erp-info-row"><span>Sân</span><strong><?= esc($row[3]) ?></strong></div>
        <div class="erp-info-row"><span>Thời gian</span><strong><?= esc($row[4]) ?></strong></div>
        <div class="erp-info-row"><span>Số tiền</span><strong><?= esc($row[5]) ?></strong></div>
        <div class="erp-info-row"><span>Trạng thái</span><?= renderStatusBadge($row[6], 'booking') ?></div>
    </div>
    <div class="erp-notice mt-3">Khách yêu cầu chuẩn bị 4 vợt thuê và 2 chai nước suối tại quầy.</div>
</template>
<?php endforeach; ?>
<?= $this->endSection() ?>
