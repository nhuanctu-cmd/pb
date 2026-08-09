<?= $this->extend('layouts/admin_master') ?>

<?= $this->section('content') ?>
<div class="erp-action-bar">
    <a href="/admin/courts" class="erp-btn"><i class="bi bi-grid"></i> Danh sách sân</a>
    <a href="/admin/bookings/create" class="erp-btn erp-btn-primary"><i class="bi bi-plus-lg"></i> Tạo booking</a>
</div>

<form method="get" action="/admin/courts/calendar">
    <?= view('layouts/partials/filter_bar', ['left' => '
        <select name="branch_id" class="erp-select" style="width:220px" onchange="this.form.submit()">
            <option value="">Chọn chi nhánh</option>' .
            implode('', array_map(fn ($branch) => '<option value="' . $branch->id . '" ' . (($currentBranchId ?? '') == $branch->id ? 'selected' : '') . '>' . esc($branch->name) . '</option>', $branches ?? [])) .
        '</select>
        <input type="date" name="date" class="erp-control" style="width:170px" value="' . esc($_GET['date'] ?? date('Y-m-d')) . '">
    ', 'right' => '<button type="submit" class="erp-btn"><i class="bi bi-search"></i> Xem lịch</button>']) ?>
</form>

<?php if (! empty($courts) && $currentBranchId): ?>
    <?php
    $hours = range(6, 22);
    $selectedDate = $_GET['date'] ?? date('Y-m-d');
    ?>
    <section class="erp-card">
        <div class="erp-card-header"><strong>Lịch sân theo khung giờ</strong><span class="erp-muted"><?= esc($selectedDate) ?></span></div>
        <div class="erp-card-body">
            <div class="erp-table-wrap">
                <table class="erp-table">
                    <thead>
                        <tr>
                            <th>Giờ</th>
                            <?php foreach ($courts as $group): ?>
                                <?php foreach ($group['courts'] as $court): ?>
                                    <th><?= esc($court->code) ?><div class="erp-muted"><?= esc($court->name_vi) ?></div></th>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hours as $hour): ?>
                            <tr>
                                <td><strong><?= sprintf('%02d:00', $hour) ?></strong></td>
                                <?php foreach ($courts as $group): ?>
                                    <?php foreach ($group['courts'] as $court): ?>
                                        <td>
                                            <?= renderStatusBadge($court->status, 'court') ?>
                                            <?php if ($court->status === 'available'): ?>
                                                <div class="mt-2"><a class="erp-btn erp-btn-primary" href="/admin/bookings/create?court_id=<?= $court->id ?>&date=<?= $selectedDate ?>&time=<?= sprintf('%02d:00', $hour) ?>">Book</a></div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="erp-mobile-list mt-3">
                <?php foreach ($courts as $group): ?>
                    <?php foreach ($group['courts'] as $court): ?>
                        <article class="erp-mobile-card">
                            <strong><?= esc($court->code) ?> - <?= esc($court->name_vi) ?></strong>
                            <div class="mt-2"><?= renderStatusBadge($court->status, 'court') ?></div>
                        </article>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php else: ?>
    <div class="erp-card erp-empty">
        <div class="erp-empty-icon"><i class="bi bi-calendar3"></i></div>
        Chọn chi nhánh để xem lịch sân.
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
