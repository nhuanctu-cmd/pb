<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>
<?php
$summary = $operations['summary'] ?? [];
$courts = $operations['courts'] ?? [];
$waitlist = $operations['waitlist'] ?? [];
?>

<div class="mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1 class="h3 mb-1">Front Desk</h1>
            <div class="text-muted">Bàn vận hành quầy: check-in, kiểm soát trễ, sân đang có khách</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-primary" href="/admin/bookings/create"><i class="bi bi-plus-circle me-1"></i>Booking mới</a>
            <a class="btn btn-outline-primary" href="/admin/walk-ins">Walk-in</a>
            <a class="btn btn-outline-secondary" href="/admin/daily-closing?date=<?= esc($date) ?>">Chốt ca</a>
        </div>
    </div>
</div>

<form class="card card-body mb-3" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Ngay hoat dong</label>
            <input class="form-control" type="date" name="date" value="<?= esc($date) ?>">
        </div>
        <?php if (!empty($branches) && is_superadmin()): ?>
            <div class="col-md-3">
                <label class="form-label small">Chi nhanh</label>
                <select class="form-select" name="branch_id">
                    <option value="">Tat ca chi nhanh</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= (int) $branch->id ?>" <?= ((int) ($scopeBranchId ?? 0) === (int) $branch->id) ? 'selected' : '' ?>>
                            <?= esc($branch->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    <div class="col-md-3">
            <label class="form-label small">Trang thai</label>
            <select class="form-select" name="status">
                <option value="">Tat ca trang thai</option>
                <option value="reserved" <?= ($statusFilter ?? '') === 'reserved' ? 'selected' : '' ?>>Da dat / cho check-in</option>
                <option value="paid" <?= ($statusFilter ?? '') === 'paid' ? 'selected' : '' ?>>Da thanh toan</option>
                <option value="hold" <?= ($statusFilter ?? '') === 'hold' ? 'selected' : '' ?>>HOLD (queue)</option>
                <option value="checked_in" <?= ($statusFilter ?? '') === 'checked_in' ? 'selected' : '' ?>>Dang choi</option>
                <option value="in_progress" <?= ($statusFilter ?? '') === 'in_progress' ? 'selected' : '' ?>>Trong tien trinh</option>
                <option value="completed" <?= ($statusFilter ?? '') === 'completed' ? 'selected' : '' ?>>Da ket thuc</option>
                <option value="cancelled" <?= ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' ?>>Da huy</option>
                <option value="no_show" <?= ($statusFilter ?? '') === 'no_show' ? 'selected' : '' ?>>Khong den</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Tim nhanh</label>
            <input class="form-control" type="search" name="search" value="<?= esc($search ?? '') ?>" placeholder="Ten / SDT / Ma booking">
        </div>
        <div class="col-md-2">
            <label class="form-label small">View</label>
            <select class="form-select" name="frame">
                <option value="" <?= ($timeframe ?? '') === '' ? 'selected' : '' ?>>Tat ca</option>
                <option value="upcoming" <?= ($timeframe ?? '') === 'upcoming' ? 'selected' : '' ?>>Chi booking tu hien tai tro len</option>
            </select>
        </div>
        <div class="col-md-1">
            <button class="btn btn-outline-primary w-100">Loc</button>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Tong booking', $summary['total'] ?? 0, 'bi-calendar-check', 'primary'],
        ['Dang choi', $summary['playing'] ?? 0, 'bi-play-circle', 'success'],
        ['San dang co nguoi', $courts['occupied'] ?? 0, 'bi-grid-3x3-gap', 'danger'],
        ['San trong', (($courts['available'] ?? 0)), 'bi-square', 'info'],
        ['Trong HOLD', count($holdQueue ?? []), 'bi-hourglass', 'secondary'],
        ['Chua check-in', count($lateBookings ?? []), 'bi-hourglass-split', 'warning'],
        ['Cho di qua gio', $operations['walk_ins']['waiting'] ?? 0, 'bi-exclamation-triangle', 'warning'],
    ];
    ?>
    <?php foreach ($cards as $metric): ?>
        <div class="col-6 col-xl-2">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small"><?= esc($metric[0]) ?></div>
                        <div class="h5 mb-0 text-<?= esc($metric[3]) ?>"><?= esc((string) $metric[1]) ?></div>
                    </div>
                    <i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Lich trong ngay</strong>
                <?php if (($nextBooking ?? null) !== null): ?>
                    <span class="small text-muted">
                        Booking ke tiep:
                        #<?= (int) $nextBooking->id ?> - <?= esc(substr((string) $nextBooking->start_time, 0, 5)) ?> đến <?= esc(substr((string) $nextBooking->end_time, 0, 5)) ?> (<?= esc($nextBooking->customer_name) ?>)
                    </span>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr><th>Gio</th><th>Ma</th><th>Khach</th><th>San</th><th>Thanh toan</th><th>Trang thai</th><th class="text-end">Thao tac</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Chua co booking phu hop.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            $bookingStartTs = strtotime((string) $booking->booking_date . ' ' . substr((string) $booking->start_time, 0, 8));
                            $lateMinutes = ($bookingStartTs !== false && in_array($booking->status, ['reserved', 'paid'], true) && $bookingStartTs < time())
                                ? max(0, (int) floor((time() - $bookingStartTs) / 60))
                                : 0;
                            $isLate = $lateMinutes > 0;
                            $isCriticalLate = $lateMinutes >= 15;
                            ?>
                            <tr class="<?= $isCriticalLate ? 'table-danger' : ($isLate ? 'table-warning' : '') ?>">
                                <td class="fw-semibold">
                                    <?= esc(substr((string) $booking->start_time, 0, 5)) ?>
                                    <br><small class="text-muted"><?= esc(substr((string) $booking->end_time, 0, 5)) ?></small>
                                </td>
                                <td><span class="badge text-bg-light">#<?= (int) $booking->id ?></span></td>
                                <td>
                                    <a href="/admin/bookings/show/<?= (int) $booking->id ?>"><?= esc($booking->customer_name) ?></a>
                                    <br><small class="text-muted"><?= esc($booking->customer_phone) ?></small>
                                </td>
                                <td><?= esc($booking->court_names ?: 'Chua gan san') ?></td>
                                <td><?= number_format((float) $booking->paid_amount, 0, ',', '.') ?> / <?= number_format((float) $booking->total_amount, 0, ',', '.') ?>đ</td>
                                <td>
                                    <span class="badge <?= $booking->status === 'no_show' ? 'text-bg-danger' : 'text-bg-light' ?>"><?= esc($booking->status) ?></span>
                                    <?php if ($isLate): ?>
                                        <div class="small text-danger">Muon <?= $lateMinutes ?> phut</div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (in_array($booking->status, ['reserved', 'paid'], true)): ?>
                                <form method="post" action="/admin/front-desk/check-in/<?= (int) $booking->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                                            <button class="btn btn-sm btn-success">
                                                <i class="bi bi-box-arrow-in-right"></i> Check-in
                                            </button>
                                        </form>
                                        <form method="post" action="/admin/front-desk/hold/<?= (int) $booking->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                                            <input type="hidden" name="timeout_minutes" value="5">
                                            <button class="btn btn-sm btn-outline-dark">Hold</button>
                                        </form>
                                        <form method="post" action="/admin/front-desk/no-show/<?= (int) $booking->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                                            <input type="hidden" name="reason" value="Khong check-in dung gio">
                                            <button class="btn btn-sm btn-outline-warning">No-show</button>
                                        </form>
                                    <?php elseif ((string) $booking->status === 'hold'): ?>
                                        <form method="post" action="/admin/front-desk/release-hold/<?= (int) $booking->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Mở HOLD</button>
                                        </form>
                                        <form method="post" action="/admin/front-desk/cancel/<?= (int) $booking->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                                            <input type="hidden" name="reason" value="Huy tu HOLD">
                                            <button class="btn btn-sm btn-outline-danger">Huỷ</button>
                                        </form>
                                    <?php elseif (in_array($booking->status, ['checked_in', 'in_progress'], true)): ?>
                                        <form method="post" action="/admin/front-desk/complete/<?= (int) $booking->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-flag"></i> Ket thuc</button>
                                        </form>
                                        <a class="btn btn-sm btn-outline-secondary" href="/admin/bookings/show/<?= (int) $booking->id ?>">Chi tiet</a>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-outline-secondary" href="/admin/bookings/show/<?= (int) $booking->id ?>">Chi tiet</a>
                                        <form method="post" action="/admin/front-desk/cancel/<?= (int) $booking->id ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                                            <input type="hidden" name="reason" value="Huy tu front desk">
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Xac nhan huy booking?')">Huy</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Tinh trang quan sat nhanh</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2"><span>Tong san</span><strong><?= (int) (($courts['total'] ?? 0)) ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Dang choi</span><strong><?= (int) (($courts['occupied'] ?? 0)) ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Trong</span><strong class="text-success"><?= (int) (($courts['available'] ?? 0)) ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Bao tri</span><strong class="text-warning"><?= (int) (($courts['maintenance'] ?? 0)) ?></strong></div>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header">Canh bao hang ngay</div>
            <div class="card-body">
                <div class="text-muted small mb-2">Tong truong hop den muon tren lich</div>
                <?php if (empty($lateBookings)): ?>
                    <div class="text-success small">Khong co booking tre.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($lateBookings as $booking): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>#<?= (int) $booking->id ?> - <?= esc($booking->customer_name) ?></span>
                                <span class="small text-muted">Muộn: <?= (int) ($booking->late_minutes ?? 0) ?> phút</span>
                                <a href="/admin/bookings/show/<?= (int) $booking->id ?>">Kiem tra</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow-sm mt-3">
            <div class="card-header">Danh sách HOLD (queue)</div>
            <div class="card-body">
                <?php if (empty($holdQueue ?? [])): ?>
                    <div class="text-muted small">Không có booking nào đang HOLD.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($holdQueue as $booking): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>#<?= (int) $booking->id ?></strong> <?= esc($booking->customer_name) ?>
                                    <div class="small text-muted"><?= esc($booking->customer_phone) ?> · Sân: <?= esc($booking->court_names ?: 'Chưa gán') ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-nowrap">Hết hạn: <?= esc($booking->hold_until) ?></div>
                                    <form method="post" action="/admin/front-desk/release-hold/<?= (int) $booking->id ?>" class="d-inline mt-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="date" value="<?= esc($date) ?>">
                                        <button class="btn btn-sm btn-outline-primary">Mở HOLD</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
