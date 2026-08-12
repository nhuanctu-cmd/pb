<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>
<?php $summary = $operations['summary'] ?? []; $courts = $operations['courts'] ?? []; $waitlist = $operations['waitlist'] ?? []; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Front Desk</h1>
        <div class="text-muted">Màn hình một chạm cho nhân viên quầy · <?= esc($date) ?></div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="/admin/bookings/create"><i class="bi bi-plus-circle me-1"></i>Booking mới</a>
        <a class="btn btn-outline-primary" href="/admin/walk-ins">Walk-in</a>
        <a class="btn btn-outline-secondary" href="/admin/daily-closing?date=<?= esc($date) ?>">Chốt ca</a>
    </div>
</div>

<form class="card card-body mb-3" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Ngày vận hành</label>
            <input class="form-control" type="date" name="date" value="<?= esc($date) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Trạng thái</label>
            <select class="form-select" name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="reserved" <?= ($statusFilter ?? '') === 'reserved' ? 'selected' : '' ?>>Đã giữ</option>
                <option value="paid" <?= ($statusFilter ?? '') === 'paid' ? 'selected' : '' ?>>Đã thu tiền</option>
                <option value="checked_in" <?= ($statusFilter ?? '') === 'checked_in' ? 'selected' : '' ?>>Đang chơi</option>
                <option value="in_progress" <?= ($statusFilter ?? '') === 'in_progress' ? 'selected' : '' ?>>Đang vận hành</option>
                <option value="completed" <?= ($statusFilter ?? '') === 'completed' ? 'selected' : '' ?>>Hoàn tất</option>
                <option value="cancelled" <?= ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                <option value="no_show" <?= ($statusFilter ?? '') === 'no_show' ? 'selected' : '' ?>>Không tới</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Tìm nhanh</label>
            <input class="form-control" type="search" name="search" value="<?= esc($search ?? '') ?>" placeholder="Tên / SĐT / Mã booking">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100">Lọc dữ liệu</button>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <?php foreach ([['Booking', $summary['total'] ?? 0, 'bi-calendar-check', 'primary'], ['Đang chơi', $summary['playing'] ?? 0, 'bi-play-circle', 'success'], ['Sân trống', $courts['available'] ?? 0, 'bi-grid-3x3-gap', 'info'], ['Chờ xử lý', ($waitlist['waiting'] ?? 0) + ($operations['walk_ins']['waiting'] ?? 0), 'bi-hourglass-split', 'warning']] as $metric): ?>
        <div class="col-6 col-xl-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex justify-content-between">
                    <div>
                        <div class="text-muted small"><?= esc($metric[0]) ?></div>
                        <div class="h4 mb-0"><?= esc((string) $metric[1]) ?></div>
                    </div>
                    <i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Lịch trong ngày</strong>
        <span class="text-muted small">Cập nhật từ booking và booking_items</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Giờ</th><th>Mã</th><th>Khách</th><th>Sân</th><th>Thanh toán</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có booking phù hợp.</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= esc(substr((string) $booking->start_time, 0, 5)) ?>
                            <br><small class="text-muted"><?= esc(substr((string) $booking->end_time, 0, 5)) ?></small>
                        </td>
                        <td><span class="badge text-bg-light">#<?= (int) $booking->id ?></span></td>
                        <td>
                            <a href="/admin/bookings/show/<?= (int) $booking->id ?>"><?= esc($booking->customer_name) ?></a>
                            <br><small class="text-muted"><?= esc($booking->customer_phone) ?></small>
                        </td>
                        <td><?= esc($booking->court_names ?: 'Chưa gán sân') ?></td>
                        <td><?= number_format((float) $booking->paid_amount, 0, ',', '.') ?> / <?= number_format((float) $booking->total_amount, 0, ',', '.') ?>đ</td>
                        <td><span class="badge text-bg-light"><?= esc($booking->status) ?></span></td>
                        <td class="text-end">
                            <?php if (in_array($booking->status, ['reserved', 'paid'], true)): ?>
                                <form method="post" action="/admin/bookings/check-in/<?= (int) $booking->id ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-success">
                                        <i class="bi bi-box-arrow-in-right"></i> Check-in
                                    </button>
                                </form>
                            <?php elseif (in_array($booking->status, ['checked_in', 'in_progress'], true)): ?>
                                <span class="text-success small"><i class="bi bi-play-circle"></i> Đang chơi</span>
                            <?php else: ?>
                                <a class="btn btn-sm btn-outline-secondary" href="/admin/bookings/show/<?= (int) $booking->id ?>">Chi tiết</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
