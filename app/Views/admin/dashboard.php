<?= $this->extend('admin/layouts/main') ?>

<?= $this->section('content') ?>
<?php
$operations = $operations ?? [];
$dashboardContext = $dashboardContext ?? [];
$roleSlug = $dashboardContext['role_slug'] ?? 'staff';
$scopeLabel = !empty($dashboardContext['is_global']) ? 'Toàn tenant' : 'Chi nhánh phụ trách';
$roleFocus = [
    'super-admin' => 'Theo dõi toàn tenant, doanh thu và sức khỏe hệ thống.',
    'owner' => 'Theo dõi tăng trưởng, tài chính và hiệu suất câu lạc bộ.',
    'branch-manager' => 'Tập trung booking, sân, doanh thu và hoạt động tại cơ sở.',
    'staff' => 'Xử lý lịch đặt sân, check-in, walk-in và thanh toán tại quầy.',
    'referee' => 'Theo dõi giải đấu, lịch trận và nhập kết quả.',
][$roleSlug] ?? 'Theo dõi các hoạt động chính trong ngày.';
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
        'label' => 'Người chơi',
        'value' => (int) ($operations['totals']['players'] ?? 0),
        'icon' => 'bi-person-badge',
        'trend' => 'Dữ liệu CRM',
        'trendType' => 'success',
    ],
    [
        'label' => 'Sân',
        'value' => (int) ($operations['totals']['courts'] ?? 0),
        'icon' => 'bi-grid-3x3-gap',
        'trend' => $scopeLabel,
        'trendType' => 'success',
    ],
];
?>

<?= view('layouts/partials/stat_cards', ['stats' => $stats]) ?>

<div class="card shadow-sm border-0 mb-4" style="background:linear-gradient(135deg,#0d6efd,#0b4fa8);color:#fff">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 p-4">
        <div>
            <div class="small opacity-75"><?= esc($dashboardContext['greeting'] ?? 'Xin chào') ?></div>
            <h1 class="h3 mb-2"><?= esc($dashboardContext['name'] ?? 'bạn') ?> 👋</h1>
            <div class="opacity-90"><?= esc($roleFocus) ?></div>
        </div>
        <div class="text-md-end">
            <span class="badge rounded-pill text-bg-light mb-2"><?= esc($dashboardContext['role'] ?? 'Nhân viên') ?></span>
            <div class="small opacity-75"><?= esc($dashboardContext['tenant_name'] ?? 'Pickleball') ?></div>
            <div class="fw-semibold"><?= esc($dashboardContext['branch_name'] ?? 'Chi nhánh phụ trách') ?></div>
        </div>
    </div>
</div>

<div class="alert alert-primary d-flex align-items-center justify-content-between gap-3 mb-4" role="alert">
    <div>
        <i class="bi bi-building me-2"></i>
        <strong><?= esc($currentTenantName ?? 'Tenant hiện tại') ?></strong>
        <span class="ms-2">Dữ liệu đang được lọc theo <?= esc($dashboardContext['is_global'] ?? false ? 'toàn tenant' : 'chi nhánh phụ trách') ?>.</span>
    </div>
    <?php if (is_superadmin()): ?><a class="btn btn-sm btn-primary" href="/admin/tenants/select">Đổi tenant</a><?php endif; ?>
</div>

<section class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3"><div><h2 class="h5 mb-1">Commercial Control</h2><div class="text-muted small">Bốn điểm chạm tạo doanh thu và giúp chủ sân điều hành mỗi ngày</div></div><span class="badge text-bg-light">Owner workflow</span></div>
    <div class="row g-3">
        <?php foreach (($commercialLinks ?? []) as $link): ?><div class="col-6 col-xl-3"><a href="<?= esc($link['url']) ?>" class="card h-100 shadow-sm text-decoration-none"><div class="card-body"><div class="d-flex justify-content-between align-items-start"><span class="badge text-bg-<?= esc($link['color']) ?>"><i class="bi <?= esc($link['icon']) ?>"></i></span><i class="bi bi-arrow-up-right text-muted"></i></div><h3 class="h6 text-dark mt-3 mb-1"><?= esc($link['label']) ?></h3><div class="text-muted small"><?= esc($link['description']) ?></div></div></a></div><?php endforeach; ?>
    </div>
</section>

<?php $platformStats = $platformStats ?? []; ?>
<section class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h2 class="h5 mb-1">Dữ liệu hệ thống</h2>
            <div class="text-muted small">Tổng quan các module đã seed trong tenant này</div>
        </div>
        <a href="/admin/operations-report" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bar-chart me-1"></i>Báo cáo vận hành</a>
    </div>
    <div class="row g-3">
        <?php foreach ($platformStats as $metric): ?>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="<?= esc($metric['link'] ?? '#') ?>" class="card h-100 shadow-sm text-decoration-none">
                    <div class="card-body d-flex align-items-center justify-content-between gap-2">
                        <div><div class="text-muted small"><?= esc($metric['label'] ?? '') ?></div><div class="h4 mb-0 text-dark"><?= esc((string) ($metric['value'] ?? 0)) ?></div></div>
                        <i class="bi <?= esc($metric['icon'] ?? 'bi-bar-chart') ?> fs-2 text-primary"></i>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php $operationSummary = $operations['summary'] ?? []; ?>
<div class="card shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap gap-3 align-items-end justify-content-between">
        <div>
            <div class="text-muted small">Ngày vận hành</div>
            <div class="fw-semibold">Xem snapshot booking, doanh thu, walk-in và lịch trong ngày</div>
        </div>
        <form method="get" action="/admin/dashboard" class="d-flex gap-2 align-items-end">
            <div>
                <label for="dashboard-date" class="form-label small mb-1">Chọn ngày</label>
                <input id="dashboard-date" type="date" name="date" value="<?= esc($operations['date'] ?? date('Y-m-d')) ?>" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-filter me-1"></i>Áp dụng</button>
            <a href="/admin/dashboard" class="btn btn-outline-secondary btn-sm">Hôm nay</a>
        </form>
    </div>
</div>
<?php $totals = $operations['totals'] ?? []; ?>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Booking lũy kế', (int) ($totals['bookings'] ?? 0), 'bi-calendar2-check', 'primary', '/admin/bookings'],
        ['Hội viên', (int) ($totals['memberships'] ?? 0), 'bi-award', 'success', '/admin/memberships'],
        ['Giải đấu', (int) ($totals['tournaments'] ?? 0), 'bi-trophy', 'warning', '/admin/tournaments'],
        ['Đơn POS', (int) ($totals['pos_orders'] ?? 0), 'bi-bag-check', 'info', '/admin/pos'],
    ] as $metric): ?>
        <div class="col-6 col-xl-3"><a href="<?= esc($metric[4]) ?>" class="text-decoration-none"><div class="card shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="text-muted small"><?= esc($metric[0]) ?></div><div class="h5 mb-0 text-dark"><?= esc((string) $metric[1]) ?></div><small class="text-muted"><?= esc($scopeLabel) ?></small></div><i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i></div></div></a></div>
    <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Hóa đơn lũy kế', (int) ($totals['invoices'] ?? 0), 'bi-receipt-cutoff', 'primary', '/admin/payments'],
        ['Mở chơi', (int) ($totals['open_play_sessions'] ?? 0), 'bi-people-fill', 'success', '/admin/open-play'],
        ['Coaching', (int) ($totals['coaching_sessions'] ?? 0), 'bi-person-video3', 'warning', '/admin/coaching'],
        ['Bài cộng đồng', (int) ($totals['community_posts'] ?? 0), 'bi-chat-square-text', 'info', '/admin/growth'],
    ] as $metric): ?>
        <div class="col-6 col-xl-3"><a href="<?= esc($metric[4]) ?>" class="text-decoration-none"><div class="card shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="text-muted small"><?= esc($metric[0]) ?></div><div class="h5 mb-0 text-dark"><?= esc((string) $metric[1]) ?></div><small class="text-muted"><?= esc($scopeLabel) ?></small></div><i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i></div></div></a></div>
    <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['Doanh số lũy kế', number_format((float) ($totals['billed'] ?? 0), 0, ',', '.') . 'đ', 'bi-bar-chart-line', 'primary'],
        ['Đã thu lũy kế', number_format((float) ($totals['collected'] ?? 0), 0, ',', '.') . 'đ', 'bi-wallet2', 'success'],
        ['Công nợ lũy kế', number_format((float) ($totals['outstanding'] ?? 0), 0, ',', '.') . 'đ', 'bi-exclamation-circle', 'danger'],
    ] as $metric): ?>
        <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body d-flex justify-content-between align-items-center"><div><div class="text-muted small"><?= esc($metric[0]) ?></div><div class="h5 mb-0"><?= esc((string) $metric[1]) ?></div></div><i class="bi <?= esc($metric[2]) ?> fs-2 text-<?= esc($metric[3]) ?>"></i></div></div></div>
    <?php endforeach; ?>
</div>
<div class="row g-3 mb-4">
    <?php foreach ([['Booking ngày ' . ($operations['date'] ?? date('Y-m-d')), (int) ($operationSummary['total'] ?? 0), 'bi-calendar-check', 'primary'], ['Đang chơi', (int) ($operationSummary['playing'] ?? 0), 'bi-play-circle', 'success'], ['Doanh thu ngày', number_format((float) ($operationSummary['revenue'] ?? 0), 0, ',', '.') . 'đ', 'bi-cash-stack', 'warning'], ['Đã thu ngày', number_format((float) ($operationSummary['collected'] ?? 0), 0, ',', '.') . 'đ', 'bi-wallet2', 'info']] as $metric): ?>
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
