<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$counts = [
    'pending' => count(array_filter($bookings ?? [], fn ($b) => $b->status === 'pending')),
    'paid' => count(array_filter($bookings ?? [], fn ($b) => $b->payment_status === 'paid')),
    'checked_in' => count(array_filter($bookings ?? [], fn ($b) => $b->status === 'checked_in')),
    'cancelled' => count(array_filter($bookings ?? [], fn ($b) => $b->status === 'cancelled')),
];
?>
<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/bookings/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Tạo booking</a>
        <button class="erp-btn"><i class="bi bi-file-earmark-excel"></i> Xuất Excel</button>
        <button class="erp-btn" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
    <a href="/admin/bookings/calendar" class="erp-btn"><i class="bi bi-calendar3"></i> Lịch sân</a>
</div>

<?= view('layouts/partials/stat_cards', ['stats' => [
    ['label' => 'Tổng booking', 'value' => (string) count($bookings ?? []), 'trend' => 'Theo bộ lọc hiện tại', 'icon' => 'bi-calendar-check'],
    ['label' => 'Chờ xác nhận', 'value' => (string) $counts['pending'], 'trend' => 'Cần xử lý', 'icon' => 'bi-hourglass-split'],
    ['label' => 'Đã thanh toán', 'value' => (string) $counts['paid'], 'trend' => 'Sẵn sàng vận hành', 'icon' => 'bi-credit-card'],
    ['label' => 'Đang chơi', 'value' => (string) $counts['checked_in'], 'trend' => 'Check-in trong sân', 'icon' => 'bi-play-circle'],
]]) ?>

<?= view('layouts/partials/filter_bar', ['left' => '
    <select name="branch_id" class="erp-select" style="width:200px"><option value="">Tất cả chi nhánh</option>' .
        implode('', array_map(fn ($b) => '<option value="' . $b->id . '" ' . (($branchId ?? '') == $b->id ? 'selected' : '') . '>' . esc($b->name) . '</option>', $branches ?? [])) .
    '</select>
    <input type="date" name="date" class="erp-control" style="width:160px" value="' . esc($date ?? '') . '">
    <select name="status" class="erp-select" style="width:170px"><option value="">Tất cả trạng thái</option><option value="pending">Chờ xác nhận</option><option value="reserved">Đã giữ chỗ</option><option value="checked_in">Đang chơi</option><option value="cancelled">Đã hủy</option></select>
    <select name="payment_status" class="erp-select" style="width:190px"><option>Thanh toán bất kỳ</option><option>Chưa thanh toán</option><option>Đã thanh toán</option></select>
    <input name="search" class="erp-control" style="width:280px" value="' . esc($search ?? '') . '" placeholder="Tên / SĐT / mã booking">
']) ?>

<div class="erp-summary-chips">
    <div class="erp-summary-chip"><?= renderStatusBadge('pending', 'booking') ?> <strong><?= $counts['pending'] ?></strong></div>
    <div class="erp-summary-chip"><?= renderStatusBadge('paid', 'payment') ?> <strong><?= $counts['paid'] ?></strong></div>
    <div class="erp-summary-chip"><?= renderStatusBadge('checked_in', 'booking') ?> <strong><?= $counts['checked_in'] ?></strong></div>
    <div class="erp-summary-chip"><?= renderStatusBadge('cancelled', 'booking') ?> <strong><?= $counts['cancelled'] ?></strong></div>
</div>

<?php if (! empty($bookings)): ?>
<div class="erp-table-wrap">
    <table class="erp-table">
        <thead><tr><th>Mã booking</th><th>Khách hàng</th><th>Sân</th><th>Ngày giờ</th><th>Số tiền</th><th>Booking</th><th>Thanh toán</th><th>Nguồn</th><th class="col-actions">Action</th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><strong><?= esc($booking->booking_code) ?></strong><div class="erp-muted">#<?= (int) $booking->id ?></div></td>
                <td><?= esc($booking->customer_name) ?><div class="erp-muted"><?= esc($booking->customer_phone) ?></div></td>
                <td><?= esc($booking->court_info ?? 'Xem chi tiết') ?></td>
                <td><?= esc($booking->booking_date) ?><div class="erp-muted"><?= esc($booking->getTimeRange()) ?></div></td>
                <td><strong><?= format_money($booking->total_amount) ?></strong></td>
                <td><?= renderStatusBadge($booking->status, 'booking') ?></td>
                <td><?= renderStatusBadge($booking->payment_status, 'payment') ?></td>
                <td><span class="erp-chip erp-status-neutral"><?= esc($booking->source) ?></span></td>
                <td class="col-actions">
                    <button class="erp-btn erp-btn-icon" data-drawer-open="#erpQuickDrawer" data-drawer-url="/admin/ops/booking-drawer/<?= $booking->id ?>" title="Xem nhanh"><i class="bi bi-eye"></i></button>
                    <a class="erp-btn erp-btn-icon" href="/admin/bookings/reschedule/<?= $booking->id ?>" title="Sửa/đổi lịch"><i class="bi bi-pencil"></i></a>
                    <?php if (in_array($booking->status, ['reserved', 'paid'], true)): ?>
                        <form method="post" action="/admin/bookings/check-in/<?= $booking->id ?>" class="d-inline"><?= csrf_field() ?><button class="erp-btn erp-btn-icon" data-confirm="Check-in booking này?"><i class="bi bi-qr-code-scan"></i></button></form>
                    <?php endif; ?>
                    <?php if (! in_array($booking->status, ['completed', 'cancelled', 'refunded', 'no_show'], true)): ?>
                        <form method="post" action="/admin/bookings/cancel/<?= $booking->id ?>" class="d-inline"><?= csrf_field() ?><button class="erp-btn erp-btn-icon" data-confirm="Hủy booking này?"><i class="bi bi-x-circle"></i></button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="erp-pagination"><span class="erp-muted">Tổng <?= count($bookings) ?> booking</span><div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary">Trước</button><button class="btn btn-outline-secondary active">1</button><button class="btn btn-outline-secondary">Sau</button></div></div>
</div>

<div class="erp-mobile-list">
    <?php foreach ($bookings as $booking): ?>
        <article class="erp-mobile-card">
            <div class="d-flex justify-content-between gap-2 mb-2">
                <div>
                    <strong><?= esc($booking->booking_code) ?></strong>
                    <div class="erp-muted"><?= esc($booking->customer_name) ?> · <?= esc($booking->customer_phone) ?></div>
                </div>
                <?= renderStatusBadge($booking->status, 'booking') ?>
            </div>
            <div class="erp-info-list">
                <div class="erp-info-row"><span>Sân</span><strong><?= esc($booking->court_info ?? 'Xem chi tiết') ?></strong></div>
                <div class="erp-info-row"><span>Thời gian</span><strong><?= esc($booking->booking_date) ?> <?= esc($booking->getTimeRange()) ?></strong></div>
                <div class="erp-info-row"><span>Số tiền</span><strong><?= format_money($booking->total_amount) ?></strong></div>
                <div class="erp-info-row"><span>Thanh toán</span><?= renderStatusBadge($booking->payment_status, 'payment') ?></div>
            </div>
            <div class="d-flex gap-2 flex-wrap mt-3">
                <button class="erp-btn" data-drawer-open="#erpQuickDrawer" data-drawer-url="/admin/ops/booking-drawer/<?= $booking->id ?>"><i class="bi bi-eye"></i> Xem nhanh</button>
                <a class="erp-btn" href="/admin/bookings/reschedule/<?= $booking->id ?>"><i class="bi bi-pencil"></i> Sửa</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="erp-card erp-empty">
    <div class="erp-empty-icon"><i class="bi bi-calendar-x"></i></div>
    <h3>Không có booking phù hợp</h3>
    <p>Thử đổi bộ lọc hoặc tạo booking mới.</p>
    <a href="/admin/bookings/create" class="erp-btn erp-btn-primary">Tạo booking</a>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
