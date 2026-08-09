<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$courtsFlat = [];
foreach (($courtGrid ?? []) as $group) {
    foreach (($group['courts'] ?? []) as $court) {
        $courtsFlat[] = $court;
    }
}
$total = count($courtsFlat);
$available = count(array_filter($courtsFlat, fn ($c) => $c->status === 'available'));
$maintenance = count(array_filter($courtsFlat, fn ($c) => $c->status === 'maintenance'));
$active = count(array_filter($courtsFlat, fn ($c) => in_array($c->status, ['available', 'occupied'], true)));
?>

<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/courts/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Thêm sân</a>
        <button class="erp-btn"><i class="bi bi-file-earmark-excel"></i> Xuất Excel</button>
        <button class="erp-btn" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
    </div>
    <div class="erp-muted">Quản lý trạng thái sân, bảo trì, rule giá và lịch hôm nay.</div>
</div>

<?= view('layouts/partials/stat_cards', ['stats' => [
    ['label' => 'Tổng số sân', 'value' => (string) $total, 'trend' => 'Trong chi nhánh đang chọn', 'icon' => 'bi-grid-3x3-gap'],
    ['label' => 'Sân đang hoạt động', 'value' => (string) $active, 'trend' => 'Có thể vận hành', 'icon' => 'bi-play-circle'],
    ['label' => 'Sân bảo trì', 'value' => (string) $maintenance, 'trend' => $maintenance ? 'Cần theo dõi' : 'Không có bảo trì', 'trendType' => $maintenance ? 'danger' : 'success', 'icon' => 'bi-tools'],
    ['label' => 'Sân trống hôm nay', 'value' => (string) $available, 'trend' => 'Theo status hiện tại', 'icon' => 'bi-check2-circle'],
]]) ?>

<?= view('layouts/partials/filter_bar', ['left' => '
    <select name="branch_id" class="erp-select" style="width:220px" onchange="location.href=\'/admin/courts?branch_id=\'+this.value">
        <option value="">Chọn chi nhánh</option>' .
        implode('', array_map(fn ($b) => '<option value="' . $b->id . '" ' . (($currentBranchId ?? '') == $b->id ? 'selected' : '') . '>' . esc($b->name) . '</option>', $branches ?? [])) .
    '</select>
    <select class="erp-select" style="width:190px" name="court_type_id">
        <option value="">Tất cả loại sân</option>' .
        implode('', array_map(fn ($t) => '<option value="' . $t->id . '" ' . (($filters['court_type_id'] ?? '') == $t->id ? 'selected' : '') . '>' . esc($t->name_vi) . '</option>', $courtTypes ?? [])) .
    '</select>
    <select class="erp-select" style="width:170px" name="status">
        <option value="">Tất cả trạng thái</option>
        <option value="available">Trống</option>
        <option value="occupied">Đang dùng</option>
        <option value="maintenance">Bảo trì</option>
        <option value="inactive">Ngưng dùng</option>
    </select>
']) ?>

<div class="erp-table-toolbar">
    <div class="d-flex gap-2 align-items-center">
        <button class="erp-btn erp-btn-ghost active"><i class="bi bi-grid"></i> Card view</button>
        <button class="erp-btn erp-btn-ghost"><i class="bi bi-table"></i> Table view</button>
    </div>
    <div class="erp-muted">Click vào mắt để xem nhanh thông tin sân và rule giá.</div>
</div>

<?php if (! empty($courtsFlat)): ?>
    <?php foreach (($courtGrid ?? []) as $group): ?>
        <section class="erp-section">
            <div class="erp-section-title">
                <h2>Tầng <?= esc($group['floor']) ?></h2>
                <span class="erp-chip erp-status-neutral"><?= count($group['courts']) ?> sân</span>
            </div>
            <div class="row g-3">
                <?php foreach ($group['courts'] as $court): ?>
                    <div class="col-sm-6 col-xl-3">
                        <div class="erp-card h-100">
                            <div class="erp-card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="erp-muted"><?= esc($court->court_type_name_vi ?? 'Pickleball') ?></div>
                                        <h3 class="h5 mb-1"><?= esc($court->code) ?> - <?= esc($court->getName()) ?></h3>
                                    </div>
                                    <?= renderStatusBadge($court->status, 'court') ?>
                                </div>
                                <div class="erp-info-list mb-3">
                                    <div class="erp-info-row"><span>Không gian</span><strong><?= $court->is_indoor ? 'Trong nhà' : 'Ngoài trời' ?></strong></div>
                                    <div class="erp-info-row"><span>Diện tích</span><strong><?= esc($court->area ?: '—') ?> m²</strong></div>
                                    <div class="erp-info-row"><span>Tiện ích</span><strong><?= $court->has_light ? 'Đèn ' : '' ?><?= $court->has_fan ? 'Quạt ' : '' ?><?= $court->has_camera ? 'Camera' : '' ?></strong></div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="erp-btn erp-btn-icon" data-drawer-open="#erpQuickDrawer" data-drawer-url="/admin/ops/court-drawer/<?= $court->id ?>" title="Xem nhanh"><i class="bi bi-eye"></i></button>
                                    <a class="erp-btn erp-btn-icon" href="/admin/courts/edit/<?= $court->id ?>" title="Sửa"><i class="bi bi-pencil"></i></a>
                                    <a class="erp-btn erp-btn-icon" href="/admin/courts/maintenance/<?= $court->id ?>" title="Bảo trì"><i class="bi bi-tools"></i></a>
                                    <a class="erp-btn erp-btn-icon" href="/admin/bookings?date=<?= date('Y-m-d') ?>&court_id=<?= $court->id ?>" title="Booking hôm nay"><i class="bi bi-calendar-day"></i></a>
                                    <a class="erp-btn erp-btn-icon" href="/admin/pricing-rules?court_id=<?= $court->id ?>" title="Giá đang áp dụng"><i class="bi bi-cash-coin"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="erp-table-wrap mt-3">
        <table class="erp-table">
            <thead><tr><th>Mã sân</th><th>Tên sân</th><th>Loại</th><th>Tầng</th><th>Tiện ích</th><th>Trạng thái</th><th class="col-actions">Action</th></tr></thead>
            <tbody>
            <?php foreach ($courtsFlat as $court): ?>
                <tr>
                    <td><strong><?= esc($court->code) ?></strong></td>
                    <td><?= esc($court->getName()) ?></td>
                    <td><?= esc($court->court_type_name_vi ?? '-') ?></td>
                    <td><?= esc($court->floor ?? 1) ?></td>
                    <td><?= $court->has_light ? 'Đèn ' : '' ?><?= $court->has_fan ? 'Quạt ' : '' ?><?= $court->has_camera ? 'Camera' : '' ?></td>
                    <td><?= renderStatusBadge($court->status, 'court') ?></td>
                    <td class="col-actions"><button class="erp-btn erp-btn-icon" data-drawer-open="#erpQuickDrawer" data-drawer-url="/admin/ops/court-drawer/<?= $court->id ?>"><i class="bi bi-eye"></i></button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="erp-card erp-empty">
        <div class="erp-empty-icon"><i class="bi bi-grid-3x3-gap"></i></div>
        <h3>Chưa có sân</h3>
        <p>Chọn chi nhánh hoặc tạo sân đầu tiên để bắt đầu vận hành.</p>
        <a href="/admin/courts/create" class="erp-btn erp-btn-primary">Thêm sân</a>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
