<?= $this->extend('layouts/admin_master') ?>
<?= $this->section('content') ?>
<?= view('layouts/partials/filter_bar', ['left' => '
    <select name="branch_id" class="erp-select" style="width:220px"><option value="">Tất cả chi nhánh</option>' .
    implode('', array_map(fn ($b) => '<option value="' . $b->id . '">' . esc($b->name) . '</option>', $branches ?? [])) .
    '</select>
    <input type="date" name="date" class="erp-control" style="width:170px">
    <select name="status" class="erp-select" style="width:160px"><option value="">Tất cả</option><option value="open">Open</option><option value="matched">Matched</option><option value="cancelled">Cancelled</option></select>
']) ?>
<h3 class="h5 mb-3">Kèo đang mở</h3>
<div class="erp-table-wrap mb-4">
    <table class="erp-table">
        <thead><tr><th>Người tạo</th><th>Chi nhánh</th><th>Thời gian</th><th>Level</th><th>Cần</th><th>Trạng thái</th><th class="col-actions">Action</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $request): ?>
            <tr>
                <td><?= esc($request->full_name) ?></td>
                <td><?= esc($request->branch_name) ?></td>
                <td><?= esc($request->preferred_date) ?><div class="erp-muted"><?= substr($request->preferred_start_time, 0, 5) ?>-<?= substr($request->preferred_end_time, 0, 5) ?></div></td>
                <td><?= (int) $request->level_from ?> - <?= (int) $request->level_to ?></td>
                <td><?= (int) $request->need_players ?></td>
                <td><span class="erp-chip erp-status-neutral"><?= esc($request->status) ?></span></td>
                <td class="col-actions">
                    <a href="/admin/matches/show/<?= $request->id ?>" class="erp-btn erp-btn-icon"><i class="bi bi-eye"></i></a>
                    <form method="post" action="/admin/matches/approve/<?= $request->id ?>" class="d-inline"><?= csrf_field() ?><button class="erp-btn erp-btn-icon" title="Auto match"><i class="bi bi-magic"></i></button></form>
                    <form method="post" action="/admin/matches/cancel/<?= $request->id ?>" class="d-inline"><?= csrf_field() ?><button class="erp-btn erp-btn-icon" title="Cancel"><i class="bi bi-x-circle"></i></button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<h3 class="h5 mb-3">Trận social</h3>
<div class="erp-table-wrap">
    <table class="erp-table">
        <thead><tr><th>Chi nhánh</th><th>Thời gian</th><th>Booking</th><th>Trạng thái</th><th class="col-actions">Action</th></tr></thead>
        <tbody>
        <?php foreach ($matches as $match): ?>
            <tr>
                <td><?= esc($match->branch_name) ?></td>
                <td><?= esc($match->match_date) ?><div class="erp-muted"><?= substr($match->start_time, 0, 5) ?>-<?= substr($match->end_time, 0, 5) ?></div></td>
                <td><?= $match->booking_id ? '#' . $match->booking_id : '-' ?></td>
                <td><span class="erp-chip erp-status-neutral"><?= esc($match->status) ?></span></td>
                <td class="col-actions">
                    <?php if (! $match->booking_id): ?><form method="post" action="/admin/matches/convert/<?= $match->id ?>"><?= csrf_field() ?><button class="erp-btn"><i class="bi bi-calendar-plus"></i> Booking</button></form><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>
