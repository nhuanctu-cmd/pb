<?= $this->extend('admin/layouts/main') ?>
<?= $this->section('content') ?>

<?php
$operationSummary = $operations['summary'] ?? [];
$courts = $operations['courts'] ?? [];
$waitlist = $operations['waitlist'] ?? [];
$branches = $branches ?? [];
$mtd = $mtd ?? [];
$ytd = $ytd ?? [];
$topCustomers = $topCustomers ?? [];
$offPeakCustomers = $offPeakCustomers ?? [];
$discrepancyAlerts = $discrepancyAlerts ?? [];
$creditAlerts = $creditAlerts ?? [];
$totalOutstanding = 0;
$totalDiscrepancy = 0;
foreach ($creditAlerts as $alert) {
    $totalOutstanding += (float) ($alert->outstanding ?? 0);
}
foreach ($discrepancyAlerts as $alert) {
    $totalDiscrepancy += (float) ($alert->discrepancy_amount ?? 0);
}
?>

<div class="mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <h1 class="h3 mb-1">Owner Dashboard</h1>
        <div class="text-muted">Màn hình vận hành thương mại cho quản lý chủ sân</div>
    </div>
    <a class="btn btn-outline-primary" href="/admin/dashboard?date=<?= esc($date) ?>">Dashboard tổng</a>
</div>

<form class="card card-body mb-3" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small">Ngày vận hành</label>
            <input type="date" class="form-control" name="date" value="<?= esc($date) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Lọc theo nhanh quầy</label>
            <select class="form-select" name="branch_id">
                <option value="">Toàn tenant</option>
                <?php if (!empty($branches ?? [])): ?>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= (int) $branch->id ?>" <?= ((int) ($scopeBranchId ?? 0) === (int) $branch->id) ? 'selected' : '' ?>>
                            <?= esc($branch->name) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100">Áp dụng</button>
        </div>
    </div>
</form>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['Khách đang xếp', (int) ($operationSummary['total'] ?? 0), 'bi-calendar-check', 'primary', '/admin/front-desk?date=' . $date],
        ['Đang chơi', (int) ($operationSummary['playing'] ?? 0), 'bi-play-circle', 'success', '/admin/front-desk?date=' . $date],
        ['Sân đang sử dụng', (int) ($courts['occupied'] ?? 0), 'bi-grid-3x3-gap', 'danger', '/admin/courts'],
        ['Sân trống', (int) ($courts['available'] ?? 0), 'bi-border-style', 'info', '/admin/courts'],
        ['Doanh thu booking', number_format((float) ($operationSummary['revenue'] ?? 0), 0, ',', '.') . 'đ', 'bi-wallet2', 'success', '/admin/daily-closing?date=' . $date],
        ['Doanh thu đã thu', number_format((float) ($operationSummary['collected'] ?? 0), 0, ',', '.') . 'đ', 'bi-cash-stack', 'warning', '/admin/payments'],
    ] as $metric): ?>
        <div class="col-6 col-lg-4">
            <a href="<?= esc($metric[4]) ?>" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small"><?= esc($metric[0]) ?></div>
                            <div class="h5 mb-0 text-<?= esc($metric[3]) ?>"><?= esc((string) $metric[1]) ?></div>
                        </div>
                        <i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['MTD doanh thu', number_format((float) ($mtd['billed'] ?? 0), 0, ',', '.') . 'đ', 'bi-graph-up-arrow', 'primary', '/admin/owner-dashboard?date=' . $date],
        ['MTD thu tiền', number_format((float) ($mtd['collected'] ?? 0), 0, ',', '.') . 'đ', 'bi-cash', 'success', '/admin/owner-dashboard?date=' . $date],
        ['YTD doanh thu', number_format((float) ($ytd['billed'] ?? 0), 0, ',', '.') . 'đ', 'bi-bar-chart-line', 'danger', '/admin/owner-dashboard?date=' . $date],
        ['YTD thu tiền', number_format((float) ($ytd['collected'] ?? 0), 0, ',', '.') . 'đ', 'bi-wallet2', 'warning', '/admin/owner-dashboard?date=' . $date],
    ] as $metric): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= esc($metric[4]) ?>" class="text-decoration-none">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small"><?= esc($metric[0]) ?></div>
                            <div class="h5 mb-0 text-<?= esc($metric[3]) ?>"><?= esc((string) $metric[1]) ?></div>
                        </div>
                        <i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">Cảnh báo vận hành tài chính</div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between">
                    <span>Nợ quá hạn (Outstanding)</span>
                    <strong><?= number_format((float) $totalOutstanding, 0, ',', '.') ?>đ</strong>
                </div>
                <div class="list-group-item d-flex justify-content-between">
                    <span>Chênh lệch chốt ca</span>
                    <strong><?= number_format((float) $totalDiscrepancy, 0, ',', '.') ?>đ</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">Danh sách cần gia hạn hôm nay</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Khách</th>
                            <th>Gói</th>
                            <th>Hết hạn</th>
                            <th>Còn lại</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($renewals)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Không có hội viên cần follow-up trong 30 ngày.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($renewals as $membership): ?>
                            <tr>
                                <td>
                                    <strong><?= esc($membership->full_name) ?></strong><br>
                                    <small class="text-muted"><?= esc($membership->phone) ?> | <?= esc($membership->player_code) ?></small>
                                </td>
                                <td><?= esc($membership->package_name_vi ?? $membership->package_name_en ?? '-') ?></td>
                                <td><?= esc($membership->end_date) ?></td>
                                <td><span class="badge <?= ((int) ($membership->remaining_days ?? 0) < 0 ? 'text-bg-danger' : 'text-bg-warning') ?>"><?= (int) ($membership->remaining_days ?? 0) ?> ngày</span></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/admin/memberships/renewals">Gia hạn</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Đến hạn sớm (<=7 ngày)</span>
                <a class="small" href="/admin/memberships/renewals">Xem tất cả</a>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($soonExpired)): ?>
                    <div class="list-group-item text-muted">Không có hội viên nào sắp tới hạn.</div>
                <?php endif; ?>
                <?php foreach ($soonExpired as $membership): ?>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><strong><?= esc($membership->full_name) ?></strong><br><small class="text-muted"><?= esc($membership->phone) ?></small></span>
                        <span class="badge text-bg-warning"><?= (int) ($membership->remaining_days ?? 0) ?> ngày</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Quick actions</span>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary" href="/admin/front-desk?date=<?= esc($date) ?>"><i class="bi bi-person-workspace me-1"></i>Front Desk</a>
                    <a class="btn btn-outline-success" href="/admin/daily-closing?date=<?= esc($date) ?>"><i class="bi bi-cash-stack me-1"></i>Chốt ca hôm nay</a>
                    <a class="btn btn-outline-warning" href="/admin/crm-campaigns"><i class="bi bi-megaphone-fill me-1"></i>CRM Campaign</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header">Top khách ngoài giờ cao điểm (MTD)</div>
            <div class="list-group list-group-flush">
                <?php if (empty($offPeakCustomers)): ?>
                    <div class="list-group-item text-muted">Không có dữ liệu ngoài giờ cao điểm trong tháng.</div>
                <?php endif; ?>
                <?php foreach ($offPeakCustomers as $customer): ?>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><strong><?= esc($customer->player_name ?: 'Khách hàng') ?></strong><br><small class="text-muted"><?= esc($customer->player_phone ?: '-') ?></small></span>
                        <span class="text-end"><strong><?= number_format((float) ($customer->revenue ?? 0), 0, ',', '.') ?>đ</strong><br><small class="text-muted"><?= (int) ($customer->booking_count ?? 0) ?> lượt</small></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-header">Top khách chi tiêu cao</div>
            <div class="list-group list-group-flush">
                <?php if (empty($topCustomers)): ?>
                    <div class="list-group-item text-muted">Không có dữ liệu phân tích trong tháng.</div>
                <?php endif; ?>
                <?php foreach ($topCustomers as $customer): ?>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><strong><?= esc($customer->player_name ?: 'Khách hàng') ?></strong><br><small class="text-muted"><?= esc($customer->player_phone ?: '-') ?></small></span>
                        <span class="text-end"><strong><?= number_format((float) ($customer->revenue ?? 0), 0, ',', '.') ?>đ</strong><br><small class="text-muted"><?= (int) ($customer->booking_count ?? 0) ?> lượt</small></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">Chi tiết cảnh báo</div>
            <div class="list-group list-group-flush">
                <?php if (empty($discrepancyAlerts) && empty($creditAlerts)): ?>
                    <div class="list-group-item text-muted">Hiện tại hệ thống cân đối ổn.</div>
                <?php endif; ?>
                <?php foreach ($discrepancyAlerts as $alert): ?>
                    <div class="list-group-item d-flex justify-content-between">
                        <span>Chốt ca <?= esc($alert->closing_date ?? '') ?> | <?= esc($alert->status ?? '') ?></span>
                        <strong class="<?= ((float) ($alert->discrepancy_amount ?? 0) >= 0 ? 'text-success' : 'text-danger') ?>">
                            <?= number_format((float) ($alert->discrepancy_amount ?? 0), 0, ',', '.') ?>đ
                        </strong>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($creditAlerts as $alert): ?>
                    <div class="list-group-item d-flex justify-content-between">
                        <span><?= esc($alert->player_name ?: 'Khách hàng') ?> · HD: <?= esc($alert->invoice_code ?? '') ?></span>
                        <strong class="text-warning">Nợ: <?= number_format((float) ($alert->outstanding ?? 0), 0, ',', '.') ?>đ</strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
