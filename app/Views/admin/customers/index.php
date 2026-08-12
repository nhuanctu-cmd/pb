<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<?php
$statusList = [
    'active' => ['success', 'Hoạt động'],
    'inactive' => ['warning', 'Chưa tương tác'],
    'merged' => ['neutral', 'Merged'],
];
?>

<div class="erp-action-bar">
    <div>
        <h1 class="h4 mb-1">CRM Khách hàng</h1>
        <div class="erp-muted">Nâng cấp hồ sơ khách hàng theo dạng directory CRM (tách khỏi Player Registry).</div>
    </div>
    <a href="/admin/players" class="erp-btn"><i class="bi bi-person-vcard"></i> Player Registry</a>
</div>

<?= view('layouts/partials/stat_cards', ['stats' => [
    ['label' => 'Tổng hồ sơ', 'value' => (string) ($stats['total'] ?? 0), 'trend' => 'Toàn bộ khách đang có', 'icon' => 'bi-people'],
    ['label' => 'Hoạt động', 'value' => (string) ($stats['active'] ?? 0), 'trend' => 'Đang sử dụng thường xuyên', 'icon' => 'bi-person-check'],
    ['label' => 'Chưa kích hoạt', 'value' => (string) ($stats['inactive'] ?? 0), 'trend' => 'Cần tái kích hoạt', 'icon' => 'bi-person-dash'],
    ['label' => 'Đã gộp', 'value' => (string) ($stats['merged'] ?? 0), 'trend' => 'Merged records', 'icon' => 'bi-arrow-repeat'],
    ['label' => 'Booking', 'value' => (string) (int) floor((float) ($stats['booking_count'] ?? 0)), 'trend' => 'Booking đã ghi nhận', 'icon' => 'bi-calendar2-check'],
    ['label' => 'Doanh thu CRM', 'value' => number_format((float) ($stats['revenue'] ?? 0), 0, ',', '.') . 'đ', 'trend' => 'Tổng chi', 'icon' => 'bi-cash-stack'],
    ['label' => 'Mới tháng này', 'value' => (string) ($stats['new_this_month'] ?? 0), 'trend' => 'Tạo mới theo thời gian thực', 'icon' => 'bi-calendar-plus'],
]]) ?>

<?= view('layouts/partials/filter_bar', ['left' => '
    <input type="text" name="search" class="erp-control" style="min-width:280px" value="' . esc($filters['search'] ?? '') . '" placeholder="Tên / SĐT / Email">
    <select name="status" class="erp-select" style="width:170px"><option value="">Tất cả trạng thái</option>' .
    implode('', array_map(function ($status, $meta) use ($filters) {
        $selected = (($filters['status'] ?? '') === $status) ? ' selected' : '';
        return '<option value="' . esc($status) . '"' . $selected . '>' . esc($meta[1]) . '</option>';
    }, array_keys($statusList), $statusList)) . '</select>
    <select name="source" class="erp-select" style="width:150px"><option value="">Nguồn</option>' .
    implode('', array_map(function ($source) use ($filters) {
        $selected = (($filters['source'] ?? '') === $source) ? ' selected' : '';
        return '<option value="' . esc($source) . '"' . $selected . '>' . esc($source) . '</option>';
    }, $sourceOptions ?? ['booking'])) . '</select>
    <select name="has_player" class="erp-select" style="width:140px"><option value="">Liên kết Player</option><option value="yes"' . (((string) ($filters['has_player'] ?? '') === 'yes') ? ' selected' : '') . '>Đã liên kết</option><option value="no"' . (((string) ($filters['has_player'] ?? '') === 'no') ? ' selected' : '') . '>Chưa liên kết</option></select>
    <select name="tag_id" class="erp-select" style="min-width:180px"><option value="">Nhãn</option>' .
    implode('', array_map(function ($tag) use ($filters) {
        $selected = ((int) ($filters['tag_id'] ?? 0) === (int) $tag->id) ? ' selected' : '';
        return '<option value="' . (int) $tag->id . '"' . $selected . '>' . esc($tag->name) . '</option>';
    }, $tags ?? [])) . '</select>
', 'right' => '<button class="erp-btn erp-btn-primary"> <i class="bi bi-search"></i> Lọc</button><a href="/admin/customers" class="erp-btn erp-btn-ghost">Làm mới</a>']) ?>

<?php if (empty($customers)): ?>
<div class="erp-card erp-empty">
    <div class="erp-empty-icon"><i class="bi bi-person-lines-fill"></i></div>
    <h3>Không có hồ sơ khách trong bộ lọc này</h3>
    <p>Tạo mới booking sẽ tự sinh hồ sơ CRM sau đó tự động gán về khách này.</p>
    <a href="/admin/bookings/create" class="erp-btn erp-btn-primary"><i class="bi bi-calendar-plus"></i> Tạo booking đầu tiên</a>
</div>
<?php else: ?>
<div class="erp-table-wrap">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Khách hàng</th>
                <th>Liên hệ</th>
                <th>Nhãn</th>
                <th>Booking</th>
                <th>Tổng chi</th>
                <th>Nguồn</th>
                <th>Player liên kết</th>
                <th>Trạng thái</th>
                <th class="col-actions">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
                <?php
                    $statusMeta = $statusList[$customer->status ?? 'inactive'] ?? ['neutral', 'Không rõ'];
                    $customerTagRows = $customerTagsMap[(int) $customer->id] ?? [];
                    $hasPlayer = ! empty($customer->player_id);
                ?>
                <tr>
                    <td><strong><?= esc($customer->full_name) ?></strong><div class="erp-muted">#<?= (int) $customer->id ?></div></td>
                    <td><span><?= esc($customer->phone ?: '—') ?></span><div class="erp-muted"><?= esc($customer->email ?: '') ?></div></td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <?php if (!$customerTagRows): ?>
                                <span class="erp-muted">-</span>
                            <?php else: ?>
                                <?php foreach (array_slice($customerTagRows, 0, 3) as $tag): ?>
                                    <span class="erp-chip erp-status-neutral" style="background:<?= esc($tag->color ?: '#e8f0ff') ?>; border-color: <?= esc($tag->color ?: '#cfdcff') ?>; color:#1f2937"><?= esc($tag->name ?? $tag->code) ?></span>
                                <?php endforeach; ?>
                                <?php if (count($customerTagRows) > 3): ?>
                                    <span class="erp-muted">+<?= (int) (count($customerTagRows) - 3) ?></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?= (int) $customer->total_bookings ?></td>
                    <td><?= number_format((float) $customer->total_spend, 0, ',', '.') ?>đ</td>
                    <td><span class="erp-chip erp-status-neutral"><?= esc($customer->source ?: 'booking') ?></span></td>
                    <td>
                        <?php if ($hasPlayer): ?>
                            <a href="/admin/players/profile/<?= (int) $customer->player_id ?>" class="erp-btn"><i class="bi bi-person"></i> Player #<?= (int) $customer->player_id ?></a>
                        <?php else: ?>
                            <span class="erp-muted">Chưa liên kết</span>
                        <?php endif; ?>
                    </td>
                    <td><?= renderStatusBadge($customer->status, 'general') ?></td>
                    <td class="col-actions">
                        <a class="erp-btn erp-btn-icon" href="/admin/customers/show/<?= (int) $customer->id ?>" title="Chi tiết"><i class="bi bi-eye"></i></a>
                        <a class="erp-btn erp-btn-icon" href="/admin/customers/create-booking/<?= (int) $customer->id ?>" title="Tạo booking"><i class="bi bi-calendar-plus"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="erp-mobile-list">
    <?php foreach ($customers as $customer): ?>
        <article class="erp-card">
            <div class="erp-card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <strong><?= esc($customer->full_name) ?></strong>
                        <div class="erp-muted">#<?= (int) $customer->id ?> · <?= esc($customer->status) ?></div>
                    </div>
                    <?= renderStatusBadge($customer->status, 'general') ?>
                </div>
                <div class="erp-info-list">
                    <div class="erp-info-row"><span>Liên hệ</span><strong><?= esc($customer->phone ?: '—') ?></strong></div>
                    <div class="erp-info-row"><span>Booking</span><strong><?= (int) $customer->total_bookings ?></strong></div>
                    <div class="erp-info-row"><span>Tổng chi</span><strong><?= number_format((float) $customer->total_spend, 0, ',', '.') ?>đ</strong></div>
                    <div class="erp-info-row"><span>Nguồn</span><strong><?= esc($customer->source ?: 'booking') ?></strong></div>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <a href="/admin/customers/show/<?= (int) $customer->id ?>" class="erp-btn"><i class="bi bi-eye"></i> Xem</a>
                    <a href="/admin/customers/create-booking/<?= (int) $customer->id ?>" class="erp-btn"><i class="bi bi-calendar-plus"></i> Booking</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
