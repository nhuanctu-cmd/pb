<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<?php
$stats = [
    [
        'label' => lang('Tenant.tenants'),
        'value' => $totalTenants ?? 0,
        'icon' => 'bi-building',
        'trend' => 'Đang hoạt động',
        'trendType' => 'success',
    ],
    [
        'label' => lang('Tenant.branches'),
        'value' => $totalBranches ?? 0,
        'icon' => 'bi-diagram-3',
        'trend' => 'Chi nhánh/cụm sân',
        'trendType' => 'success',
    ],
    [
        'label' => lang('Tenant.users'),
        'value' => $totalUsers ?? 0,
        'icon' => 'bi-people',
        'trend' => 'Tài khoản hệ thống',
        'trendType' => 'success',
    ],
    [
        'label' => 'Phiên hoạt động',
        'value' => '-',
        'icon' => 'bi-activity',
        'trend' => 'Theo thời gian thực',
        'trendType' => 'warning',
    ],
];
?>

<?= view('layouts/partials/stat_cards', ['stats' => $stats]) ?>

<?php $operations = $operations ?? []; $operationSummary = $operations['summary'] ?? []; ?>
<div class="row g-3 mb-4">
    <?php foreach ([['Vandaag booking', (int) ($operationSummary['total'] ?? 0), 'bi-calendar-check', 'primary'], ['Đang chơi', (int) ($operationSummary['playing'] ?? 0), 'bi-play-circle', 'success'], ['Doanh thu snapshot', number_format((float) ($operationSummary['revenue'] ?? 0), 0, ',', '.') . 'đ', 'bi-cash-stack', 'warning'], ['Đã thu', number_format((float) ($operationSummary['collected'] ?? 0), 0, ',', '.') . 'đ', 'bi-wallet2', 'info']] as $metric): ?>
        <div class="col-md-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="text-muted small"><?= esc($metric[0]) ?></div><div class="h5 mb-0"><?= esc((string) $metric[1]) ?></div></div><i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i></div></div></div>
    <?php endforeach; ?>
</div>
<?php $commerce = $operations['commerce'] ?? []; ?>
<div class="row g-3 mb-4">
    <?php foreach ([['Hóa đơn hôm nay', (int) ($commerce['invoices'] ?? 0), 'bi-receipt', 'primary'], ['Đã thu theo hóa đơn', number_format((float) ($commerce['collected'] ?? 0), 0, ',', '.') . 'đ', 'bi-cash-coin', 'success'], ['Công nợ mở', number_format((float) ($commerce['outstanding'] ?? 0), 0, ',', '.') . 'đ', 'bi-exclamation-circle', 'danger']] as $metric): ?>
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="text-muted small"><?= esc($metric[0]) ?></div><div class="h5 mb-0"><?= esc((string) $metric[1]) ?></div></div><i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i></div></div></div>
    <?php endforeach; ?>
</div>
<div class="row g-4 mb-4">
    <div class="col-lg-7"><div class="card shadow-sm"><div class="card-header d-flex justify-content-between"><span>Lịch sắp diễn ra hôm nay</span><a href="/admin/bookings" class="small">Mở booking</a></div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Khách</th><th>Giờ</th><th>Trạng thái</th></tr></thead><tbody><?php if (empty($operations['upcoming'])): ?><tr><td colspan="3" class="text-muted text-center py-3">Chưa có lịch.</td></tr><?php endif; ?><?php foreach (($operations['upcoming'] ?? []) as $booking): ?><tr><td><?= esc($booking->customer_name) ?></td><td><?= esc(substr((string) $booking->start_time, 0, 5) . ' - ' . substr((string) $booking->end_time, 0, 5)) ?></td><td><span class="badge text-bg-light"><?= esc($booking->status) ?></span></td></tr><?php endforeach; ?></tbody></table></div></div></div>
    <div class="col-lg-5"><div class="card shadow-sm"><div class="card-header">Vận hành tại quầy</div><div class="card-body"><div class="d-flex justify-content-between mb-2"><span>Sân khả dụng</span><strong><?= (int) ($operations['courts']['available'] ?? 0) ?></strong></div><div class="d-flex justify-content-between mb-2"><span>Sân đang sử dụng</span><strong><?= (int) ($operations['courts']['occupied'] ?? 0) ?></strong></div><div class="d-flex justify-content-between mb-3"><span>Walk-in hôm nay</span><strong><?= (int) array_sum($operations['walk_ins'] ?? []) ?></strong></div><a href="/admin/walk-ins" class="btn btn-outline-primary btn-sm me-1">Quầy walk-in</a><a href="/admin/waitlist" class="btn btn-outline-secondary btn-sm">Waitlist (<?= (int) ($operations['waitlist']['waiting'] ?? 0) ?>)</a></div></div></div>
</div>

<div class="erp-dashboard-grid">
    <section class="erp-card">
        <div class="erp-card-header">
            <div>
                <h2>Hoạt động gần đây</h2>
                <div class="erp-card-subtitle">Các thay đổi mới nhất trong hệ thống</div>
            </div>
            <a class="erp-btn" href="/admin/audit-logs">
                <i class="bi bi-clock-history"></i>
                Nhật ký
            </a>
        </div>
        <div class="erp-card-body p-0">
            <div class="table-responsive">
                <table class="table erp-table mb-0">
                    <thead>
                        <tr>
                            <th>Thao tác</th>
                            <th>Module</th>
                            <th>Người dùng</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($recentActivities)): ?>
                            <?php foreach ($recentActivities as $log): ?>
                                <?php
                                $statusClass = match ($log->action) {
                                    'create' => 'erp-status-success',
                                    'update' => 'erp-status-info',
                                    'delete' => 'erp-status-danger',
                                    default => 'erp-status-neutral',
                                };
                                ?>
                                <tr>
                                    <td><span class="erp-status <?= $statusClass ?>"><?= esc($log->action) ?></span></td>
                                    <td><?= esc($log->module) ?></td>
                                    <td><?= esc($log->user_id) ?></td>
                                    <td><?= format_datetime($log->created_at) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">
                                    <div class="erp-empty">
                                        <div class="erp-empty-icon"><i class="bi bi-inbox"></i></div>
                                        <?= lang('App.no_data') ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <aside class="erp-dashboard-stack">
        <section class="erp-card">
            <div class="erp-card-header">
                <h2>Thao tác nhanh</h2>
            </div>
            <div class="erp-card-body erp-quick-grid">
                <a href="/admin/bookings/create" class="erp-quick-tile">
                    <span class="erp-quick-icon erp-quick-icon-primary"><i class="bi bi-calendar-plus"></i></span>
                    <span>Đặt sân mới</span>
                </a>
                <a href="/admin/courts/create" class="erp-quick-tile">
                    <span class="erp-quick-icon erp-quick-icon-success"><i class="bi bi-grid-3x3-gap"></i></span>
                    <span>Thêm sân</span>
                </a>
                <a href="/admin/players/create" class="erp-quick-tile">
                    <span class="erp-quick-icon erp-quick-icon-info"><i class="bi bi-person-plus"></i></span>
                    <span>Thêm người chơi</span>
                </a>
                <a href="/admin/memberships/packages" class="erp-quick-tile">
                    <span class="erp-quick-icon erp-quick-icon-warning"><i class="bi bi-award"></i></span>
                    <span>Gói hội viên</span>
                </a>
                <a href="/admin/facilities/create" class="erp-quick-tile">
                    <span class="erp-quick-icon erp-quick-icon-primary"><i class="bi bi-buildings"></i></span>
                    <span>Thêm cụm sân</span>
                </a>
                <a href="/admin/settings" class="erp-quick-tile">
                    <span class="erp-quick-icon erp-quick-icon-neutral"><i class="bi bi-gear"></i></span>
                    <span>Thiết lập</span>
                </a>
            </div>
        </section>

        <section class="erp-card">
            <div class="erp-card-header">
                <h2>Tổng quan vận hành</h2>
            </div>
            <div class="erp-card-body erp-alert-list">
                <div class="erp-alert-item">
                    <i class="bi bi-check-circle text-success"></i>
                    <div>
                        <strong>Hệ thống sẵn sàng</strong>
                        <div class="erp-muted">Dashboard đang dùng layout ERP mới.</div>
                    </div>
                </div>
                <div class="erp-alert-item">
                    <i class="bi bi-layout-text-window text-primary"></i>
                    <div>
                        <strong>Giao diện đồng bộ</strong>
                        <div class="erp-muted">Các trang admin cùng nạp chung khung sidebar/topbar.</div>
                    </div>
                </div>
            </div>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
