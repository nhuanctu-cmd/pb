<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$allCourts = [];
foreach ($courtGrid ?? [] as $group) {
    $allCourts = array_merge($allCourts, $group['courts'] ?? []);
}
?>
<div class="erp-action-bar">
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/courts/calendar?branch_id=<?= $currentBranchId ?? '' ?>" class="erp-btn"><i class="bi bi-calendar3"></i> Lịch sân</a>
        <a href="/admin/courts/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Tạo sân</a>
    </div>
    <button class="erp-btn" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
</div>

<?= view('layouts/partials/stat_cards', ['stats' => [
    ['label' => 'Tổng sân', 'value' => (string) count($allCourts), 'trend' => 'Theo bộ lọc hiện tại', 'icon' => 'bi-grid-3x3-gap'],
    ['label' => 'Đang hoạt động', 'value' => (string) count(array_filter($allCourts, fn ($c) => $c->status === 'available')), 'trend' => 'Có thể booking', 'icon' => 'bi-check-circle'],
    ['label' => 'Bảo trì', 'value' => (string) count(array_filter($allCourts, fn ($c) => $c->status === 'maintenance')), 'trend' => 'Cần theo dõi', 'icon' => 'bi-tools'],
    ['label' => 'Ngưng dùng', 'value' => (string) count(array_filter($allCourts, fn ($c) => $c->status === 'inactive')), 'trend' => 'Không nhận booking', 'icon' => 'bi-slash-circle'],
]]) ?>

<form method="get" action="/admin/courts">
    <?= view('layouts/partials/filter_bar', ['left' => '
        <select name="branch_id" class="erp-select" style="width:220px" onchange="this.form.submit()">
            <option value="">Chọn chi nhánh</option>' .
            implode('', array_map(fn ($branch) => '<option value="' . $branch->id . '" ' . (($currentBranchId ?? '') == $branch->id ? 'selected' : '') . '>' . esc($branch->name) . '</option>', $branches ?? [])) .
        '</select>
        <select name="court_type_id" class="erp-select" style="width:190px"><option value="">Tất cả loại sân</option>' .
            implode('', array_map(fn ($type) => '<option value="' . $type->id . '" ' . (($filters['court_type_id'] ?? '') == $type->id ? 'selected' : '') . '>' . esc($type->getName()) . '</option>', $courtTypes ?? [])) .
        '</select>
        <select name="status" class="erp-select" style="width:170px">
            <option value="">Tất cả trạng thái</option>
            <option value="available" ' . (($filters['status'] ?? '') === 'available' ? 'selected' : '') . '>Trống</option>
            <option value="occupied" ' . (($filters['status'] ?? '') === 'occupied' ? 'selected' : '') . '>Đang dùng</option>
            <option value="maintenance" ' . (($filters['status'] ?? '') === 'maintenance' ? 'selected' : '') . '>Bảo trì</option>
            <option value="inactive" ' . (($filters['status'] ?? '') === 'inactive' ? 'selected' : '') . '>Ngưng dùng</option>
        </select>
    ', 'right' => '<button type="submit" class="erp-btn"><i class="bi bi-funnel"></i> Lọc</button>']) ?>
</form>

<?php if (! empty($courtGrid)): ?>
    <?php foreach ($courtGrid as $group): ?>
        <section class="mb-4">
            <div class="erp-section-title">
                <h3>Tầng <?= esc($group['floor']) ?></h3>
                <span class="erp-chip erp-status-neutral"><?= count($group['courts']) ?> sân</span>
            </div>
            <div class="row g-3">
                <?php foreach ($group['courts'] as $court): ?>
                    <div class="col-12 col-md-6 col-xl-3">
                        <article class="erp-card h-100">
                            <div class="erp-card-body">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <strong><?= esc($court->code) ?></strong>
                                        <div class="erp-muted"><?= esc($court->getName()) ?></div>
                                    </div>
                                    <?= renderStatusBadge($court->status, 'court') ?>
                                </div>
                                <div class="erp-muted mt-2"><?= esc($court->court_type_name_vi ?? 'Pickleball') ?></div>
                                <div class="d-flex gap-2 flex-wrap mt-3">
                                    <button type="button" class="erp-btn" data-drawer-open="#erpQuickDrawer" data-drawer-url="/admin/ops/court-drawer/<?= $court->id ?>"><i class="bi bi-eye"></i> Xem nhanh</button>
                                    <a href="/admin/courts/edit/<?= $court->id ?>" class="erp-btn"><i class="bi bi-pencil"></i></a>
                                    <a href="/admin/courts/maintenance/<?= $court->id ?>" class="erp-btn"><i class="bi bi-tools"></i></a>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php else: ?>
    <div class="erp-card erp-empty">
        <div class="erp-empty-icon"><i class="bi bi-grid-3x3-gap"></i></div>
        <?= $currentBranchId ? 'Không có sân phù hợp bộ lọc.' : 'Chọn chi nhánh để xem lưới sân.' ?>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
