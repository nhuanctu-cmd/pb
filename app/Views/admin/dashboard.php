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
